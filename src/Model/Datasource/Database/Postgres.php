<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @package       Cake.Model.Datasource.Database
 * @since         CakePHP(tm) v 0.9.1.114
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Model\Datasource\Database;

use Cake\Error\MissingConnectionException;
use Cake\Model\Datasource\DboSource;
use Cake\Model\Model;
use Cake\Utility\Hash;
use PDO;
use PDOException;
use PDOStatement;

/**
 * PostgreSQL layer for DBO.
 *
 * @package       Cake.Model.Datasource.Database
 */
class Postgres extends DboSource
{
    /**
     * Driver description
     *
     * @var string
     */
    public string $description = 'PostgreSQL DBO Driver';

    /**
     * Base driver configuration settings. Merged with user settings.
     *
     * @var array
     */
    protected array $_baseConfig = [
        'persistent' => true,
        'host' => 'localhost',
        'login' => 'root',
        'password' => '',
        'database' => 'cake',
        'schema' => 'public',
        'port' => 5432,
        'encoding' => '',
        'flags' => [],
    ];

    /**
     * Columns
     *
     * @var array
     * @link https://www.postgresql.org/docs/9.6/static/datatype.html PostgreSQL Data Types
     */
    public array $columns = [
        'primary_key' => ['name' => 'serial NOT NULL'],
        'string' => ['name' => 'varchar', 'limit' => '255'],
        'text' => ['name' => 'text'],
        'integer' => ['name' => 'integer', 'formatter' => 'intval'],
        'smallinteger' => ['name' => 'smallint', 'formatter' => 'intval'],
        'tinyinteger' => ['name' => 'smallint', 'formatter' => 'intval'],
        'biginteger' => ['name' => 'bigint', 'limit' => '20'],
        'float' => ['name' => 'float', 'formatter' => 'floatval'],
        'decimal' => ['name' => 'decimal', 'formatter' => 'floatval'],
        'datetime' => ['name' => 'timestamp', 'format' => 'Y-m-d H:i:s', 'formatter' => 'date'],
        'timestamp' => ['name' => 'timestamp', 'format' => 'Y-m-d H:i:s', 'formatter' => 'date'],
        'time' => ['name' => 'time', 'format' => 'H:i:s', 'formatter' => 'date'],
        'date' => ['name' => 'date', 'format' => 'Y-m-d', 'formatter' => 'date'],
        'binary' => ['name' => 'bytea'],
        'boolean' => ['name' => 'boolean'],
        'number' => ['name' => 'numeric'],
        'inet' => ['name' => 'inet'],
        'uuid' => ['name' => 'uuid'],
    ];

    /**
     * Starting Quote
     *
     * @var string|null
     */
    public ?string $startQuote = '"';

    /**
     * Ending Quote
     *
     * @var string|null
     */
    public ?string $endQuote = '"';

    /**
     * Contains mappings of custom auto-increment sequences, if a table uses a sequence name
     * other than what is dictated by convention.
     *
     * @var array
     */
    protected array $_sequenceMap = [];

    /**
     * The set of valid SQL operations usable in a WHERE statement
     *
     * @var array<string>
     */
    protected array $_sqlOps = [
        'like',
        'ilike',
        'or',
        'not',
        'in',
        'between',
        '~',
        '~\*',
        '\!~',
        '\!~\*',
        'similar to',
    ];

    /**
     * Connects to the database using options in the given configuration array.
     *
     * @return bool True if successfully connected.
     * @throws MissingConnectionException
     */
    public function connect(): bool
    {
        $config = $this->config;
        $this->connected = false;

        $flags = $config['flags'] + [
            PDO::ATTR_PERSISTENT => $config['persistent'],
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ];

        $dsn = "pgsql:host={$config['host']};port={$config['port']};dbname={$config['database']}";
        if (!empty($config['sslmode'])) {
            $dsn .= ";sslmode={$config['sslmode']}";
        }
        if (!empty($config['encoding'])) {
            $dsn .= ";options='--client_encoding={$config['encoding']}'";
        }

        try {
            $this->_connection = new PDO(
                $dsn,
                $config['login'],
                $config['password'],
                $flags,
            );

            $this->connected = true;
            if (!empty($config['schema'])) {
                $this->_execute('SET search_path TO "' . $config['schema'] . '"');
            }
            if (!empty($config['settings'])) {
                foreach ($config['settings'] as $key => $value) {
                    $this->_execute("SET $key TO $value");
                }
            }
        } catch (PDOException $e) {
            throw new MissingConnectionException([
                'class' => static::class,
                'message' => $e->getMessage(),
            ]);
        }

        return $this->connected;
    }

    /**
     * Check if PostgreSQL is enabled/loaded
     *
     * @return bool
     */
    public function enabled(): bool
    {
        return in_array('pgsql', PDO::getAvailableDrivers());
    }

    /**
     * Returns an array of tables in the database. If there are no tables, an error is raised and the application exits.
     *
     * @param array|null $data The sources to list.
     * @return array|null Array of table names in the database
     */
    public function listSources(?array $data = null): ?array
    {
        $cache = parent::listSources();

        if ($cache) {
            return $cache;
        }

        $schema = $this->config['schema'];
        $sql = 'SELECT table_name as name FROM INFORMATION_SCHEMA.tables WHERE table_schema = ?';
        $result = $this->_execute($sql, [$schema]);

        if (!$result) {
            return [];
        }

        $tables = [];

        foreach ($result as $item) {
            $tables[] = $item->name;
        }

        $result->closeCursor();
        parent::listSources($tables);

        return $tables;
    }

    /**
     * Returns an array of the fields in given table name.
     *
     * @param Model|string $model Name of database table to inspect
     * @return array|false|null Fields in table. Keys are name and type
     */
    public function describe(Model|string $model): array|false|null
    {
        $table = $this->fullTableName($model, false, false);
        $fields = parent::describe($table);
        $this->_sequenceMap[$table] = [];
        $cols = null;
        $hasPrimary = false;

        if ($fields === null) {
            $cols = $this->_execute(
                'SELECT DISTINCT table_schema AS schema,
					column_name AS name,
					data_type AS type,
					is_nullable AS null,
					column_default AS default,
					ordinal_position AS position,
					character_maximum_length AS char_length,
					character_octet_length AS oct_length,
					pg_get_serial_sequence(attr.attrelid::regclass::text, attr.attname) IS NOT NULL AS has_serial
				FROM information_schema.columns c
				INNER JOIN pg_catalog.pg_namespace ns ON (ns.nspname = table_schema)
				INNER JOIN pg_catalog.pg_class cl ON (cl.relnamespace = ns.oid AND cl.relname = table_name)
				LEFT JOIN pg_catalog.pg_attribute attr ON (cl.oid = attr.attrelid AND column_name = attr.attname)
				WHERE table_name = ? AND table_schema = ? AND table_catalog = ?
				ORDER BY ordinal_position',
                [$table, $this->config['schema'], $this->config['database']],
            );

            // Postgres columns don't match the coding standards.
            foreach ($cols as $c) {
                $type = $c->type;
                if (!empty($c->oct_length) && $c->char_length === null) {
                    if ($c->type === 'character varying') {
                        $length = null;
                        $type = 'text';
                    } elseif ($c->type === 'uuid') {
                        $type = 'uuid';
                        $length = 36;
                    } else {
                        $length = (int)$c->oct_length;
                    }
                } elseif (!empty($c->char_length)) {
                    $length = (int)$c->char_length;
                } else {
                    $length = $this->length($c->type);
                }
                if (empty($length)) {
                    $length = null;
                }
                $fields[$c->name] = [
                    'type' => $this->column($type),
                    'null' => $c->null !== 'NO',
                    'default' => $c->default ? preg_replace(
                        "/^'(.*)'$/",
                        '$1',
                        preg_replace('/::[\w\s]+/', '', $c->default),
                    ) : null,
                    'length' => $length,
                ];

                // Serial columns are primary integer keys
                if ($c->has_serial) {
                    $fields[$c->name]['key'] = 'primary';
                    $fields[$c->name]['length'] = 11;
                    $hasPrimary = true;
                }
                if (
                    $hasPrimary === false &&
                    $model instanceof Model &&
                    $c->name === $model->primaryKey
                ) {
                    $fields[$c->name]['key'] = 'primary';
                    if (
                        $fields[$c->name]['type'] !== 'string' &&
                        $fields[$c->name]['type'] !== 'uuid'
                    ) {
                        $fields[$c->name]['length'] = 11;
                    }
                }
                if (
                    $fields[$c->name]['default'] === 'NULL' ||
                    $c->default === null ||
                    preg_match('/nextval\([\'"]?([\w.]+)/', $c->default, $seq)
                ) {
                    $fields[$c->name]['default'] = null;
                    if (!empty($seq) && isset($seq[1])) {
                        if (!str_contains($seq[1], '.')) {
                            $sequenceName = $c->schema . '.' . $seq[1];
                        } else {
                            $sequenceName = $seq[1];
                        }
                        $this->_sequenceMap[$table][$c->name] = $sequenceName;
                    }
                }
                if ($fields[$c->name]['type'] === 'timestamp' && $fields[$c->name]['default'] === '') {
                    $fields[$c->name]['default'] = null;
                }
                if ($fields[$c->name]['type'] === 'boolean' && !empty($fields[$c->name]['default'])) {
                    $fields[$c->name]['default'] = constant($fields[$c->name]['default']);
                }
            }
            $this->_cacheDescription($table, $fields);
        }

        if (isset($model->sequence)) {
            $this->_sequenceMap[$table][$model->primaryKey] = $model->sequence;
        }

        if ($cols) {
            $cols->closeCursor();
        }

        return $fields;
    }

    /**
     * Returns the ID generated from the previous INSERT operation.
     *
     * @param mixed $source Name of the database table
     * @param string $field Name of the ID database field. Defaults to "id"
     * @return string|bool
     */
    public function lastInsertId(
        mixed $source = null,
        string $field = 'id',
    ): string|bool {
        $seq = $this->getSequence($source, $field);

        return $this->_connection->lastInsertId($seq);
    }

    /**
     * Gets the associated sequence for the given table/field
     *
     * @param Model|string $table Either a full table name (with prefix) as a string, or a model object
     * @param string $field Name of the ID database field. Defaults to "id"
     * @return string The associated sequence name from the sequence map, defaults to "{$table}_{$field}_seq"
     */
    public function getSequence(Model|string $table, string $field = 'id'): string
    {
        if (is_object($table)) {
            $table = $this->fullTableName($table, false, false);
        }
        if (!isset($this->_sequenceMap[$table])) {
            $this->describe($table);
        }

        return $this->_sequenceMap[$table][$field] ?? "{$table}_{$field}_seq";
    }

    /**
     * Reset a sequence based on the MAX() value of $column. Useful
     * for resetting sequences after using insertMulti().
     *
     * @param string $table The name of the table to update.
     * @param string $column The column to use when resetting the sequence value,
     *   the sequence name will be fetched using Postgres::getSequence();
     * @return bool success.
     */
    public function resetSequence(string $table, string $column): bool
    {
        $tableName = $this->fullTableName($table, false, false);
        $fullTable = $this->fullTableName($table);

        $sequence = $this->value($this->getSequence($tableName, $column));
        $column = $this->name($column);
        $this->execute("SELECT setval($sequence, (SELECT MAX($column) FROM $fullTable))");

        return true;
    }

    /**
     * Deletes all the records in a table and drops all associated auto-increment sequences
     *
     * @param Model|string $table A string or model class representing the table to be truncated
     * @param bool $reset true for resetting the sequence, false to leave it as is.
     *    and if 1, sequences are not modified
     * @return PDOStatement|bool|null SQL TRUNCATE TABLE statement, false if not applicable.
     */
    public function truncate(
        Model|string $table,
        bool $reset = false,
    ): PDOStatement|bool|null {
        $table = $this->fullTableName($table, false, false);
        if (!isset($this->_sequenceMap[$table])) {
            $cache = $this->cacheSources;
            $this->cacheSources = false;
            $this->describe($table);
            $this->cacheSources = $cache;
        }
        if ($this->execute('DELETE FROM ' . $this->fullTableName($table))) {
            if (isset($this->_sequenceMap[$table]) && !$reset) {
                foreach ($this->_sequenceMap[$table] as $sequence) {
                    $quoted = $this->name($sequence);
                    $this->_execute("ALTER SEQUENCE {$quoted} RESTART WITH 1");
                }
            }

            return true;
        }

        return false;
    }

    /**
     * Prepares field names to be quoted by parent
     *
     * @param mixed $data The name to format.
     * @return array|string SQL field
     */
    public function name(mixed $data): array|string
    {
        if (is_string($data)) {
            $data = str_replace('"__"', '__', $data);
        }

        return parent::name($data);
    }

    /**
     * Generates the fields list of an SQL query.
     *
     * @param Model $model The model to get fields for.
     * @param string|null $alias Alias table name.
     * @param mixed $fields The list of fields to get.
     * @param bool $quote Whether or not to quote identifiers.
     * @return array
     */
    public function fields(
        Model $model,
        ?string $alias = null,
        mixed $fields = [],
        bool $quote = true,
    ): array {
        if (empty($alias)) {
            $alias = $model->alias;
        }
        $fields = parent::fields($model, $alias, $fields, false);

        if (!$quote) {
            return $fields;
        }
        $count = count($fields);

        if ($count >= 1 && !preg_match('/^\s*COUNT\(\*/', $fields[0])) {
            $result = [];
            for ($i = 0; $i < $count; $i++) {
                if (!preg_match('/^.+\\(.*\\)/', $fields[$i]) && !preg_match('/\s+AS\s+/', $fields[$i])) {
                    if (str_ends_with($fields[$i], '*')) {
                        if (str_contains($fields[$i], '.') && $fields[$i] != $alias . '.*') {
                            $build = explode('.', $fields[$i]);
                            $AssociatedModel = $model->{$build[0]};
                        } else {
                            $AssociatedModel = $model;
                        }

                        $_fields = $this->fields($AssociatedModel, $AssociatedModel->alias, array_keys($AssociatedModel->schema()));
                        $result = array_merge($result, $_fields);
                        continue;
                    }

                    $prepend = '';
                    if (str_contains($fields[$i], 'DISTINCT')) {
                        $prepend = 'DISTINCT ';
                        $fields[$i] = trim(str_replace('DISTINCT', '', $fields[$i]));
                    }

                    if (strrpos($fields[$i], '.') === false) {
                        $fields[$i] = $prepend . $this->name($alias) . '.' . $this->name($fields[$i]) . ' AS ' . $this->name($alias . '__' . $fields[$i]);
                    } else {
                        $build = explode('.', $fields[$i]);
                        $fields[$i] = $prepend . $this->name($build[0]) . '.' . $this->name($build[1]) . ' AS ' . $this->name($build[0] . '__' . $build[1]);
                    }
                } else {
                    $fields[$i] = preg_replace_callback('/\(([\s\.\w]+)\)/', [&$this, '_quoteFunctionField'], $fields[$i]);
                }
                $result[] = $fields[$i];
            }

            return $result;
        }

        return $fields;
    }

    /**
     * Auxiliary function to quote matched `(Model.fields)` from a preg_replace_callback call
     * Quotes the fields in a function call.
     *
     * @param array $match matched string
     * @return string quoted string
     */
    protected function _quoteFunctionField(array $match): string
    {
        $prepend = '';
        if (str_contains($match[1], 'DISTINCT')) {
            $prepend = 'DISTINCT ';
            $match[1] = trim(str_replace('DISTINCT', '', $match[1]));
        }
        $constant = preg_match('/^\d+|NULL|FALSE|TRUE$/i', $match[1]);

        if (!$constant && !str_contains($match[1], '.')) {
            $match[1] = $this->name($match[1]);
        } elseif (!$constant) {
            $parts = explode('.', $match[1]);
            if (!Hash::numeric($parts)) {
                $match[1] = $this->name($match[1]);
            }
        }

        return '(' . $prepend . $match[1] . ')';
    }

    /**
     * Returns an array of the indexes in given datasource name.
     *
     * @param Model|string $model Name of model to inspect
     * @return array Fields in table. Keys are column and unique
     */
    public function index(Model|string $model): array
    {
        $index = [];
        $table = $this->fullTableName($model, false, false);
        if ($table) {
            $indexes = $this->query("SELECT c2.relname, i.indisprimary, i.indisunique, i.indisclustered, i.indisvalid, pg_catalog.pg_get_indexdef(i.indexrelid, 0, true) as statement, c2.reltablespace
			FROM pg_catalog.pg_class c, pg_catalog.pg_class c2, pg_catalog.pg_index i
			WHERE c.oid  = (
				SELECT c.oid
				FROM pg_catalog.pg_class c LEFT JOIN pg_catalog.pg_namespace n ON n.oid = c.relnamespace
				WHERE c.relname ~ '^(" . $table . ")$'
					AND pg_catalog.pg_table_is_visible(c.oid)
					AND n.nspname ~ '^(" . $this->config['schema'] . ")$'
			)
			AND c.oid = i.indrelid AND i.indexrelid = c2.oid
			ORDER BY i.indisprimary DESC, i.indisunique DESC, c2.relname", false);
            foreach ($indexes as $info) {
                $key = array_pop($info);
                if ($key['indisprimary']) {
                    $key['relname'] = 'PRIMARY';
                }
                preg_match('/\(([^\)]+)\)/', $key['statement'], $indexColumns);
                $parsedColumn = $indexColumns[1];
                if (str_contains($indexColumns[1], ',')) {
                    $parsedColumn = explode(', ', $indexColumns[1]);
                }
                $index[$key['relname']]['unique'] = $key['indisunique'];
                $index[$key['relname']]['column'] = $parsedColumn;
            }
        }

        return $index;
    }

    /**
     * Alter the Schema of a table.
     *
     * @param mixed $compare Results of CakeSchema::compare()
     * @param string|null $table name of the table
     * @return string|false
     */
    public function alterSchema(mixed $compare, ?string $table = null): string|false
    {
        if (!is_array($compare)) {
            return false;
        }

        $out = '';
        foreach ($compare as $curTable => $types) {
            $indexes = $colList = [];
            if (!$table || $table === $curTable) {
                $out .= 'ALTER TABLE ' . $this->fullTableName($curTable) . " \n";
                foreach ($types as $type => $column) {
                    if (isset($column['indexes'])) {
                        $indexes[$type] = $column['indexes'];
                        unset($column['indexes']);
                    }
                    switch ($type) {
                        case 'add':
                            foreach ($column as $field => $col) {
                                $col['name'] = $field;
                                $colList[] = 'ADD COLUMN ' . $this->buildColumn($col);
                            }
                            break;
                        case 'drop':
                            foreach ($column as $field => $col) {
                                $col['name'] = $field;
                                $colList[] = 'DROP COLUMN ' . $this->name($field);
                            }
                            break;
                        case 'change':
                            $schema = $this->describe($curTable);
                            foreach ($column as $field => $col) {
                                if (!isset($col['name'])) {
                                    $col['name'] = $field;
                                }

                                // Check if field exists in schema
                                $original = $schema[$field] ?? null;
                                $fieldName = $this->name($field);

                                $default = $col['default'] ?? null;
                                $nullable = $col['null'] ?? null;

                                // Only perform type conversion checks if original field exists
                                $boolToInt = false;
                                $textToInt = false;
                                if ($original !== null) {
                                    $boolToInt = $original['type'] === 'boolean' && $col['type'] === 'integer';
                                    $textToInt = $original['type'] === 'text' && $col['type'] === 'integer';
                                }

                                unset($col['default'], $col['null']);
                                if ($field !== $col['name']) {
                                    $newName = $this->name($col['name']);
                                    $out .= "\tRENAME {$fieldName} TO {$newName};\n";
                                    $out .= 'ALTER TABLE ' . $this->fullTableName($curTable) . " \n";
                                    $fieldName = $newName;
                                }

                                if ($boolToInt) {
                                    $colList[] = 'ALTER COLUMN ' . $fieldName . '  SET DEFAULT NULL';
                                    $colList[] = 'ALTER COLUMN ' . $fieldName . ' TYPE ' . str_replace([$fieldName, 'NOT NULL'], '', $this->buildColumn($col)) . ' USING CASE WHEN TRUE THEN 1 ELSE 0 END';
                                } else {
                                    if ($textToInt) {
                                        $colList[] = 'ALTER COLUMN ' . $fieldName . ' TYPE ' . str_replace([$fieldName, 'NOT NULL'], '', $this->buildColumn($col)) . " USING cast({$fieldName} as INTEGER)";
                                    } else {
                                        $colList[] = 'ALTER COLUMN ' . $fieldName . ' TYPE ' . str_replace([$fieldName, 'NOT NULL'], '', $this->buildColumn($col));
                                    }
                                }

                                if (isset($nullable)) {
                                    $nullable = $nullable ? 'DROP NOT NULL' : 'SET NOT NULL';
                                    $colList[] = 'ALTER COLUMN ' . $fieldName . '  ' . $nullable;
                                }

                                if (isset($default)) {
                                    if (!$boolToInt) {
                                        $colList[] = 'ALTER COLUMN ' . $fieldName . '  SET DEFAULT ' . $this->value($default, $col['type']);
                                    }
                                } else {
                                    $colList[] = 'ALTER COLUMN ' . $fieldName . '  DROP DEFAULT';
                                }
                            }
                            break;
                    }
                }
                if (isset($indexes['drop']['PRIMARY'])) {
                    $colList[] = 'DROP CONSTRAINT ' . $curTable . '_pkey';
                }
                if (isset($indexes['add']['PRIMARY'])) {
                    $cols = $indexes['add']['PRIMARY']['column'];
                    if (is_array($cols)) {
                        $cols = implode(', ', $cols);
                    }
                    $colList[] = 'ADD PRIMARY KEY (' . $cols . ')';
                }

                if (!empty($colList)) {
                    $out .= "\t" . implode(",\n\t", $colList) . ";\n\n";
                } else {
                    $out = '';
                }
                $out .= implode(";\n\t", $this->_alterIndexes($curTable, $indexes));
            }
        }

        return $out;
    }

    /**
     * Generate PostgreSQL index alteration statements for a table.
     *
     * @param string $table Table to alter indexes for
     * @param array $indexes Indexes to add and drop
     * @return array Index alteration statements
     */
    protected function _alterIndexes(string $table, array $indexes): array
    {
        $alter = [];
        if (isset($indexes['drop'])) {
            foreach ($indexes['drop'] as $name => $value) {
                $out = 'DROP ';
                if ($name === 'PRIMARY') {
                    continue;
                } else {
                    $out .= 'INDEX ' . $name;
                }
                $alter[] = $out;
            }
        }
        if (isset($indexes['add'])) {
            foreach ($indexes['add'] as $name => $value) {
                $out = 'CREATE ';
                if ($name === 'PRIMARY') {
                    continue;
                } else {
                    if (!empty($value['unique'])) {
                        $out .= 'UNIQUE ';
                    }
                    $out .= 'INDEX ';
                }
                if (is_array($value['column'])) {
                    /** @var array<string> $_column */
                    $_column = array_map([&$this, 'name'], $value['column']);
                    $out .= $name . ' ON ' . $table . ' (' . implode(', ', $_column) . ')';
                } else {
                    $out .= $name . ' ON ' . $table . ' (' . $this->name($value['column']) . ')';
                }
                $alter[] = $out;
            }
        }

        return $alter;
    }

    /**
     * Returns a limit statement in the correct format for the particular database.
     *
     * @param array|string|int|null $limit Limit of results returned
     * @param array|string|int|null $offset Offset from which to start results
     * @return string|null SQL limit/offset statement
     */
    public function limit(
        array|string|int|null $limit,
        array|string|int|null $offset = null,
    ): ?string {
        if ($limit) {
            // Suppress PHP 8.5+ warning for backward compatibility with existing limit/offset behavior
            // The sprintf %u format behavior is undefined for values outside int range, but must remain
            // consistent with previous PHP versions for query generation
            set_error_handler(function () {
                return true;
            }, E_WARNING);
            $rt = sprintf(' LIMIT %u', $limit);
            if ($offset) {
                $rt .= sprintf(' OFFSET %u', $offset);
            }
            restore_error_handler();

            return $rt;
        }

        return null;
    }

    /**
     * Converts database-layer column types to basic types
     *
     * @param mixed $real Real database-layer column type (i.e. "varchar(255)")
     * @return string|false Abstract column type (i.e. "string")
     */
    public function column(mixed $real): string|false
    {
        if (is_array($real)) {
            $col = $real['name'];
            if (isset($real['limit'])) {
                $col .= '(' . $real['limit'] . ')';
            }

            return $col;
        }

        $col = str_replace(')', '', $real);

        if (str_contains($col, '(')) {
            [$col,] = explode('(', $col);
        }

        $floats = [
            'float', 'float4', 'float8', 'double', 'double precision', 'real',
        ];

        return match (true) {
            in_array($col, ['date', 'time', 'inet', 'boolean']) => $col,
            str_contains($col, 'timestamp') => 'datetime',
            str_starts_with($col, 'time') => 'time',
            $col === 'bigint' => 'biginteger',
            $col === 'smallint' => 'smallinteger',
            str_contains($col, 'int') && $col !== 'interval' => 'integer',
            str_contains($col, 'char') => 'string',
            str_contains($col, 'uuid') => 'uuid',
            str_contains($col, 'text') => 'text',
            str_contains($col, 'bytea') => 'binary',
            $col === 'decimal' || $col === 'numeric' => 'decimal',
            in_array($col, $floats) => 'float',
            default => 'text',
        };
    }

    /**
     * Gets the length of a database-native column description, or null if no length
     *
     * @param object|string $real Real database-layer column type (i.e. "varchar(255)")
     * @return string|int|null An integer representing the length of the column
     */
    public function length(object|string $real): string|int|null
    {
        $col = $real;
        if (str_contains($real, '(')) {
            [$col,] = explode('(', $real);
        }
        if ($col === 'uuid') {
            return 36;
        }

        return parent::length($real);
    }

    /**
     * resultSet method
     *
     * @param PDOStatement $results The results
     * @return void
     */
    public function resultSet(PDOStatement $results): void
    {
        $this->map = [];
        $numFields = $results->columnCount();
        $index = 0;
        $j = 0;

        while ($j < $numFields) {
            $column = $results->getColumnMeta($j);
            if (strpos($column['name'], '__')) {
                [$table, $name] = explode('__', $column['name']);
                $this->map[$index++] = [$table, $name, $column['native_type']];
            } else {
                $this->map[$index++] = [0, $column['name'], $column['native_type']];
            }
            $j++;
        }
    }

    /**
     * Fetches the next row from the current result set
     *
     * @return array|false
     */
    public function fetchResult(): array|false
    {
        if ($row = $this->_result->fetch(PDO::FETCH_NUM)) {
            $resultRow = [];

            foreach ($this->map as $index => $meta) {
                [$table, $column, $type] = $meta;

                $resultRow[$table][$column] = match ($type) {
                    'bool' => $row[$index] === null ? null : $this->boolean($row[$index]),
                    'binary', 'bytea' => $row[$index] === null ? null : stream_get_contents($row[$index]),
                    default => $row[$index],
                };
            }

            return $resultRow;
        }
        $this->_result->closeCursor();

        return false;
    }

    /**
     * Translates between PHP boolean values and PostgreSQL boolean values
     *
     * @param mixed $data Value to be translated
     * @param bool $quote true to quote a boolean to be used in a query, false to return the boolean value
     * @return string|bool Converted boolean value
     */
    public function boolean(mixed $data, bool $quote = false): string|bool
    {
        $result = match (true) {
            $data === true || $data === false => $data,
            $data === 't' || $data === 'f' => ($data === 't'),
            $data === 'true' || $data === 'false' => ($data === 'true'),
            $data === 'TRUE' || $data === 'FALSE' => ($data === 'TRUE'),
            default => (bool)$data,
        };

        if ($quote) {
            return $result ? 'TRUE' : 'FALSE';
        }

        return $result;
    }

    /**
     * Sets the database encoding
     *
     * @param mixed $enc Database encoding
     * @return bool True on success, false on failure
     */
    public function setEncoding(mixed $enc): bool
    {
        return $this->_execute('SET NAMES ' . $this->value($enc)) !== false;
    }

    /**
     * Gets the database encoding
     *
     * @return string|false The database encoding
     */
    public function getEncoding(): string|false
    {
        $result = $this->_execute('SHOW client_encoding')->fetch();
        if ($result === false) {
            return false;
        }

        return $result['client_encoding'] ?? false;
    }

    /**
     * Generate a Postgres-native column schema string
     *
     * @param array $column An array structured like the following:
     *                      array('name'=>'value', 'type'=>'value'[, options]),
     *                      where options can be 'default', 'length', or 'key'.
     * @return string|null
     */
    public function buildColumn(array $column): ?string
    {
        $col = $this->columns[$column['type']];
        if (!isset($col['length']) && !isset($col['limit'])) {
            unset($column['length']);
        }
        $out = parent::buildColumn($column);

        $out = preg_replace(
            '/integer\([0-9]+\)/',
            'integer',
            $out,
        );
        $out = preg_replace(
            '/bigint\([0-9]+\)/',
            'bigint',
            $out,
        );

        $out = str_replace('integer serial', 'serial', $out);
        $out = str_replace('bigint serial', 'bigserial', $out);
        if (strpos($out, 'timestamp DEFAULT')) {
            if (isset($column['null']) && $column['null']) {
                $out = str_replace('DEFAULT NULL', '', $out);
            } else {
                $out = str_replace('DEFAULT NOT NULL', '', $out);
            }
        }
        if (strpos($out, 'DEFAULT DEFAULT')) {
            if (isset($column['null']) && $column['null']) {
                $out = str_replace('DEFAULT DEFAULT', 'DEFAULT NULL', $out);
            } elseif (in_array($column['type'], ['integer', 'float'])) {
                $out = str_replace('DEFAULT DEFAULT', 'DEFAULT 0', $out);
            } elseif ($column['type'] === 'boolean') {
                $out = str_replace('DEFAULT DEFAULT', 'DEFAULT FALSE', $out);
            }
        }

        return $out;
    }

    /**
     * Format indexes for create table
     *
     * @param array $indexes The index to build
     * @param string|null $table The table name.
     * @return array<string>
     */
    public function buildIndex(array $indexes, ?string $table = null): array
    {
        $join = [];
        foreach ($indexes as $name => $value) {
            if ($name === 'PRIMARY') {
                $out = 'PRIMARY KEY  (' . $this->name($value['column']) . ')';
            } else {
                $out = 'CREATE ';
                if (!empty($value['unique'])) {
                    $out .= 'UNIQUE ';
                }
                if (is_array($value['column'])) {
                    /** @var array<string> $_column */
                    $_column = array_map([&$this, 'name'], $value['column']);
                    $value['column'] = implode(', ', $_column);
                } else {
                    $value['column'] = $this->name($value['column']);
                }
                $out .= "INDEX {$name} ON {$table}({$value['column']});";
            }
            $join[] = $out;
        }

        return $join;
    }

    /**
     * @inheritDoc
     */
    public function value(mixed $data, ?string $column = null, bool $null = true): array|string
    {
        $value = parent::value($data, $column, $null);
        if ($column === 'uuid' && $data === '') {
            return 'NULL';
        }

        return $value;
    }

    /**
     * Overrides DboSource::renderStatement to handle schema generation with Postgres-style indexes
     *
     * @param string $type The query type.
     * @param array{
     *     fields: string|null,
     *     table: string|null,
     *     alias: string|null,
     *     joins?: string|null,
     *     conditions?: string|null,
     *     group?: string|null,
     *     having?: string|null,
     *     order?: string|null,
     *     limit?: string|null,
     *     lock?: string|null
     * }|array{
     *     fields: string|null,
     *     table: string|null,
     *     values?: string|null
     * }|array{
     *     fields: string|null,
     *     table: string|null,
     *     alias: string|null,
     *     joins?: string|null,
     *     conditions?: string|null
     * }|array{
     *     table: string|null,
     *     columns?: mixed,
     *     indexes?: mixed,
     *     tableParameters?: mixed
     * } $data $data The array of data to render.
     * @return string|null
     */
    public function renderStatement(string $type, array $data): ?string
    {
        switch (strtolower($type)) {
            case 'schema':
                $table = $data['table'] ?? null;
                $columns = $data['columns'] ?? [];
                $indexes = $data['indexes'] ?? [];

                foreach ($indexes as $i => $index) {
                    if (str_contains($index, 'PRIMARY KEY')) {
                        unset($indexes[$i]);
                        $columns[] = $index;
                        break;
                    }
                }
                $join = ['columns' => ",\n\t", 'indexes' => "\n"];

                foreach (['columns', 'indexes'] as $var) {
                    if (is_array(${$var})) {
                        ${$var} = implode($join[$var], array_filter(${$var}));
                    }
                }

                return "CREATE TABLE {$table} (\n\t{$columns}\n);\n{$indexes}";
            default:
                return parent::renderStatement($type, $data);
        }
    }

    /**
     * Gets the schema name
     *
     * @return string The schema name
     */
    public function getSchemaName(): string
    {
        return $this->config['schema'];
    }

    /**
     * Check if the server support nested transactions
     *
     * @return bool
     */
    public function nestedTransactionSupported(): bool
    {
        return $this->useNestedTransactions && version_compare($this->getVersion(), '8.0', '>=');
    }

    /**
     * Returns connected server version.
     *
     * @return string Numeric version string (e.g., "15.3", "14.8")
     */
    public function getVersion(): string
    {
        if ($this->_version === null) {
            $version = (string)$this->_connection->getAttribute(PDO::ATTR_SERVER_VERSION);

            // Extract numeric version from PostgreSQL version string
            // Examples:
            // "15.3" -> "15.3"
            // "14.8 (Ubuntu 14.8-0ubuntu0.22.04.1)" -> "14.8"
            // "13.11 on x86_64-pc-linux-gnu" -> "13.11"
            if (preg_match('/^(\d+\.\d+(?:\.\d+)?)/', $version, $matches)) {
                $this->_version = $matches[1];
            } else {
                $this->_version = $version;
            }
        }

        return $this->_version;
    }
}
