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

namespace Cake\Test\TestCase\Legacy\Console\Command;

use Cake\Console\ConsoleOutput;
use Cake\Core\App;
use Cake\Core\CakePlugin;
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
        App::build([
            'Plugin' => [
                CORE_TESTS . DS . 'test_legacy_app' . DS . 'Plugin' . DS,
            ],
            'Console/Command' => [
                CORE_TESTS . DS . 'test_legacy_app' . DS . 'Console' . DS . 'Command' . DS,
            ],
        ], App::RESET);
        CakePlugin::load(['TestLegacyPlugin', 'TestTwoLegacyPlugin']);

        $out = new TestStringOutput();
        $in = $this->getMock('ConsoleInput', [], [], '', false);

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

        $expected = "/\[.*TestLegacyPlugin.*\] example/";
        $this->assertMatchesRegularExpression($expected, $output);

        $expected = "/\[.*TestTwoLegacyPlugin.*\] example, welcome/";
        $this->assertMatchesRegularExpression($expected, $output);

        $expected = "/\[.*CORE.*\] acl, api, command_list, completion, console, i18n, schema, server, test, testsuite/";
        $this->assertMatchesRegularExpression($expected, $output);

        $expected = "/\[.*app.*\] sample/";
        $this->assertMatchesRegularExpression($expected, $output);
    }
}
