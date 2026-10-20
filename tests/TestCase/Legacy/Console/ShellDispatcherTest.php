<?php
/**
 * ShellDispatcherTest file
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
 * @package       Cake.Test.Case.Console
 * @since         CakePHP(tm) v 1.2.0.5432
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Test\TestCase\Legacy\Console;

use Cake\Console\ShellDispatcher;
use Cake\Core\App;
use Cake\Core\CakePlugin;
use Cake\TestSuite\CakeTestCase;

/**
 * TestShellDispatcher class
 *
 * @package       Cake.Test.Case.Console
 */
class TestShellDispatcher extends ShellDispatcher
{
    /**
     * params property
     *
     * @var array
     */
    public $params = [];

    /**
     * stopped property
     *
     * @var string
     */
    public $stopped = null;

    /**
     * TestShell
     *
     * @var mixed
     */
    public $TestShell;

    /**
     * _initEnvironment method
     *
     * @return void
     */
    protected function _initEnvironment(): void
    {
    }

    /**
     * clear method
     *
     * @return void
     */
    public function clear(): void
    {
    }

    /**
     * _stop method
     *
     * @return void
     */
    protected function _stop($status = 0): mixed
    {
        $this->stopped = 'Stopped with status: ' . $status;

        return $status;
    }

    /**
     * @return array
     */
    public function getDefaults(): mixed
    {
        return $this->_getDefaults();
    }

    /**
     * getShell
     *
     * @param string $shell
     * @return mixed
     */
    public function getShell($shell): mixed
    {
        return $this->_getShell($shell);
    }

    /**
     * _getShell
     *
     * @param string $plugin
     * @return mixed
     */
    protected function _getShell($shell): mixed
    {
        return $this->TestShell ?? parent::_getShell($shell);
    }
}

/**
 * ShellDispatcherTest
 *
 * @package       Cake.Test.Case.Console
 */
class ShellDispatcherTest extends CakeTestCase
{
    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        App::build([
            'Plugin' => [
                CORE_TESTS . DS . 'test_legacy_app' . DS . 'Plugin' . DS,
            ],
            'Console/Command' => [
                CORE_TESTS . DS . 'test_legacy_app' . DS . 'Console' . DS . 'Command' . DS,
            ],
        ], App::RESET);
        CakePlugin::load('TestLegacyPlugin');
    }

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
     * Verify loading of (plugin-) shells
     *
     * @return void
     */
    public function testGetShell(): void
    {
        $this->skipIf(class_exists('SampleShell'), 'SampleShell Class already loaded.');
        $this->skipIf(class_exists('ExampleShell'), 'ExampleShell Class already loaded.');

        $Dispatcher = new TestShellDispatcher();

        $result = $Dispatcher->getShell('sample');
        $this->assertInstanceOf('SampleShell', $result);

        $Dispatcher = new TestShellDispatcher();
        $result = $Dispatcher->getShell('test_legacy_plugin.example');
        $this->assertInstanceOf('ExampleShell', $result);
        $this->assertEquals('TestLegacyPlugin', $result->plugin);
        $this->assertEquals('Example', $result->name);

        $Dispatcher = new TestShellDispatcher();
        $result = $Dispatcher->getShell('TestLegacyPlugin.example');
        $this->assertInstanceOf('ExampleShell', $result);

        $Dispatcher = new TestShellDispatcher();
        $result = $Dispatcher->getShell('test_legacy_plugin');
        $this->assertInstanceOf('TestLegacyPluginShell', $result);

        $Dispatcher = new TestShellDispatcher();
        $result = $Dispatcher->getShell('TestLegacyPlugin');
        $this->assertInstanceOf('TestLegacyPluginShell', $result);
    }
}
