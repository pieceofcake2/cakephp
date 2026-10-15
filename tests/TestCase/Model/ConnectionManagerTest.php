<?php
/**
 * Connection Manager tests
 *
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.Test.Case.Model
 * @since         CakePHP(tm) v 1.2.0.5550
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

/**
 * ConnectionManagerTest
 *
 * @package       Cake.Test.Case.Model
 */
class ConnectionManagerTest extends CakeTestCase
{
    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        CakePlugin::unload();

        parent::tearDown();
    }

    /**
     * testEnumConnectionObjects method
     *
     * @return void
     */
    public function testEnumConnectionObjects(): void
    {
        $sources = ConnectionManager::enumConnectionObjects();
        $this->assertTrue(count($sources) >= 1);

        $connections = ['default', 'test', 'test'];
        $this->assertTrue(count(array_intersect(array_keys($sources), $connections)) >= 1);
    }

    /**
     * testGetDataSource method
     *
     * @return void
     */
    public function testGetDataSource(): void
    {
        App::build([
            'Model/Datasource' => [
                CORE_TESTS . DS . 'test_app' . DS . 'Model' . DS . 'Datasource' . DS,
            ],
        ]);

        $name = 'test_get_datasource';
        $config = ['datasource' => 'Test2Source'];

        ConnectionManager::create($name, $config);
        $connections = ConnectionManager::enumConnectionObjects();
        $this->assertTrue(count(array_keys($connections)) >= 1);

        $source = ConnectionManager::getDataSource('test_get_datasource');
        $this->assertTrue(is_object($source));
        ConnectionManager::drop('test_get_datasource');
    }

    /**
     * testGetDataSourceException() method
     *
     * @return void
     */
    public function testGetDataSourceException(): void
    {
        $this->expectException(MissingDatasourceConfigException::class);
        ConnectionManager::getDataSource('non_existent_source');
    }

    /**
     * testGetPluginDataSource method
     *
     * @return void
     */
    public function testGetPluginDataSource(): void
    {
        App::build([
            'Plugin' => [CORE_TESTS . DS . 'test_app' . DS . 'Plugin' . DS],
        ], App::RESET);
        CakePlugin::load('TestPlugin');
        $name = 'test_source';
        $config = ['datasource' => 'TestPlugin.TestSource'];
        $connection = ConnectionManager::create($name, $config);

        $this->assertTrue(class_exists('TestSource'));
        $this->assertEquals($connection->configKeyName, $name);
        $this->assertEquals($connection->config, $config);

        ConnectionManager::drop($name);
    }

    /**
     * testGetPluginDataSourceAndPluginDriver method
     *
     * @return void
     */
    public function testGetPluginDataSourceAndPluginDriver(): void
    {
        App::build([
            'Plugin' => [CORE_TESTS . DS . 'test_app' . DS . 'Plugin' . DS],
        ], App::RESET);
        CakePlugin::load('TestPlugin');
        $name = 'test_plugin_source_and_driver';
        $config = ['datasource' => 'TestPlugin.Database/TestDriver'];

        $connection = ConnectionManager::create($name, $config);

        $this->assertTrue(class_exists('TestSource'));
        $this->assertTrue(class_exists('TestDriver'));
        $this->assertEquals($connection->configKeyName, $name);
        $this->assertEquals($connection->config, $config);

        ConnectionManager::drop($name);
    }

    /**
     * testGetLocalDataSourceAndPluginDriver method
     *
     * @return void
     */
    public function testGetLocalDataSourceAndPluginDriver(): void
    {
        App::build([
            'Plugin' => [CORE_TESTS . DS . 'test_app' . DS . 'Plugin' . DS],
        ]);
        CakePlugin::load('TestPlugin');
        $name = 'test_local_source_and_plugin_driver';
        $config = ['datasource' => 'TestPlugin.Database/DboDummy'];

        $connection = ConnectionManager::create($name, $config);

        $this->assertTrue(class_exists('DboSource'));
        $this->assertTrue(class_exists('DboDummy'));
        $this->assertEquals($connection->configKeyName, $name);

        ConnectionManager::drop($name);
    }

    /**
     * testGetPluginDataSourceAndLocalDriver method
     *
     * @return void
     */
    public function testGetPluginDataSourceAndLocalDriver(): void
    {
        App::build([
            'Plugin' => [CORE_TESTS . DS . 'test_app' . DS . 'Plugin' . DS],
            'Model/Datasource/Database' => [
                CORE_TESTS . DS . 'test_app' . DS . 'Model' . DS . 'Datasource' . DS . 'Database' . DS,
            ],
        ]);

        $name = 'test_plugin_source_and_local_driver';
        $config = ['datasource' => 'Database/TestLocalDriver'];

        $connection = ConnectionManager::create($name, $config);

        $this->assertTrue(class_exists('TestSource'));
        $this->assertTrue(class_exists('TestLocalDriver'));
        $this->assertEquals($connection->configKeyName, $name);
        $this->assertEquals($connection->config, $config);
        ConnectionManager::drop($name);
    }

    /**
     * testSourceList method
     *
     * @return void
     */
    public function testSourceList(): void
    {
        ConnectionManager::getDataSource('test');
        $sources = ConnectionManager::sourceList();
        $this->assertTrue(count($sources) >= 1);
        $this->assertContains('test', $sources);
    }

    /**
     * testGetSourceName method
     *
     * @return void
     */
    public function testGetSourceName(): void
    {
        $source = ConnectionManager::getDataSource('test');
        $result = ConnectionManager::getSourceName($source);

        $this->assertEquals('test', $result);

        $source = new StdClass();
        $result = ConnectionManager::getSourceName($source);
        $this->assertNull($result);
    }

    /**
     * testLoadDataSource method
     *
     * @return void
     */
    public function testLoadDataSource(): void
    {
        $connections = [
            ['classname' => 'Mysql', 'filename' => 'Mysql', 'package' => 'Database'],
            ['classname' => 'Postgres', 'filename' => 'Postgres', 'package' => 'Database'],
            ['classname' => 'Sqlite', 'filename' => 'Sqlite', 'package' => 'Database'],
        ];

        foreach ($connections as $connection) {
            $exists = class_exists($connection['classname']);
            $loaded = ConnectionManager::loadDataSource($connection);
            $this->assertEquals($loaded, !$exists, "Failed loading the {$connection['classname']} datasource");
        }
    }

    /**
     * testLoadDataSourceException() method
     *
     * @return void
     */
    public function testLoadDataSourceException(): void
    {
        $this->expectException(MissingDatasourceException::class);
        $connection = ['classname' => 'NonExistentDataSource', 'filename' => 'non_existent'];
        ConnectionManager::loadDataSource($connection);
    }

    /**
     * testCreateDataSource method
     *
     * @return void
     */
    public function testCreateDataSourceWithIntegrationTests(): void
    {
        $name = 'test_created_connection';

        $connections = ConnectionManager::enumConnectionObjects();
        $this->assertTrue(count(array_keys($connections)) >= 1);

        $source = ConnectionManager::getDataSource('test');
        $this->assertTrue(is_object($source));

        $config = $source->config;
        $connection = ConnectionManager::create($name, $config);

        $this->assertTrue(is_object($connection));
        $this->assertEquals($name, $connection->configKeyName);
        $this->assertEquals($name, ConnectionManager::getSourceName($connection));

        $source = ConnectionManager::create(null, []);
        $this->assertEquals(null, $source);

        $source = ConnectionManager::create('another_test', []);
        $this->assertEquals(null, $source);

        $config = ['classname' => 'DboMysql', 'filename' => 'dbo' . DS . 'dbo_mysql'];
        $source = ConnectionManager::create(null, $config);
        $this->assertEquals(null, $source);
    }

    /**
     * testConnectionData method
     *
     * @return void
     */
    public function testConnectionData(): void
    {
        App::build([
            'Plugin' => [CORE_TESTS . DS . 'test_app' . DS . 'Plugin' . DS],
            'Model/Datasource' => [
                CORE_TESTS . DS . 'test_app' . DS . 'Model' . DS . 'Datasource' . DS,
            ],
        ], App::RESET);
        CakePlugin::load(['TestPlugin', 'TestPluginTwo']);
        $expected = [
            'datasource' => 'Test2Source',
        ];

        ConnectionManager::create('connection1', ['datasource' => 'Test2Source']);
        $connections = ConnectionManager::enumConnectionObjects();
        $this->assertEquals($expected, $connections['connection1']);
        ConnectionManager::drop('connection1');

        ConnectionManager::create('connection2', ['datasource' => 'Test2Source']);
        $connections = ConnectionManager::enumConnectionObjects();
        $this->assertEquals($expected, $connections['connection2']);
        ConnectionManager::drop('connection2');

        ConnectionManager::create('connection3', ['datasource' => 'TestPlugin.TestSource']);
        $connections = ConnectionManager::enumConnectionObjects();
        $expected['datasource'] = 'TestPlugin.TestSource';
        $this->assertEquals($expected, $connections['connection3']);
        ConnectionManager::drop('connection3');

        ConnectionManager::create('connection4', ['datasource' => 'TestPlugin.TestSource']);
        $connections = ConnectionManager::enumConnectionObjects();
        $this->assertEquals($expected, $connections['connection4']);
        ConnectionManager::drop('connection4');

        ConnectionManager::create('connection5', ['datasource' => 'Test2OtherSource']);
        $connections = ConnectionManager::enumConnectionObjects();
        $expected['datasource'] = 'Test2OtherSource';
        $this->assertEquals($expected, $connections['connection5']);
        ConnectionManager::drop('connection5');

        ConnectionManager::create('connection6', ['datasource' => 'Test2OtherSource']);
        $connections = ConnectionManager::enumConnectionObjects();
        $this->assertEquals($expected, $connections['connection6']);
        ConnectionManager::drop('connection6');

        ConnectionManager::create('connection7', ['datasource' => 'TestPlugin.TestOtherSource']);
        $connections = ConnectionManager::enumConnectionObjects();
        $expected['datasource'] = 'TestPlugin.TestOtherSource';
        $this->assertEquals($expected, $connections['connection7']);
        ConnectionManager::drop('connection7');

        ConnectionManager::create('connection8', ['datasource' => 'TestPlugin.TestOtherSource']);
        $connections = ConnectionManager::enumConnectionObjects();
        $this->assertEquals($expected, $connections['connection8']);
        ConnectionManager::drop('connection8');
    }

    /**
     * Tests that a connection configuration can be deleted in runtime
     *
     * @return void
     */
    public function testDrop(): void
    {
        App::build([
            'Model/Datasource' => [
                CORE_TESTS . DS . 'test_app' . DS . 'Model' . DS . 'Datasource' . DS,
            ],
        ]);
        ConnectionManager::create('droppable', ['datasource' => 'Test2Source']);
        $connections = ConnectionManager::enumConnectionObjects();
        $this->assertEquals(['datasource' => 'Test2Source'], $connections['droppable']);

        $this->assertTrue(ConnectionManager::drop('droppable'));
        $connections = ConnectionManager::enumConnectionObjects();
        $this->assertFalse(isset($connections['droppable']));
    }

    /**
     * Data provider for testParseDsn
     *
     * @return array
     */
    public static function parseDsnProvider()
    {
        return [
            'mysql' => [
                'mysql://app:secret@db.example.com:3306/app_db?encoding=utf8mb4',
                [
                    'datasource' => 'Database/Mysql',
                    'login' => 'app',
                    'password' => 'secret',
                    'host' => 'db.example.com',
                    'port' => '3306',
                    'database' => 'app_db',
                    'encoding' => 'utf8mb4',
                ],
            ],
            'mysql without port' => [
                'mysql://root@localhost/app',
                ['datasource' => 'Database/Mysql', 'login' => 'root', 'host' => 'localhost', 'database' => 'app'],
            ],
            'mysql without password and database' => [
                'mysql://localhost',
                ['datasource' => 'Database/Mysql', 'host' => 'localhost'],
            ],
            'empty password' => [
                'mysql://root:@localhost/app',
                ['datasource' => 'Database/Mysql', 'login' => 'root', 'password' => '', 'host' => 'localhost', 'database' => 'app'],
            ],
            'postgres' => [
                'postgres://postgres:postgres@127.0.0.1:5432/app?schema=public',
                [
                    'datasource' => 'Database/Postgres',
                    'login' => 'postgres',
                    'password' => 'postgres',
                    'host' => '127.0.0.1',
                    'port' => '5432',
                    'database' => 'app',
                    'schema' => 'public',
                ],
            ],
            'postgresql' => [
                'postgresql://u@h/d',
                ['datasource' => 'Database/Postgres', 'login' => 'u', 'host' => 'h', 'database' => 'd'],
            ],
            'pgsql' => [
                'pgsql://u@h/d',
                ['datasource' => 'Database/Postgres', 'login' => 'u', 'host' => 'h', 'database' => 'd'],
            ],
            'sqlite memory' => [
                'sqlite:///:memory:',
                ['datasource' => 'Database/Sqlite', 'database' => ':memory:'],
            ],
            'sqlite relative path' => [
                'sqlite:///tmp/app.sqlite',
                ['datasource' => 'Database/Sqlite', 'database' => 'tmp/app.sqlite'],
            ],
            'sqlite absolute path' => [
                'sqlite:////var/db/app.sqlite',
                ['datasource' => 'Database/Sqlite', 'database' => '/var/db/app.sqlite'],
            ],
            'sqlite with host' => [
                'sqlite://127.0.0.1/tmp/tests.sqlite',
                ['datasource' => 'Database/Sqlite', 'host' => '127.0.0.1', 'database' => 'tmp/tests.sqlite'],
            ],
            'sqlserver' => [
                'sqlserver://sa:Password123%21@127.0.0.1:1433/app?encoding=utf8&options[TrustServerCertificate]=yes',
                [
                    'datasource' => 'Database/Sqlserver',
                    'login' => 'sa',
                    'password' => 'Password123!',
                    'host' => '127.0.0.1',
                    'port' => '1433',
                    'database' => 'app',
                    'encoding' => 'utf8',
                    'options' => ['TrustServerCertificate' => 'yes'],
                ],
            ],
            'sqlserver named instance' => [
                'mssql://sa:pw@localhost%5CSQLEXPRESS/app',
                ['datasource' => 'Database/Sqlserver', 'login' => 'sa', 'password' => 'pw', 'host' => 'localhost\SQLEXPRESS', 'database' => 'app'],
            ],
            'sqlsrv' => [
                'sqlsrv://sa:pw@db/app',
                ['datasource' => 'Database/Sqlserver', 'login' => 'sa', 'password' => 'pw', 'host' => 'db', 'database' => 'app'],
            ],
            'scheme is case insensitive' => [
                'MySQL://u@h/d',
                ['datasource' => 'Database/Mysql', 'login' => 'u', 'host' => 'h', 'database' => 'd'],
            ],
            'encoded special characters' => [
                'mysql://my%40user:p%40ss%3Aw%2Fo%23rd%3F%25+@localhost/my%20db',
                ['datasource' => 'Database/Mysql', 'login' => 'my@user', 'password' => 'p@ss:w/o#rd?%+', 'host' => 'localhost', 'database' => 'my db'],
            ],
            'unencoded @ and : in password' => [
                'mysql://user:p@ss:word@localhost/app',
                ['datasource' => 'Database/Mysql', 'login' => 'user', 'password' => 'p@ss:word', 'host' => 'localhost', 'database' => 'app'],
            ],
            'ipv6 host' => [
                'mysql://root:pw@[::1]:3307/app',
                ['datasource' => 'Database/Mysql', 'login' => 'root', 'password' => 'pw', 'host' => '::1', 'port' => '3307', 'database' => 'app'],
            ],
            'ipv6 host without port' => [
                'postgres://u@[2001:db8::10]/app',
                ['datasource' => 'Database/Postgres', 'login' => 'u', 'host' => '2001:db8::10', 'database' => 'app'],
            ],
            'query options' => [
                'mysql://u@h/d?prefix=app_&persistent=true&encoding=utf8&flags[1002]=SET%20NAMES%20utf8&timezone=null&cacheMetadata=false',
                [
                    'datasource' => 'Database/Mysql',
                    'login' => 'u',
                    'host' => 'h',
                    'database' => 'd',
                    'prefix' => 'app_',
                    'persistent' => true,
                    'encoding' => 'utf8',
                    'flags' => [1002 => 'SET NAMES utf8'],
                    'timezone' => null,
                    'cacheMetadata' => false,
                ],
            ],
            'query parameters win over the DSN' => [
                'mysql://u@h/d?database=other&datasource=MyPlugin.Database/Custom',
                ['datasource' => 'MyPlugin.Database/Custom', 'login' => 'u', 'host' => 'h', 'database' => 'other'],
            ],
            'unknown scheme with datasource' => [
                'mydb://u@h/d?datasource=MyPlugin.Database/MyDb',
                ['datasource' => 'MyPlugin.Database/MyDb', 'login' => 'u', 'host' => 'h', 'database' => 'd'],
            ],
            'fragment is ignored' => [
                'mysql://u@h/d#comment',
                ['datasource' => 'Database/Mysql', 'login' => 'u', 'host' => 'h', 'database' => 'd'],
            ],
        ];
    }

    /**
     * Test parseDsn()
     *
     * @param string $dsn DSN
     * @param array $expected Expected configuration
     * @return void
     * @dataProvider parseDsnProvider
     */
    public function testParseDsn($dsn, $expected)
    {
        $result = ConnectionManager::parseDsn($dsn);
        ksort($expected);
        ksort($result);
        $this->assertSame($expected, $result);
    }

    /**
     * Test parseDsn() with an unknown scheme
     *
     * @return void
     */
    public function testParseDsnUnknownScheme()
    {
        $this->expectException(CakeException::class);
        $this->expectExceptionMessage('The DSN scheme "oracle" is not supported.');
        ConnectionManager::parseDsn('oracle://u:p@h/d');
    }

    /**
     * Test parseDsn() with an invalid DSN does not print the password
     *
     * @return void
     */
    public function testParseDsnInvalid()
    {
        try {
            ConnectionManager::parseDsn('not a dsn://user:secret@host');
            $this->fail('No exception thrown');
        } catch (CakeException $e) {
            $this->assertStringContainsString('could not be parsed', $e->getMessage());
            $this->assertStringNotContainsString('secret', $e->getMessage());
        }
    }

    /**
     * Test that the DSN of a configuration replaces the keys it sets and keeps the others
     *
     * @return void
     */
    public function testCreateWithDsnMergesExplicitKeys()
    {
        $name = 'test_dsn_merge';
        ConnectionManager::create($name, [
            'datasource' => 'Database/Mysql',
            'dsn' => 'sqlite:///:memory:?prefix=dsn_',
            'database' => 'ignored',
            'prefix' => 'ignored_',
            'persistent' => false,
            'encoding' => 'utf8',
        ]);
        $db = ConnectionManager::getDataSource($name);
        $config = ConnectionManager::enumConnectionObjects()[$name];
        ConnectionManager::drop($name);

        $this->assertInstanceOf('Sqlite', $db);
        $this->assertArrayNotHasKey('dsn', $config);
        $this->assertSame('Database/Sqlite', $config['datasource']);
        $this->assertSame(':memory:', $config['database']);
        $this->assertSame('dsn_', $config['prefix']);
        $this->assertFalse($config['persistent']);
        $this->assertSame('utf8', $config['encoding']);
        $this->assertSame(':memory:', $db->config['database']);
        $this->assertSame('dsn_', $db->config['prefix']);
    }

    /**
     * Test that an empty DSN is ignored
     *
     * @return void
     */
    public function testCreateWithEmptyDsn()
    {
        foreach ([null, '', false] as $i => $dsn) {
            $name = 'test_dsn_empty_' . $i;
            ConnectionManager::create($name, [
                'datasource' => 'Database/Sqlite',
                'dsn' => $dsn,
                'database' => ':memory:',
            ]);
            $db = ConnectionManager::getDataSource($name);
            $config = ConnectionManager::enumConnectionObjects()[$name];
            ConnectionManager::drop($name);

            $this->assertInstanceOf('Sqlite', $db);
            $this->assertArrayNotHasKey('dsn', $config);
            $this->assertSame(':memory:', $db->config['database']);
        }
    }

    /**
     * Test an invalid DSN names the connection
     *
     * @return void
     */
    public function testCreateWithInvalidDsn()
    {
        $this->expectException(CakeException::class);
        $this->expectExceptionMessage('Invalid "dsn" of the "test_dsn_invalid" datasource configuration');
        ConnectionManager::create('test_dsn_invalid', ['dsn' => 'oracle://u:p@h/d']);
    }

    /**
     * Test connecting to SQLite with a DATABASE_URL environment variable, from
     * a configuration in DATABASE_CONFIG
     *
     * @return void
     */
    public function testGetDataSourceWithDatabaseUrl()
    {
        $this->skipIf(!extension_loaded('pdo_sqlite'), 'pdo_sqlite is required.');

        $file = TMP . 'tests' . DS . 'dsn_test_' . getmypid() . '.sqlite';
        if (file_exists($file)) {
            unlink($file);
        }
        $previous = getenv('DATABASE_URL');
        putenv('DATABASE_URL=sqlite:///' . str_replace('%2F', '/', rawurlencode($file)) . '?prefix=dsn_');
        $name = 'test_dsn_env';

        try {
            ConnectionManager::$config->{$name} = [
                'datasource' => 'Database/Mysql',
                'persistent' => false,
                'dsn' => env('DATABASE_URL'),
            ];
            $db = ConnectionManager::getDataSource($name);

            $this->assertInstanceOf('Sqlite', $db);
            $this->assertTrue($db->isConnected());
            $this->assertSame($file, $db->config['database']);
            $this->assertSame('dsn_', $db->config['prefix']);

            $db->rawQuery('CREATE TABLE dsn_items (id INTEGER PRIMARY KEY, title VARCHAR(20))');
            $db->rawQuery("INSERT INTO dsn_items (title) VALUES ('first')");
            $Item = new Model(['table' => 'items', 'name' => 'Item', 'ds' => $name]);
            $this->assertSame('dsn_items', $Item->tablePrefix . $Item->useTable);
            $result = $Item->find('first', ['fields' => ['title']]);
            $this->assertSame('first', $result['Item']['title']);
            $this->assertFileExists($file);
        } finally {
            if (isset($db)) {
                $db->disconnect();
            }
            ConnectionManager::drop($name);
            putenv($previous === false ? 'DATABASE_URL' : 'DATABASE_URL=' . $previous);
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }

    /**
     * Restores the DSN class map of ConnectionManager.
     *
     * @param array $map The map to restore.
     * @return void
     */
    protected function _restoreDsnClassMap($map)
    {
        $property = new ReflectionProperty('ConnectionManager', '_dsnClassMap');
        $property->setAccessible(true);
        $property->setValue(null, $map);
    }

    /**
     * Test the built-in DSN class map
     *
     * @return void
     */
    public function testGetDsnClassMap()
    {
        $map = ConnectionManager::getDsnClassMap();
        $this->assertSame('Database/Mysql', $map['mysql']);
        $this->assertSame('Database/Postgres', $map['pgsql']);
        $this->assertSame('Database/Sqlite', $map['sqlite']);
        $this->assertSame('Database/Sqlserver', $map['sqlsrv']);
    }

    /**
     * Test setDsnClassMap() with a class name, a callable, an override and a removal
     *
     * @return void
     */
    public function testSetDsnClassMap()
    {
        $original = ConnectionManager::getDsnClassMap();
        try {
            ConnectionManager::setDsnClassMap([
                'MyDb' => 'MyPlugin.Database/MyDb',
                'otherdb' => function (array $parts, array $config) {
                    return [
                        'datasource' => 'MyPlugin.Database/Other',
                        'server' => $parts['host'],
                        'user' => $parts['username'],
                        'had' => $config['encoding'] ?? null,
                    ] + $parts['query'];
                },
                'mysql' => 'MyPlugin.Database/MyMysql',
                'mssql' => null,
            ]);

            $this->assertSame(
                ['prefix' => 'x_', 'login' => 'u', 'password' => 'p', 'host' => 'h', 'port' => '1234', 'database' => 'd', 'datasource' => 'MyPlugin.Database/MyDb'],
                ConnectionManager::parseDsn('mydb://u:p@h:1234/d?prefix=x_'),
            );
            $this->assertSame(
                ['datasource' => 'MyPlugin.Database/Other', 'server' => 'h', 'user' => 'u', 'had' => 'utf8', 'opt' => '1'],
                ConnectionManager::parseDsn('otherdb://u@h/d?opt=1', ['encoding' => 'utf8']),
            );
            $this->assertSame('MyPlugin.Database/MyMysql', ConnectionManager::parseDsn('mysql://h/d')['datasource']);
            $this->assertSame('Database/Postgres', ConnectionManager::parseDsn('postgres://h/d')['datasource']);
            $this->assertArrayNotHasKey('mssql', ConnectionManager::getDsnClassMap());
            $this->assertArrayHasKey('mydb', ConnectionManager::getDsnClassMap());

            $this->expectException(CakeException::class);
            $this->expectExceptionMessage('The DSN scheme "mssql" is not supported.');
            ConnectionManager::parseDsn('mssql://h/d');
        } finally {
            $this->_restoreDsnClassMap($original);
        }
    }

    /**
     * Test that a scheme registered after the configuration is defined is used
     * when the connection is created
     *
     * @return void
     */
    public function testDsnClassMapIsReadWhenTheConnectionIsCreated()
    {
        $this->skipIf(!extension_loaded('pdo_sqlite'), 'pdo_sqlite is required.');

        $original = ConnectionManager::getDsnClassMap();
        $name = 'test_dsn_class_map';
        ConnectionManager::$config->{$name} = [
            'datasource' => 'Database/Mysql',
            'dsn' => 'memorylite://localhost?prefix=map_',
        ];
        try {
            ConnectionManager::setDsnClassMap([
                'memorylite' => function (array $parts, array $config) {
                    return ['datasource' => 'Database/Sqlite', 'database' => ':memory:'] + $parts['query'];
                },
            ]);
            $db = ConnectionManager::getDataSource($name);

            $this->assertInstanceOf('Sqlite', $db);
            $this->assertSame(':memory:', $db->config['database']);
            $this->assertSame('map_', $db->config['prefix']);
            $this->assertArrayNotHasKey('dsn', $db->config);
        } finally {
            ConnectionManager::drop($name);
            $this->_restoreDsnClassMap($original);
        }
    }

    /**
     * Test errors of a callable in the map name the connection
     *
     * @return void
     */
    public function testDsnClassMapCallableMustReturnArray()
    {
        $original = ConnectionManager::getDsnClassMap();
        ConnectionManager::setDsnClassMap(['broken' => function () {
            return 'not an array';
        }]);
        try {
            ConnectionManager::create('test_dsn_broken', ['dsn' => 'broken://u:secret@h/d']);
            $this->fail('No exception thrown');
        } catch (CakeException $e) {
            $this->assertStringContainsString('Invalid "dsn" of the "test_dsn_broken" datasource configuration', $e->getMessage());
            $this->assertStringContainsString('must return an array', $e->getMessage());
            $this->assertStringNotContainsString('secret', $e->getMessage());
        } finally {
            $this->_restoreDsnClassMap($original);
        }
    }
}
