<?php
/**
 * PhpConfigReaderTest
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
 * @package       Cake.Test.Case.Configure
 * @since         CakePHP(tm) v 2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Test\TestCase\Legacy\Configure;

use Cake\Configure\PhpReader;
use Cake\Core\App;
use Cake\Core\CakePlugin;
use Cake\TestSuite\CakeTestCase;

/**
 * PhpReaderTest
 *
 * @package       Cake.Test.Case.Configure
 */
class PhpReaderTest extends CakeTestCase
{
    /**
     * Setup.
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->path = CORE_TESTS . DS . 'test_legacy_app' . DS . 'Config' . DS;
    }

    /**
     * Test reading from plugins.
     *
     * @return void
     */
    public function testReadPluginValue(): void
    {
        App::build([
            'Plugin' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Plugin' . DS],
        ], App::RESET);
        CakePlugin::load('TestLegacyPlugin');
        $reader = new PhpReader($this->path);
        $result = $reader->read('TestLegacyPlugin.load');
        $this->assertTrue(isset($result['plugin_load']));

        $result = $reader->read('TestLegacyPlugin.load.php');
        $this->assertTrue(isset($result['plugin_load']));
        CakePlugin::unload();
    }

    /**
     * Test reading a file from the config directory of a plugin with the CakePHP 5 layout.
     *
     * @return void
     */
    public function testReadPluginConfigDirectory(): void
    {
        App::build([
            'Plugin' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'plugins' . DS],
        ], App::RESET);
        CakePlugin::load('TestNamespacedPlugin');
        $reader = new PhpReader($this->path);
        $result = $reader->read('TestNamespacedPlugin.modern_config');
        $this->assertSame('read from config/modern_config.php', $result['ModernConfig']);
        CakePlugin::unload();
    }
}
