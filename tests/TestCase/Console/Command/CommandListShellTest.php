<?php
/**
 * CommandListShellTest file
 *
 * CakePHP :  Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP Project
 * @package       Cake.Test.Case.Console.Command
 * @since         CakePHP v 2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Test\TestCase\Console\Command;

use Cake\Console\ConsoleInput;
use Cake\Console\ConsoleOutput;
use Cake\Core\App;
use Cake\Core\CakePlugin;
use Cake\Core\Configure;
use Cake\TestSuite\CakeTestCase;

/**
 * TestStringOutput
 *
 * @package       Cake.Test.Case.Console.Command
 */
class TestStringOutput extends ConsoleOutput
{
    public $output = '';

    protected function _write($message)
    {
        $this->output .= $message;
    }
}

/**
 * CommandListShellTest
 *
 * @package       Cake.Test.Case.Console.Command
 */
class CommandListShellTest extends CakeTestCase
{
    protected $_appNamespace = null;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->_appNamespace = Configure::read('App.namespace');
        Configure::write('App.namespace', 'TestApp');

        App::build([
            'Plugin' => [
                CORE_TESTS . DS . 'test_app' . DS . 'plugins' . DS,
            ],
            'Console/Command' => [
                CORE_TESTS . DS . 'test_app' . DS . 'Console' . DS . 'Command' . DS,
            ],
        ], App::RESET);
        CakePlugin::load(['TestPlugin', 'TestPluginTwo']);

        $out = new TestStringOutput();
        $in = $this->getMock(ConsoleInput::class, [], [], '', false);

        $this->Shell = $this->getMock(
            'CommandListShell',
            ['in', '_stop', 'clear'],
            [$out, $out, $in],
        );

        $this->Shell->Command = $this->getMock(
            'CommandTask',
            ['in', '_stop', 'clear'],
            [$out, $out, $in],
        );
    }

    /**
     * tearDown
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->Shell);
        CakePlugin::unload();

        Configure::write('App.namespace', $this->_appNamespace);

        parent::tearDown();
    }

    /**
     * test that main finds core shells.
     *
     * @return void
     */
    public function testMain()
    {
        $this->Shell->main();
        $output = $this->Shell->stdout->output;

        $expected = "/\[.*TestPlugin.*\] example/";
        $this->assertMatchesRegularExpression($expected, $output);

        $expected = "/\[.*TestPluginTwo.*\] example, welcome/";
        $this->assertMatchesRegularExpression($expected, $output);

        $expected = "/\[.*CORE.*\] acl, api, command_list, completion, console, i18n, schema, server, test, testsuite/";
        $this->assertMatchesRegularExpression($expected, $output);

        $expected = "/\[.*app.*\] sample/";
        $this->assertMatchesRegularExpression($expected, $output);
    }

    /**
     * test xml output.
     *
     * @return void
     */
    public function testMainXml()
    {
        $this->Shell->params['xml'] = true;
        $this->Shell->main();

        $output = $this->Shell->stdout->output;

        $find = '<shell name="sample" call_as="sample" provider="app" help="sample -h"/>';
        $this->assertStringContainsString($find, $output);

        $find = '<shell name="welcome" call_as="TestPluginTwo.welcome" provider="TestPluginTwo" help="TestPluginTwo.welcome -h"/>';
        $this->assertStringContainsString($find, $output);
    }
}
