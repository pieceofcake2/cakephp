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

use Cake\Console\Command\CommandListShell;
use Cake\Console\Command\Task\CommandTask;
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

    protected function _write($message): void
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
    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        Configure::write('App.namespace', 'TestApp');

        App::build([
            'Plugin' => [
                CORE_TESTS . DS . 'test_app' . DS . 'plugins' . DS,
            ],
            'Console/Command' => [
                CORE_TESTS . DS . 'test_app' . DS . 'src' . DS . 'Console' . DS . 'Command' . DS,
            ],
        ], App::RESET);
        CakePlugin::load(['TestPlugin', 'TestTwoPlugin']);

        $out = new TestStringOutput();
        $in = $this->getMock(ConsoleInput::class, [], [], '', false);

        $this->Shell = $this->getMock(
            CommandListShell::class,
            ['in', '_stop', 'clear'],
            [$out, $out, $in],
        );

        $this->Shell->Command = $this->getMock(
            CommandTask::class,
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
     * test that main finds core shells.
     *
     * @return void
     */
    public function testMain(): void
    {
        $this->Shell->main();
        $output = $this->Shell->stdout->output;

        $expected = "/\[.*TestPlugin.*\] example/";
        $this->assertMatchesRegularExpression($expected, $output);

        $expected = "/\[.*TestTwoPlugin.*\] example, welcome/";
        $this->assertMatchesRegularExpression($expected, $output);

        $expected = "/\[.*CORE.*\] acl, api, command_list, completion, console, i18n, schema, server, test, testsuite/";
        $this->assertMatchesRegularExpression($expected, $output);

        $expected = "/\[.*app.*\] namespaced, sample/";
        $this->assertMatchesRegularExpression($expected, $output);
    }

    /**
     * test xml output.
     *
     * @return void
     */
    public function testMainXml(): void
    {
        $this->Shell->params['xml'] = true;
        $this->Shell->main();

        $output = $this->Shell->stdout->output;

        $find = '<shell name="sample" call_as="sample" provider="app" help="sample -h"/>';
        $this->assertStringContainsString($find, $output);

        $find = '<shell name="welcome" call_as="TestTwoPlugin.welcome" provider="TestTwoPlugin" help="TestTwoPlugin.welcome -h"/>';
        $this->assertStringContainsString($find, $output);
    }

    /**
     * Test that startup() shows the welcome text, but not for the XML output
     *
     * @return void
     */
    public function testStartup(): void
    {
        $this->Shell->startup();
        $this->assertStringContainsString('Welcome to CakePHP', $this->Shell->stdout->output);

        $this->Shell->stdout->output = '';
        $this->Shell->params['xml'] = true;
        $this->Shell->startup();
        $this->assertSame('', $this->Shell->stdout->output);
    }

    /**
     * Test that the text output sorts the commands of each group
     *
     * @return void
     */
    public function testMainSortsCommands(): void
    {
        $this->Shell->Command = $this->getMock(CommandTask::class, ['getShellList'], [], '', false);
        $this->Shell->Command->expects($this->once())
            ->method('getShellList')
            ->will($this->returnValue([
                'CORE' => ['zeta', 'alpha', 'mu'],
                'app' => ['second', 'first'],
            ]));
        $this->Shell->stdout->outputAs(ConsoleOutput::PLAIN);

        $this->Shell->main();
        $output = $this->Shell->stdout->output;

        $this->assertStringContainsString('[CORE] alpha, mu, zeta', $output);
        $this->assertStringContainsString('[app] first, second', $output);
        $this->assertLessThan(strpos($output, '[app]'), strpos($output, '[CORE]'));
        $this->assertStringContainsString('To get help on a specific command, type cake shell_name --help', $output);
    }

    /**
     * Test that nothing is listed when there are no shells
     *
     * @return void
     */
    public function testMainEmptyShellList(): void
    {
        $this->Shell->Command = $this->getMock(CommandTask::class, ['getShellList'], [], '', false);
        $this->Shell->Command->expects($this->exactly(2))
            ->method('getShellList')
            ->will($this->returnValue([]));

        $this->Shell->main();
        $output = $this->Shell->stdout->output;
        $this->assertStringContainsString('Available Shells:', $output);
        $this->assertStringNotContainsString('To run an app or core command', $output);

        $this->Shell->stdout->output = '';
        $this->Shell->params['xml'] = true;
        $this->Shell->main();
        $this->assertSame('', $this->Shell->stdout->output);
    }

    /**
     * Test that the XML output prefixes the commands of loaded plugins only
     *
     * @return void
     */
    public function testMainXmlPluginPrefix(): void
    {
        $this->Shell->Command = $this->getMock(CommandTask::class, ['getShellList'], [], '', false);
        $this->Shell->Command->expects($this->once())
            ->method('getShellList')
            ->will($this->returnValue([
                'CORE' => ['acl'],
                'TestPlugin' => ['example'],
                'NotLoaded' => ['thing'],
            ]));
        $this->Shell->params['xml'] = true;

        $this->Shell->main();
        $output = $this->Shell->stdout->output;

        $this->assertStringStartsWith('<?xml version="1.0"?>', $output);
        $this->assertStringContainsString('<shell name="acl" call_as="acl" provider="CORE" help="acl -h"/>', $output);
        $this->assertStringContainsString('<shell name="example" call_as="TestPlugin.example" provider="TestPlugin" help="TestPlugin.example -h"/>', $output);
        $this->assertStringContainsString('<shell name="thing" call_as="thing" provider="NotLoaded" help="thing -h"/>', $output);
        $this->assertStringNotContainsString('Current Paths', $output);
    }

    /**
     * Test the option parser
     *
     * @return void
     */
    public function testGetOptionParser(): void
    {
        $parser = $this->Shell->getOptionParser();
        $options = $parser->options();

        $this->assertArrayHasKey('sort', $options);
        $this->assertArrayHasKey('xml', $options);
        $this->assertTrue($options['xml']->isBoolean());
        $this->assertTrue($options['sort']->isBoolean());
        $this->assertStringContainsString('Get the list of available shells for this CakePHP application.', $parser->help());
    }
}
