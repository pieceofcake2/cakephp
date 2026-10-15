<?php
/**
 * Datasource connection manager
 *
 * Provides an interface for loading and enumerating connections defined in app/Config/database.php
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @package       Cake.Model
 * @since         CakePHP(tm) v 0.10.x.1402
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

/**
 * Manages loaded instances of DataSource objects
 *
 * Provides an interface for loading and enumerating connections defined in
 * app/Config/database.php
 *
 * A connection configuration may set its connection with a DSN in the `'dsn'`
 * key, for example `'dsn' => env('DATABASE_URL')`. See `parseDsn()` and
 * `setDsnClassMap()`.
 *
 * @package       Cake.Model
 */
class ConnectionManager
{
    /**
     * The DSN class map: datasources (or callables) for the DSN schemes
     * understood by `parseDsn()`.
     *
     * @var array<string, mixed>
     */
    protected static $_dsnClassMap = [
        'mysql' => 'Database/Mysql',
        'postgres' => 'Database/Postgres',
        'postgresql' => 'Database/Postgres',
        'pgsql' => 'Database/Postgres',
        'sqlite' => 'Database/Sqlite',
        'sqlserver' => 'Database/Sqlserver',
        'mssql' => 'Database/Sqlserver',
        'sqlsrv' => 'Database/Sqlserver',
    ];

    /**
     * Holds a loaded instance of the Connections object
     *
     * @var DATABASE_CONFIG
     */
    public static $config = null;

    /**
     * Holds instances DataSource objects
     *
     * @var array
     */
    protected static $_dataSources = [];

    /**
     * Contains a list of all file and class names used in Connection settings
     *
     * @var array
     */
    protected static $_connectionsEnum = [];

    /**
     * Indicates if the init code for this class has already been executed
     *
     * @var bool
     */
    protected static $_init = false;

    /**
     * Loads connections configuration.
     *
     * @return void
     */
    protected static function _init()
    {
        try {
            require_once CONFIG . 'database.php';
        } catch (Throwable $e) {
        }
        if (class_exists(DATABASE_CONFIG::class)) {
            static::$config = new DATABASE_CONFIG();
        }
        static::$_init = true;
    }

    /**
     * Gets a reference to a DataSource object
     *
     * @param string $name The name of the DataSource, as defined in app/Config/database.php
     * @return DataSource Instance
     * @throws MissingDatasourceException
     */
    public static function getDataSource($name)
    {
        if (empty(static::$_init)) {
            static::_init();
        }

        if (!empty(static::$_dataSources[$name])) {
            return static::$_dataSources[$name];
        }

        if (empty(static::$_connectionsEnum[$name])) {
            static::_getConnectionObject($name);
        }

        static::loadDataSource($name);
        $conn = static::$_connectionsEnum[$name];
        $class = $conn['classname'];

        if (!class_exists($class) && !str_contains(App::location($class), 'Datasource')) {
            throw new MissingDatasourceException([
                'class' => $class,
                'plugin' => null,
                'message' => 'Datasource is not found in Model/Datasource package.',
            ]);
        }
        static::$_dataSources[$name] = new $class(static::$config->{$name});
        static::$_dataSources[$name]->configKeyName = $name;

        return static::$_dataSources[$name];
    }

    /**
     * Gets the list of available DataSource connections
     * This will only return the datasources instantiated by this manager
     * It differs from enumConnectionObjects, since the latter will return all configured connections
     *
     * @return array List of available connections
     */
    public static function sourceList()
    {
        if (empty(static::$_init)) {
            static::_init();
        }

        return array_keys(static::$_dataSources);
    }

    /**
     * Gets a DataSource name from an object reference.
     *
     * @param DataSource $source DataSource object
     * @return string|null Datasource name, or null if source is not present
     *    in the ConnectionManager.
     */
    public static function getSourceName($source)
    {
        if (empty(static::$_init)) {
            static::_init();
        }
        foreach (static::$_dataSources as $name => $ds) {
            if ($ds === $source) {
                return $name;
            }
        }

        return null;
    }

    /**
     * Loads the DataSource class for the given connection name
     *
     * @param array|string $connName A string name of the connection, as defined in app/Config/database.php,
     *    or an array containing the filename (without extension) and class name of the object,
     *    to be found in app/Model/Datasource/ or src/Cake/Model/Datasource/.
     * @return bool True on success, null on failure or false if the class is already loaded
     * @throws MissingDatasourceException
     */
    public static function loadDataSource($connName)
    {
        if (empty(static::$_init)) {
            static::_init();
        }

        if (is_array($connName)) {
            $conn = $connName;
        } else {
            $conn = static::$_connectionsEnum[$connName];
        }

        if (class_exists($conn['classname'], false)) {
            return false;
        }

        $plugin = $package = null;
        if (!empty($conn['plugin'])) {
            $plugin = $conn['plugin'] . '.';
        }
        if (!empty($conn['package'])) {
            $package = '/' . $conn['package'];
        }

        App::uses($conn['classname'], $plugin . 'Model/Datasource' . $package);
        if (!class_exists($conn['classname'])) {
            throw new MissingDatasourceException([
                'class' => $conn['classname'],
                'plugin' => substr($plugin, 0, -1),
            ]);
        }

        return true;
    }

    /**
     * Returns a list of connections
     *
     * @return array An associative array of elements where the key is the connection name
     *    (as defined in Connections), and the value is an array with keys 'filename' and 'classname'.
     */
    public static function enumConnectionObjects()
    {
        if (empty(static::$_init)) {
            static::_init();
        }

        return (array)static::$config;
    }

    /**
     * Dynamically creates a DataSource object at runtime, with the given name and settings
     *
     * @param string $name The DataSource name
     * @param array $config The DataSource configuration settings
     * @return DataSource|null A reference to the DataSource object, or null if creation failed
     */
    public static function create($name = '', $config = [])
    {
        if (empty(static::$_init)) {
            static::_init();
        }

        if (empty($name) || empty($config) || array_key_exists($name, static::$_connectionsEnum)) {
            return null;
        }
        $config = static::_expandDsn($config, $name);
        static::$config->{$name} = $config;
        static::$_connectionsEnum[$name] = static::_connectionData($config);
        $return = static::getDataSource($name);

        return $return;
    }

    /**
     * Adds DSN schemes, or replaces built-in ones, for the `'dsn'` key of
     * datasource configurations.
     *
     * A plugin registers its schemes in its `Config/bootstrap.php`:
     *
     * ```
     * ConnectionManager::setDsnClassMap([
     *     // mydb://user:pass@host:1234/name uses MyPlugin.Database/MyDb with
     *     // login, password, host, port and database
     *     'mydb' => 'MyPlugin.Database/MyDb',
     *     // a callable returns the configuration keys itself
     *     'otherdb' => function (array $parts, array $config) {
     *         return ['datasource' => 'MyPlugin.Database/Other', 'server' => $parts['host'] ?? 'localhost'] + $parts['query'];
     *     },
     * ]);
     * ```
     *
     * A value is either a datasource class name in plugin dot syntax, or a
     * callable (not a string) `function (array $parts, array $config): array`
     * that gets the parts of `CakeDsn::parse()` and the configuration without
     * the `'dsn'` key and returns the configuration keys. `null` removes a
     * scheme. Schemes are case-insensitive.
     *
     * The map is read when a connection is created, so register schemes
     * before the first query (a plugin bootstrap is early enough).
     *
     * @param array<string, mixed> $map Scheme => datasource class name or callable.
     * @return void
     */
    public static function setDsnClassMap($map)
    {
        static::$_dsnClassMap = CakeDsn::mergeClassMap($map, static::$_dsnClassMap);
    }

    /**
     * Returns the DSN class map (scheme => datasource class name or callable).
     *
     * @return array<string, mixed>
     */
    public static function getDsnClassMap()
    {
        return static::$_dsnClassMap;
    }

    /**
     * Parses a database DSN into datasource configuration keys.
     *
     * ```
     * ConnectionManager::parseDsn('mysql://app:secret@db.example.com:3306/app?encoding=utf8mb4');
     * // ['datasource' => 'Database/Mysql', 'login' => 'app', 'password' => 'secret',
     * //  'host' => 'db.example.com', 'port' => '3306', 'database' => 'app', 'encoding' => 'utf8mb4']
     *
     * ConnectionManager::parseDsn('sqlite:///:memory:');
     * // ['datasource' => 'Database/Sqlite', 'database' => ':memory:']
     * ```
     *
     * - The scheme selects the datasource with the DSN class map (see
     *   `setDsnClassMap()`): `mysql` (`Database/Mysql`), `postgres` /
     *   `postgresql` / `pgsql` (`Database/Postgres`), `sqlite`
     *   (`Database/Sqlite`) and `sqlserver` / `mssql` / `sqlsrv`
     *   (`Database/Sqlserver`) are built in. A `datasource` query parameter
     *   (e.g. `?datasource=MyPlugin.Database/Custom`) overrides a class name
     *   and allows schemes that are not in the map.
     * - For a class name, the user, password, host, port and path become
     *   `login`, `password`, `host`, `port` and `database`. They are
     *   URL-decoded, so encode reserved characters in a password (`p%40ss`
     *   for `p@ss`). The brackets of an IPv6 host (`[::1]`) are removed.
     * - The database is the path without its leading `/`, as in CakePHP 3.x and
     *   later: `sqlite:///:memory:` is `:memory:`, `sqlite:///app.sqlite` is
     *   the relative path `app.sqlite` and `sqlite:////var/db/app.sqlite`
     *   (four slashes) is the absolute path `/var/db/app.sqlite`.
     * - Query parameters become configuration keys as they are (`encoding`,
     *   `schema`, `prefix`, `persistent`, `flags[...]`, `options[...]`, ...).
     *   `true`, `false` and `null` are converted to the PHP values.
     * - A callable in the map returns the configuration keys itself.
     *
     * Only the parts present in the DSN are returned, so the other keys of a
     * configuration still apply.
     *
     * @param string $dsn The DSN.
     * @param array $config The configuration the DSN is merged into (passed to a callable of the map).
     * @return array The datasource configuration.
     * @throws CakeException When the DSN cannot be parsed or its scheme is not supported.
     */
    public static function parseDsn($dsn, $config = [])
    {
        return CakeDsn::toConfig($dsn, static::$_dsnClassMap, 'datasource', function ($parsed) {
            $config = [];
            if (isset($parsed['username']) && $parsed['username'] !== '') {
                $config['login'] = $parsed['username'];
            }
            if (isset($parsed['password'])) {
                $config['password'] = $parsed['password'];
            }
            if (isset($parsed['host'])) {
                $config['host'] = trim($parsed['host'], '[]');
            }
            if (isset($parsed['port'])) {
                $config['port'] = (string)$parsed['port'];
            }
            if (isset($parsed['path']) && strlen($parsed['path']) > 1) {
                $config['database'] = substr($parsed['path'], 1);
            }

            return $parsed['query'] + $config;
        }, $config);
    }

    /**
     * Removes a connection configuration at runtime given its name
     *
     * @param string $name the connection name as it was created
     * @return bool success if connection was removed, false if it does not exist
     */
    public static function drop($name)
    {
        if (empty(static::$_init)) {
            static::_init();
        }

        if (!isset(static::$config->{$name})) {
            return false;
        }
        unset(static::$_connectionsEnum[$name], static::$_dataSources[$name], static::$config->{$name});

        return true;
    }

    /**
     * Gets a list of class and file names associated with the user-defined DataSource connections
     *
     * @param string $name Connection name
     * @return void
     * @throws MissingDatasourceConfigException
     */
    protected static function _getConnectionObject($name)
    {
        if (!empty(static::$config->{$name})) {
            static::$config->{$name} = static::_expandDsn(static::$config->{$name}, $name);
            static::$_connectionsEnum[$name] = static::_connectionData(static::$config->{$name});
        } else {
            throw new MissingDatasourceConfigException(['config' => $name]);
        }
    }

    /**
     * Merges the DSN in the `'dsn'` key of a configuration into it.
     *
     * The keys parsed from the DSN replace the same keys of the configuration
     * (as `StaticConfigTrait::setConfig()` of CakePHP 3.x and later does), and
     * the other keys are kept. The `'dsn'` key is removed. An empty `'dsn'`
     * (e.g. `env('DATABASE_URL')` when the variable is not set) is ignored.
     *
     * @param array $config The connection configuration.
     * @param string $name The connection name, for error messages.
     * @return array The configuration without the `'dsn'` key.
     * @throws CakeException When the DSN cannot be parsed or its scheme is not supported.
     */
    protected static function _expandDsn($config, $name)
    {
        return CakeDsn::expandConfig(
            $config,
            [static::class, 'parseDsn'],
            sprintf('the "%s" datasource configuration', $name),
        );
    }

    /**
     * Returns the file, class name, and parent for the given driver.
     *
     * @param array $config Array with connection configuration. Key 'datasource' is required
     * @return array An indexed array with: filename, classname, plugin and parent
     */
    protected static function _connectionData($config)
    {
        $package = $classname = $plugin = null;

        [$plugin, $classname] = pluginSplit($config['datasource']);
        if (str_contains($classname, '/')) {
            $package = dirname($classname);
            $classname = basename($classname);
        }

        return compact('package', 'classname', 'plugin');
    }
}
