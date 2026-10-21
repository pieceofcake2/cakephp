<?php
/**
 * ConfigureTest file
 *
 * Holds several tests
 *
 * CakePHP(tm) Tests <https://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.Test.Case.Core
 * @since         CakePHP(tm) v 1.2.0.5432
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Test\TestCase\Legacy\Core;

use Cake\Configure\PhpReader;
use Cake\Core\App;
use Cake\Core\CakePlugin;
use Cake\Core\Configure;
use Cake\TestSuite\CakeTestCase;

/**
 * ConfigureTest
 *
 * @package       Cake.Test.Case.Core
 */
class ConfigureTest extends CakeTestCase
{
    protected $_restoreError = false;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        Configure::write('Cache.disable', true);
        App::build();
        App::objects('plugin', null, true);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        if (file_exists(TMP . 'cache' . DS . 'persistent' . DS . 'cake_core_core_paths')) {
            unlink(TMP . 'cache' . DS . 'persistent' . DS . 'cake_core_core_paths');
        }
        if (file_exists(TMP . 'cache' . DS . 'persistent' . DS . 'cake_core_dir_map')) {
            unlink(TMP . 'cache' . DS . 'persistent' . DS . 'cake_core_dir_map');
        }
        if (file_exists(TMP . 'cache' . DS . 'persistent' . DS . 'cake_core_file_map')) {
            unlink(TMP . 'cache' . DS . 'persistent' . DS . 'cake_core_file_map');
        }
        if (file_exists(TMP . 'cache' . DS . 'persistent' . DS . 'cake_core_object_map')) {
            unlink(TMP . 'cache' . DS . 'persistent' . DS . 'cake_core_object_map');
        }
        if (file_exists(TMP . 'cache' . DS . 'persistent' . DS . 'test.config.php')) {
            unlink(TMP . 'cache' . DS . 'persistent' . DS . 'test.config.php');
        }
        if (file_exists(TMP . 'cache' . DS . 'persistent' . DS . 'test.php')) {
            unlink(TMP . 'cache' . DS . 'persistent' . DS . 'test.php');
        }
        Configure::drop('test');

        if ($this->_restoreError) {
            restore_error_handler();
        }

        parent::tearDown();
    }

    /**
     * testLoad method
     *
     * @return void
     */
    public function testLoadPlugin(): void
    {
        App::build([
            'Plugin' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Plugin' . DS],
        ], App::RESET);
        Configure::config('test', new PhpReader());
        CakePlugin::load('TestLegacyPlugin');
        $result = Configure::load('TestLegacyPlugin.load', 'test');
        $this->assertTrue($result);
        $expected = '/test_app/plugins/test_legacy_plugin/config/load.php';
        $config = Configure::read('plugin_load');
        $this->assertEquals($expected, $config);

        $result = Configure::load('TestLegacyPlugin.more.load', 'test');
        $this->assertTrue($result);
        $expected = '/test_app/plugins/test_legacy_plugin/config/more.load.php';
        $config = Configure::read('plugin_more_load');
        $this->assertEquals($expected, $config);
        CakePlugin::unload();
    }
}
