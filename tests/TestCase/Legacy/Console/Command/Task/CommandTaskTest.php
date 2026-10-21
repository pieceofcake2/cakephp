<?php
/**
 * CakePHP : Rapid Development Framework (https://cakephp.org)
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

namespace Cake\Test\TestCase\Legacy\Console\Command\Task;

use Cake\Core\App;
use Cake\Core\CakePlugin;
use Cake\TestSuite\CakeTestCase;

/**
 * CommandTaskTest class
 *
 * @package   Cake.Test.Case.Console.Command.Task
 */
class CommandTaskTest extends CakeTestCase
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

        $out = $this->getMock('ConsoleOutput', [], [], '', false);
        $in = $this->getMock('ConsoleInput', [], [], '', false);

        $this->CommandTask = $this->getMock(
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
        unset($this->CommandTask);
        CakePlugin::unload();

        parent::tearDown();
    }

    /**
     * Test the resulting list of shells
     *
     * @return void
     */
    public function testGetShellList(): void
    {
        $result = $this->CommandTask->getShellList();

        $expected = [
            'CORE' => [
                'acl',
                'api',
                'command_list',
                'completion',
                'console',
                'i18n',
                'schema',
                'server',
                'test',
                'testsuite',
            ],
            'TestLegacyPlugin' => [
                'example',
                'test_legacy_plugin',
            ],
            'TestTwoLegacyPlugin' => [
                'example',
                'welcome',
            ],
            'app' => [
                'sample',
            ],
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * Test the resulting list of commands
     *
     * @return void
     */
    public function testCommands(): void
    {
        $result = $this->CommandTask->commands();

        $expected = [
            'TestLegacyPlugin.example',
            'TestLegacyPlugin.test_legacy_plugin',
            'TestTwoLegacyPlugin.example',
            'TestTwoLegacyPlugin.welcome',
            'acl',
            'api',
            'command_list',
            'completion',
            'console',
            'i18n',
            'schema',
            'server',
            'test',
            'testsuite',
            'sample',
        ];
        $this->assertEquals($expected, $result);
    }
}
