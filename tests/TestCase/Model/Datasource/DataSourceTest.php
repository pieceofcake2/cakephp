<?php
/**
 * DataSourceTest file
 *
 * CakePHP(tm) Tests <https://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.Test.Case.Model.Datasource
 * @since         CakePHP(tm) v 1.2.0.4206
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Test\TestCase\Model\Datasource;

use ArrayObject;
use Cake\Cache\Cache;
use Cake\Model\ConnectionManager;
use Cake\Model\Datasource\DataSource;
use Cake\Model\Model;
use Cake\TestSuite\CakeTestCase;

/**
 * TestSource
 *
 * @package       Cake.Test.Case.Model.Datasource
 */
class TestSource extends DataSource
{
    /**
     * _schema
     *
     * @var array
     */
    protected $_schema = [
        'id' => [
            'type' => 'integer',
            'null' => false,
            'key' => 'primary',
            'length' => 11,
        ],
        'text' => [
            'type' => 'string',
            'null' => true,
            'length' => 140,
        ],
        'status' => [
            'type' => 'string',
            'null' => true,
            'length' => 140,
        ],
        'customField' => [
            'type' => 'string',
            'null' => true,
            'length' => 255,
        ],
    ];

    /**
     * listSources
     *
     * @return array|null
     */
    public function listSources($data = \null): mixed
    {
        return null;
    }

    /**
     * Returns the schema for the datasource to enable create/update
     *
     * @param Model|string $model
     * @return array|false|null
     */
    public function describe($Model): mixed
    {
        return $this->_schema;
    }

    /**
     * Just return $func to pass to read() to figure out the COUNT
     * Required for delete/update to work
     *
     * @param Model $model
     * @param string $func
     * @param array|string $params
     * @return string
     */
    public function calculate(Model $Model, $func, $params = []): mixed
    {
        return $func;
    }

    /**
     * Quotes a value, integers without quotes
     *
     * @param mixed $data The value
     * @param string|null $column The column type
     * @return string
     */
    public function value($data, $column = null): mixed
    {
        if ($column === 'integer') {
            return (string)(int)$data;
        }

        return "'" . $data . "'";
    }
}

/**
 * A datasource that keeps the listSources(), describe() and transaction
 * behaviour of DataSource
 *
 * @package       Cake.Test.Case.Model.Datasource
 */
class BaseTestSource extends DataSource
{
    /**
     * Base configuration
     *
     * @var array
     */
    protected $_baseConfig = ['host' => 'localhost', 'database' => 'default'];

    /**
     * Records the rollback() and close() calls
     *
     * @var ArrayObject|null
     */
    public $calls = null;

    /**
     * Sets whether a transaction is started
     *
     * @param bool $started Whether a transaction is started
     * @return void
     */
    public function setTransactionStarted($started): void
    {
        $this->_transactionStarted = $started;
    }

    /**
     * Caches a description as DboSource::describe() does
     *
     * @param string $table The table name
     * @param array $description The description
     * @return mixed
     */
    public function cacheDescription($table, $description): mixed
    {
        return $this->_cacheDescription($table, $description);
    }

    /**
     * Records the call and rolls back
     *
     * @return bool
     */
    public function rollback(): mixed
    {
        if ($this->calls !== null) {
            $this->calls[] = 'rollback';
        }

        return parent::rollback();
    }

    /**
     * Records the call and closes
     *
     * @return bool|null
     */
    public function close(): mixed
    {
        if ($this->calls !== null) {
            $this->calls[] = 'close';
        }

        return parent::close();
    }
}

/**
 * DataSourceTest class
 *
 * @package       Cake.Test.Case.Model.Datasource
 */
class DataSourceTest extends CakeTestCase
{
    /**
     * Name of test source
     *
     * @var string
     */
    public $sourceName = 'myapitest';

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->Source = $this->getMock(
            TestSource::class,
            ['create', 'read', 'update', 'delete'],
        );
        ConnectionManager::create($this->sourceName, [
            'datasource' => $this->Source::class,
            'apiKey' => '1234abcd',
        ]);
        $this->Model = $this->getMock(
            Model::class,
            ['getDataSource'],
            [['ds' => $this->sourceName]],
        );
        $this->Model->expects($this->any())
            ->method('getDataSource')
            ->will($this->returnValue($this->Source));
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->Model, $this->Source);
        ConnectionManager::drop($this->sourceName);
        ConnectionManager::drop('datasource_test_base');
        Cache::delete('datasource_test_base_my_db_1_list', '_cake_model_');
        Cache::delete('datasource_test_base_prefix_posts', '_cake_model_');
        Cache::delete('datasource_test_base_unknown', '_cake_model_');
        Cache::delete('datasource_test_base_comments', '_cake_model_');

        parent::tearDown();
    }

    /**
     * testCreate
     *
     * @return void
     */
    public function testCreate(): void
    {
        $data = [
            $this->Model->alias => [
                'text' => 'This is a test',
                'status' => 'Test status',
                'customField' => [
                    'array', 'field', 'type',
                    'for', 'custom', 'datasources',
                ],
            ],
        ];
        $this->Source->expects($this->once())
            ->method('create')
            ->with(
                $this->equalTo($this->Model),
                $this->equalTo(array_keys($data[$this->Model->alias])),
                $this->equalTo(array_values($data[$this->Model->alias])),
            );
        $this->Model->save($data);
    }

    /**
     * testRead
     *
     * @return void
     */
    public function testRead(): void
    {
        $expected = [
            'conditions' => ['status' => 'test'],
            'fields' => null,
            'joins' => [],
            'limit' => 10,
            'offset' => null,
            'order' => ['status'],
            'page' => 1,
            'group' => null,
            'callbacks' => true,
        ];
        $this->Source->expects($this->once())
            ->method('read')
            ->with(
                $this->anything(),
                $this->equalTo($expected),
            );
        $this->Model->find('all', [
            'conditions' => ['status' => 'test'],
            'limit' => 10,
            'order' => ['status'],
        ]);
    }

    /**
     * testUpdate
     *
     * @return void
     */
    public function testUpdate(): void
    {
        $data = [
            $this->Model->alias => [
                'id' => 1,
                'text' => 'This is a test',
                'status' => 'Test status',
                'customField' => [
                    'array', 'field', 'type',
                    'for', 'custom', 'datasources',
                ],
            ],
        ];
        $this->Source->expects($this->any())
            ->method('read')
            ->will($this->returnValue([
                [$this->Model->alias => ['count' => 1]],
            ]));
        $this->Source->expects($this->once())
            ->method('update')
            ->with(
                $this->equalTo($this->Model),
                $this->equalTo(array_keys($data[$this->Model->alias])),
                $this->equalTo(array_values($data[$this->Model->alias])),
            );
        $this->Model->save($data);
    }

    /**
     * testDelete
     *
     * @return void
     */
    public function testDelete(): void
    {
        $this->Source->expects($this->any())
            ->method('read')
            ->will($this->returnValue([
                [$this->Model->alias => ['count' => 1]],
            ]));
        $this->Source->expects($this->once())
            ->method('delete')
            ->with(
                $this->equalTo($this->Model),
                $this->equalTo([$this->Model->alias . '.id' => 1]),
            );
        $this->Model->delete(1);
    }

    /**
     * Creates a BaseTestSource registered in the ConnectionManager.
     *
     * @param array $config The configuration
     * @return BaseTestSource
     */
    protected function _baseSource($config = []): BaseTestSource
    {
        $this->skipIf(!in_array('_cake_model_', Cache::configured()), 'The _cake_model_ cache is not configured.');
        ConnectionManager::drop('datasource_test_base');

        return ConnectionManager::create('datasource_test_base', ['datasource' => BaseTestSource::class] + $config);
    }

    /**
     * Test that listSources() stores the given sources in the model cache
     * under a key made of the connection and the database names, and that a
     * new datasource for the same connection reads them from the cache.
     *
     * @return void
     */
    public function testListSourcesCachesTheSources(): void
    {
        $source = $this->_baseSource(['database' => 'my db/1']);
        $this->assertSame(['posts', 'comments'], $source->listSources(['posts', 'comments']));
        $this->assertSame(['posts', 'comments'], Cache::read('datasource_test_base_my_db_1_list', '_cake_model_'));
        $this->assertSame(['posts', 'comments'], $source->listSources(['other']));

        $source = $this->_baseSource(['database' => 'my db/1']);
        $this->assertSame(['posts', 'comments'], $source->listSources());

        $source->cacheSources = false;
        $this->assertNull($source->listSources(['posts']));
    }

    /**
     * Test that describe() returns the cached description of a table, by
     * name or by the prefixed table of a model, and keeps it in memory, and
     * that _cacheDescription() stores a description for the next datasource.
     *
     * @return void
     */
    public function testDescribeReadsTheCachedDescription(): void
    {
        $source = $this->_baseSource();
        $schema = ['id' => ['type' => 'integer', 'key' => 'primary']];
        Cache::write('datasource_test_base_prefix_posts', $schema, '_cake_model_');

        $Model = new Model(['table' => false, 'name' => 'Post']);
        $Model->table = 'posts';
        $Model->tablePrefix = 'prefix_';
        $this->assertSame($schema, $source->describe($Model));

        Cache::delete('datasource_test_base_prefix_posts', '_cake_model_');
        $this->assertSame($schema, $source->describe('prefix_posts'));

        $this->assertNull($source->describe('unknown'));

        $comments = ['id' => ['type' => 'string', 'key' => 'primary']];
        $this->assertSame($comments, $source->cacheDescription('comments', $comments));
        $this->assertSame($comments, Cache::read('datasource_test_base_comments', '_cake_model_'));
        $this->assertSame($comments, $source->describe('comments'));
        $this->assertSame($comments, $this->_baseSource()->describe('comments'));

        $source->cacheSources = false;
        $this->assertNull($source->describe('prefix_posts'));
        $this->assertNull($source->cacheDescription('comments', $comments));
    }

    /**
     * Test that setConfig() merges the base configuration, the current
     * configuration and the given one, in that order.
     *
     * @return void
     */
    public function testSetConfig(): void
    {
        $source = new BaseTestSource(['database' => 'app']);
        $this->assertSame(['host' => 'localhost', 'database' => 'app'], $source->config);

        $source->setConfig(['login' => 'user', 'host' => 'db.local']);
        $this->assertSame(['host' => 'db.local', 'database' => 'app', 'login' => 'user'], $source->config);
    }

    /**
     * Test the transaction state, and that the destructor rolls back a
     * started transaction and closes an open connection.
     *
     * @return void
     */
    public function testTransactionsAndDestructor(): void
    {
        $source = new BaseTestSource();
        $this->assertTrue($source->begin());
        $this->assertFalse($source->commit());
        $this->assertFalse($source->rollback());

        $source->setTransactionStarted(true);
        $this->assertFalse($source->begin());
        $this->assertTrue($source->commit());

        $calls = new ArrayObject();
        $source->calls = $calls;
        $source->connected = true;
        unset($source);
        $this->assertSame(['rollback', 'close'], $calls->getArrayCopy());

        $calls = new ArrayObject();
        $source = new BaseTestSource();
        $source->calls = $calls;
        unset($source);
        $this->assertSame([], $calls->getArrayCopy());
    }

    /**
     * Test the results of the operations that the subclasses have to implement.
     *
     * @return void
     */
    public function testBaseOperations(): void
    {
        $source = new BaseTestSource();
        $this->assertFalse($source->create($this->Model));
        $this->assertFalse($source->read($this->Model));
        $this->assertFalse($source->update($this->Model));
        $this->assertFalse($source->delete($this->Model));
        $this->assertFalse($source->column('varchar(255)'));
        $this->assertFalse($source->lastInsertId());
        $this->assertFalse($source->lastNumRows());
        $this->assertFalse($source->lastAffected());
        $this->assertTrue($source->enabled());
        $this->assertNull($source->getSchemaName());
        $this->assertSame($this->Model->alias . '__id', $source->resolveKey($this->Model, '__id'));

        $source->connected = true;
        $source->close();
        $this->assertFalse($source->connected);
    }

    /**
     * Test that insertQueryData() replaces the id placeholder with the
     * primary key of the model, quoted for the type of the primary key.
     *
     * @return void
     */
    public function testInsertQueryDataId(): void
    {
        $alias = $this->Model->alias;
        $query = 'SELECT * FROM comments WHERE post_id = {$__cakeID__$}';

        $result = $this->Source->insertQueryData($query, [$alias => ['id' => '5']], 'Comment', $this->Model, []);
        $this->assertSame('SELECT * FROM comments WHERE post_id = 5', $result);

        $result = $this->Source->insertQueryData($query, [$alias => ['id' => '0']], 'Comment', $this->Model, []);
        $this->assertSame('SELECT * FROM comments WHERE post_id = 0', $result);

        $result = $this->Source->insertQueryData($query, ['Comment' => ['id' => 6]], 'Comment', $this->Model, []);
        $this->assertSame('SELECT * FROM comments WHERE post_id = 6', $result);

        $data = ['Tag' => ['id' => 3], 'Category' => ['id' => 4]];
        $result = $this->Source->insertQueryData($query, $data, 'Comment', $this->Model, ['Tag', 'Category']);
        $this->assertSame('SELECT * FROM comments WHERE post_id = 4', $result);
        $result = $this->Source->insertQueryData($query, $data, 'Comment', $this->Model, ['Category', 'Tag', 99]);
        $this->assertSame('SELECT * FROM comments WHERE post_id = 3', $result);

        $this->assertFalse($this->Source->insertQueryData($query, $data, 'Comment', $this->Model, ['Other']));
        $this->assertFalse($this->Source->insertQueryData($query, [$alias => ['id' => '']], 'Comment', $this->Model, []));

        $query = 'SELECT * FROM comments';
        $this->assertSame($query, $this->Source->insertQueryData($query, [], 'Comment', $this->Model, []));
    }

    /**
     * Test that insertQueryData() replaces the foreign key placeholder with
     * the foreign key of the association, and fails for an association
     * without a foreign key.
     *
     * @return void
     */
    public function testInsertQueryDataForeignKey(): void
    {
        $alias = $this->Model->alias;
        $this->Model->hasMany = [
            'Comment' => ['className' => 'Comment', 'foreignKey' => 'post_id'],
            'Note' => ['className' => 'Note'],
        ];
        $this->Model->Comment = $this->Model;
        $this->Model->Note = $this->Model;
        $query = 'SELECT * FROM comments WHERE id = {$__cakeForeignKey__$} AND post_id = {$__cakeID__$}';

        $data = [$alias => ['id' => 1, 'post_id' => 2], 'Comment' => ['post_id' => 7]];
        $result = $this->Source->insertQueryData($query, $data, 'Comment', $this->Model, []);
        $this->assertSame('SELECT * FROM comments WHERE id = 2 AND post_id = 1', $result);

        $data = [$alias => ['id' => 1], 'Comment' => ['post_id' => 7]];
        $result = $this->Source->insertQueryData($query, $data, 'Comment', $this->Model, []);
        $this->assertSame('SELECT * FROM comments WHERE id = 7 AND post_id = 1', $result);

        $data = [$alias => ['id' => 1, 'post_id' => 2]];
        $this->assertFalse($this->Source->insertQueryData($query, $data, 'Note', $this->Model, []));
        $this->assertFalse($this->Source->insertQueryData($query, $data, 'Unknown', $this->Model, []));
    }
}
