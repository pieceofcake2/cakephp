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

namespace Cake\Test\TestCase\Console\Command;

use Cake\Console\ConsoleInput;
use Cake\Console\ConsoleOutput;
use Cake\Core\App;
use Cake\Core\CakePlugin;
use Cake\Core\Configure;
use Cake\TestSuite\CakeTestCase;

/**
 * TestCompletionStringOutput
 *
 * @package       Cake.Test.Case.Console.Command
 */
class TestCompletionStringOutput extends ConsoleOutput
{
    public $output = '';

    protected function _write(string $message): int|false
    {
        $this->output .= $message;

        return 0;
    }
}

/**
 * CompletionShellTest
 *
 * @package       Cake.Test.Case.Console.Command
 */
class CompletionShellTest extends CakeTestCase
{
    protected ?string $_appNamespace = null;

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
                CORE_TESTS . DS . 'test_app' . DS . 'src' . DS . 'Console' . DS . 'Command' . DS,
            ],
        ], App::RESET);
        CakePlugin::load(['TestPlugin', 'TestPluginTwo']);

        $out = new TestCompletionStringOutput();
        $in = $this->getMock(ConsoleInput::class, [], [], '', false);

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
        Configure::write('App.namespace', $this->_appNamespace);

        parent::tearDown();
    }

    /**
     * test that the startup method supresses the shell header
     *
     * @return void
     */
    public function testStartup()
    {
        $this->Shell->runCommand('main', []);
        $output = $this->Shell->stdout->output;

        $needle = 'Welcome to CakePHP';
        $this->assertTextNotContains($needle, $output);
    }

    /**
     * test that main displays a warning
     *
     * @return void
     */
    public function testMain()
    {
        $this->Shell->runCommand('main', []);
        $output = $this->Shell->stdout->output;

        $expected = '/This command is not intended to be called manually/';
        $this->assertMatchesRegularExpression($expected, $output);
    }

    /**
     * test commands method that list all available commands
     *
     * @return void
     */
    public function testCommands()
    {
        $this->Shell->runCommand('commands', []);
        $output = $this->Shell->stdout->output;

        $expected = "TestPlugin.example TestPlugin.test_plugin TestPluginTwo.example TestPluginTwo.welcome acl api command_list completion console i18n schema server test testsuite sample\n";
        $this->assertEquals($expected, $output);
    }

    /**
     * test that options without argument returns the default options
     *
     * @return void
     */
    public function testOptionsNoArguments()
    {
        $this->Shell->runCommand('options', []);
        $output = $this->Shell->stdout->output;

        $expected = "--help -h --verbose -v --quiet -q\n";
        $this->assertEquals($expected, $output);
    }

    /**
     * test that options with a nonexisting command returns the default options
     *
     * @return void
     */
    public function testOptionsNonExistingCommand()
    {
        $this->Shell->runCommand('options', ['options', 'foo']);
        $output = $this->Shell->stdout->output;

        $expected = "--help -h --verbose -v --quiet -q\n";
        $this->assertEquals($expected, $output);
    }

    /**
     * test that options with a existing command returns the proper options
     *
     * @return void
     */
    public function testOptions()
    {
        $this->Shell->runCommand('options', ['options', 'schema']);
        $output = $this->Shell->stdout->output;

        $expected = "--help -h --verbose -v --quiet -q\n";
        $this->assertEquals($expected, $output);
    }

    /**
     * test that subCommands with a existing CORE command returns the proper sub commands
     *
     * @return void
     */
    public function testSubCommandsCorePlugin()
    {
        $this->Shell->runCommand('subCommands', ['subCommands', 'CORE.schema']);
        $output = $this->Shell->stdout->output;

        $expected = "create dump generate update view\n";
        $this->assertEquals($expected, $output);
    }

    /**
     * test that subCommands with a existing APP command returns the proper sub commands (in this case none)
     *
     * @return void
     */
    public function testSubCommandsAppPlugin()
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
    public function testSubCommandsPlugin()
    {
        $this->Shell->runCommand('subCommands', ['subCommands', 'TestPluginTwo.welcome']);
        $output = $this->Shell->stdout->output;

        $expected = "say_hello\n";
        $this->assertEquals($expected, $output);
    }

    /**
     * test that subcommands without arguments returns nothing
     *
     * @return void
     */
    public function testSubCommandsNoArguments()
    {
        $this->Shell->runCommand('subCommands', []);
        $output = $this->Shell->stdout->output;

        $expected = '';
        $this->assertEquals($expected, $output);
    }

    /**
     * test that subcommands with a nonexisting command returns nothing
     *
     * @return void
     */
    public function testSubCommandsNonExistingCommand()
    {
        $this->Shell->runCommand('subCommands', ['subCommands', 'foo']);
        $output = $this->Shell->stdout->output;

        $expected = '';
        $this->assertEquals($expected, $output);
    }

    /**
     * test that subcommands returns the available subcommands for the given command
     *
     * @return void
     */
    public function testSubCommands()
    {
        $this->Shell->runCommand('subCommands', ['subCommands', 'schema']);
        $output = $this->Shell->stdout->output;

        $expected = "create dump generate update view\n";
        $this->assertEquals($expected, $output);
    }

    /**
     * test that fuzzy returns nothing
     *
     * @return void
     */
    public function testFuzzy()
    {
        $this->Shell->runCommand('fuzzy', []);
        $output = $this->Shell->stdout->output;

        $expected = '';
        $this->assertEquals($expected, $output);
    }
}
