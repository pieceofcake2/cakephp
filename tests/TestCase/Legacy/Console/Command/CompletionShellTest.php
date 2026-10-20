<?php
/**
 * CompletionShellTest file
 *
 * PHP 5
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
 * @since         CakePHP v 2.5
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Test\TestCase\Legacy\Console\Command;

use Cake\Console\ConsoleOutput;
use Cake\Core\App;
use Cake\Core\CakePlugin;
use Cake\TestSuite\CakeTestCase;

/**
 * TestCompletionStringOutput
 *
 * @package       Cake.Test.Case.Console.Command
 */
class TestCompletionStringOutput extends ConsoleOutput
{
    public $output = '';

    protected function _write($message): void
    {
        $this->output .= $message;
    }
}

/**
 * CompletionShellTest
 *
 * @package       Cake.Test.Case.Console.Command
 */
class CompletionShellTest extends CakeTestCase
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
        CakePlugin::load(['TestLegacyPlugin', 'TestTwoLegacyPlugin']);

        $out = new TestCompletionStringOutput();
        $in = $this->getMock('ConsoleInput', [], [], '', false);

        $this->Shell = $this->getMock(
            'CompletionShell',
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

        parent::tearDown();
    }

    /**
     * test commands method that list all available commands
     *
     * @return void
     */
    public function testCommands(): void
    {
        $this->Shell->runCommand('commands', []);
        $output = $this->Shell->stdout->output;

        $expected = "TestLegacyPlugin.example TestLegacyPlugin.test_legacy_plugin TestTwoLegacyPlugin.example TestTwoLegacyPlugin.welcome acl api command_list completion console i18n schema server test testsuite sample\n";
        $this->assertEquals($expected, $output);
    }

    /**
     * test that subCommands with a existing APP command returns the proper sub commands (in this case none)
     *
     * @return void
     */
    public function testSubCommandsAppPlugin(): void
    {
        $this->Shell->runCommand('subCommands', ['subCommands', 'app.sample']);
        $output = $this->Shell->stdout->output;

        $expected = '';
        $this->assertEquals($expected, $output);
    }

    /**
     * test that subCommands with a existing plugin command returns the proper sub commands
     *
     * @return void
     */
    public function testSubCommandsPlugin(): void
    {
        $this->Shell->runCommand('subCommands', ['subCommands', 'TestTwoLegacyPlugin.welcome']);
        $output = $this->Shell->stdout->output;

        $expected = "say_hello\n";
        $this->assertEquals($expected, $output);
    }
}
