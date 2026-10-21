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

namespace Cake\Test\TestCase\Legacy\Model;

use Cake\Core\App;
use Cake\Core\CakePlugin;
use Cake\Error\MissingDatasourceException;
use Cake\Model\ConnectionManager;
use Cake\TestSuite\CakeTestCase;

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
     * testGetDataSource method
     *
     * @return void
     */
    public function testGetDataSource(): void
    {
        App::build([
            'Model/Datasource' => [
                CORE_TESTS . DS . 'test_legacy_app' . DS . 'Model' . DS . 'Datasource' . DS,
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
     * testGetPluginDataSource method
     *
     * @return void
     */
    public function testGetPluginDataSource(): void
    {
        App::build([
            'Plugin' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Plugin' . DS],
        ], App::RESET);
        CakePlugin::load('TestLegacyPlugin');
        $name = 'test_source';
        $config = ['datasource' => 'TestLegacyPlugin.TestSource'];
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
            'Plugin' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Plugin' . DS],
        ], App::RESET);
        CakePlugin::load('TestLegacyPlugin');
        $name = 'test_plugin_source_and_driver';
        $config = ['datasource' => 'TestLegacyPlugin.Database/TestDriver'];

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
            'Plugin' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Plugin' . DS],
        ]);
        CakePlugin::load('TestLegacyPlugin');
        $name = 'test_local_source_and_plugin_driver';
        $config = ['datasource' => 'TestLegacyPlugin.Database/DboDummy'];

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
            'Plugin' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Plugin' . DS],
            'Model/Datasource/Database' => [
                CORE_TESTS . DS . 'test_legacy_app' . DS . 'Model' . DS . 'Datasource' . DS . 'Database' . DS,
            ],
        ]);
        CakePlugin::load('TestLegacyPlugin');

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
     * testConnectionData method
     *
     * @return void
     */
    public function testConnectionData(): void
    {
        App::build([
            'Plugin' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Plugin' . DS],
            'Model/Datasource' => [
                CORE_TESTS . DS . 'test_legacy_app' . DS . 'Model' . DS . 'Datasource' . DS,
            ],
        ], App::RESET);
        CakePlugin::load(['TestLegacyPlugin', 'TestTwoLegacyPlugin']);
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

        ConnectionManager::create('connection3', ['datasource' => 'TestLegacyPlugin.TestSource']);
        $connections = ConnectionManager::enumConnectionObjects();
        $expected['datasource'] = 'TestLegacyPlugin.TestSource';
        $this->assertEquals($expected, $connections['connection3']);
        ConnectionManager::drop('connection3');

        ConnectionManager::create('connection4', ['datasource' => 'TestLegacyPlugin.TestSource']);
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

        ConnectionManager::create('connection7', ['datasource' => 'TestLegacyPlugin.TestOtherSource']);
        $connections = ConnectionManager::enumConnectionObjects();
        $expected['datasource'] = 'TestLegacyPlugin.TestOtherSource';
        $this->assertEquals($expected, $connections['connection7']);
        ConnectionManager::drop('connection7');

        ConnectionManager::create('connection8', ['datasource' => 'TestLegacyPlugin.TestOtherSource']);
        $connections = ConnectionManager::enumConnectionObjects();
        $this->assertEquals($expected, $connections['connection8']);
        ConnectionManager::drop('connection8');
    }
}
