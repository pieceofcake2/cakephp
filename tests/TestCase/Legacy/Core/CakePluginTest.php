<?php
/**
 * CakePluginTest file.
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
 * @package       Cake.Test.Case.Core
 * @since         CakePHP(tm) v 2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Test\TestCase\Legacy\Core;

use Cake\Core\App;
use Cake\Core\CakePlugin;
use Cake\Core\Configure;
use Cake\Routing\Router;
use Cake\TestSuite\CakeTestCase;

/**
 * CakePluginTest class
 */
class CakePluginTest extends CakeTestCase
{
    /**
     * Sets the plugins folder for this test
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        App::build([
            'Plugin' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Plugin' . DS],
        ], App::RESET);
        App::objects('plugins', null, false);
    }

    /**
     * Reverts the changes done to the environment while testing
     *
     * @return void
     */
    public function tearDown(): void
    {
        CakePlugin::unload();

        parent::tearDown();
    }

    /**
     * Tests loading a plugin and its bootstrap file
     *
     * @return void
     */
    public function testLoadSingleWithBootstrap(): void
    {
        CakePlugin::load('TestLegacyPlugin', ['bootstrap' => true]);
        $this->assertTrue(CakePlugin::loaded('TestLegacyPlugin'));
        $this->assertEquals('loaded plugin bootstrap', Configure::read('CakePluginTest.test_legacy_plugin.bootstrap'));
    }

    /**
     * Tests loading a plugin with bootstrap file and routes file
     *
     * @return void
     */
    public function testLoadSingleWithBootstrapAndRoutes(): void
    {
        CakePlugin::load('TestLegacyPlugin', ['bootstrap' => true, 'routes' => true]);
        $this->assertTrue(CakePlugin::loaded('TestLegacyPlugin'));
        $this->assertEquals('loaded plugin bootstrap', Configure::read('CakePluginTest.test_legacy_plugin.bootstrap'));

        CakePlugin::routes();
        $this->assertEquals('loaded plugin routes', Configure::read('CakePluginTest.test_legacy_plugin.routes'));
    }

    /**
     * Tests loading the bootstrap and routes files from the config directory of a plugin
     *
     * @return void
     */
    public function testLoadWithConfigDirectory(): void
    {
        App::build([
            'Plugin' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'plugins' . DS],
        ]);
        CakePlugin::load('TestNamespacedPlugin', ['bootstrap' => true, 'routes' => true]);
        $this->assertSame('loaded from config/bootstrap.php', Configure::read('TestNamespacedPlugin.bootstrap'));

        Router::reload();
        CakePlugin::routes('TestNamespacedPlugin');
        $this->assertSame('/modern', Router::url(['plugin' => 'test_namespaced_plugin', 'controller' => 'modern', 'action' => 'index']));
    }

    /**
     * Tests configPath() with the config directory of the CakePHP 5 layout and the Config directory of CakePHP 2
     *
     * @return void
     */
    public function testConfigPath(): void
    {
        App::build([
            'Plugin' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'plugins' . DS],
        ]);
        CakePlugin::load(['TestLegacyPlugin', 'TestNamespacedPlugin']);

        $expected = CORE_TESTS . DS . 'test_legacy_app' . DS . 'Plugin' . DS . 'TestLegacyPlugin' . DS . 'Config' . DS;
        // Config/ is also found as config/ on case-insensitive file systems
        $this->assertEqualsIgnoringCase($expected, CakePlugin::configPath('TestLegacyPlugin'));

        $expected = CORE_TESTS . DS . 'test_legacy_app' . DS . 'plugins' . DS . 'TestNamespacedPlugin' . DS . 'config' . DS;
        $this->assertSame($expected, CakePlugin::configPath('TestNamespacedPlugin'));
    }

    /**
     * Tests that it is possible to load multiple bootstrap files at once
     *
     * @return void
     */
    public function testMultipleBootstrapFiles(): void
    {
        CakePlugin::load('TestLegacyPlugin', ['bootstrap' => ['bootstrap', 'custom_config']]);
        $this->assertTrue(CakePlugin::loaded('TestLegacyPlugin'));
        $this->assertEquals('loaded plugin bootstrap', Configure::read('CakePluginTest.test_legacy_plugin.bootstrap'));
    }

    /**
     * Tests that CakePlugin::path() returns the correct path for the loaded plugins
     *
     * @return void
     */
    public function testPath(): void
    {
        CakePlugin::load(['TestLegacyPlugin', 'TestTwoLegacyPlugin']);
        $expected = CORE_TESTS . DS . 'test_legacy_app' . DS . 'Plugin' . DS . 'TestLegacyPlugin' . DS;
        $this->assertEquals($expected, CakePlugin::path('TestLegacyPlugin'));

        $expected = CORE_TESTS . DS . 'test_legacy_app' . DS . 'Plugin' . DS . 'TestTwoLegacyPlugin' . DS;
        $this->assertEquals($expected, CakePlugin::path('TestTwoLegacyPlugin'));
    }

    /**
     * Tests that CakePlugin::loadAll() will load all plugins in the configured folder
     *
     * @return void
     */
    public function testLoadAll(): void
    {
        CakePlugin::loadAll();
        $expected = ['TestJsLegacyPlugin', 'TestLegacyPlugin', 'TestMergeVarLegacyPlugin', 'TestRegistryLegacyPlugin', 'TestTwoLegacyPlugin'];
        $this->assertEquals($expected, CakePlugin::loaded());
    }

    /**
     * Tests that CakePlugin::loadAll() will load all plugins in the configured folder with defaults
     * and merges in global defaults.
     *
     * @return void
     */
    public function testLoadAllWithDefaultsAndOverride(): void
    {
        CakePlugin::loadAll([['bootstrap' => true], 'TestLegacyPlugin' => ['routes' => true]]);
        CakePlugin::routes();

        $expected = ['TestJsLegacyPlugin', 'TestLegacyPlugin', 'TestMergeVarLegacyPlugin', 'TestRegistryLegacyPlugin', 'TestTwoLegacyPlugin'];
        $this->assertEquals($expected, CakePlugin::loaded());
        $this->assertEquals('loaded js plugin bootstrap', Configure::read('CakePluginTest.js_plugin.bootstrap'));
        $this->assertEquals('loaded plugin routes', Configure::read('CakePluginTest.test_legacy_plugin.routes'));
        $this->assertEquals('loaded plugin bootstrap', Configure::read('CakePluginTest.test_legacy_plugin.bootstrap'));
        $this->assertEquals('loaded plugin two bootstrap', Configure::read('CakePluginTest.test_two_legacy_plugin.bootstrap'));
    }
}
