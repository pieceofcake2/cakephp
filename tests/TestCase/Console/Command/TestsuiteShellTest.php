<?php
/**
 * TestsuiteShellTest file
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
 * @package       Cake.Test.Case.Console.Command
 * @since         CakePHP(tm) v 2.13
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Test\TestCase\Console\Command;

use Cake\Console\Command\TestShell;
use Cake\Console\Command\TestsuiteShell;
use Cake\Console\ConsoleInput;
use Cake\Console\ConsoleOutput;
use Cake\TestSuite\CakeTestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * TestTestsuiteShell
 *
 * @package       Cake.Test.Case.Console.Command
 */
class TestTestsuiteShell extends TestsuiteShell
{
    public function parseArgs(): ?array
    {
        return $this->_parseArgs();
    }
}

/**
 * TestsuiteShellTest
 *
 * @package       Cake.Test.Case.Console.Command
 */
class TestsuiteShellTest extends CakeTestCase
{
    /**
     * The shell under test
     *
     * @var TestTestsuiteShell&MockObject
     */
    public $Shell;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $out = $this->getMock(ConsoleOutput::class, [], [], '', false);
        $in = $this->getMock(ConsoleInput::class, [], [], '', false);

        $this->Shell = $this->getMock(
            TestTestsuiteShell::class,
            ['in', 'out', 'hr', 'err', '_stop', 'initialize', '_run', 'available'],
            [$out, $out, $in],
        );
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->Shell);

        parent::tearDown();
    }

    /**
     * Test that the shell is the test shell
     *
     * @return void
     */
    public function testIsTestShell(): void
    {
        $this->assertInstanceOf(TestShell::class, $this->Shell);
    }

    /**
     * Test the option parser
     *
     * @return void
     */
    public function testGetOptionParser(): void
    {
        $parser = $this->Shell->getOptionParser();

        $description = $parser->description();
        $this->assertStringContainsString('The CakePHP Testsuite allows you to run test cases from the command line', $description);
        $this->assertStringContainsString('This shell is for backwards-compatibility only', $description);
        // The options of the test shell are kept
        $this->assertArrayHasKey('filter', $parser->options());
        $arguments = $parser->arguments();
        $this->assertSame('category', $arguments[0]->name());
        $this->assertSame('file', $arguments[1]->name());
    }

    /**
     * Test the argument mapping without arguments
     *
     * @return void
     */
    public function testParseArgsEmpty(): void
    {
        $this->Shell->args = [];

        $this->assertNull($this->Shell->parseArgs());
    }

    /**
     * Test the argument mapping of the categories
     *
     * @return void
     */
    public function testParseArgs(): void
    {
        $this->Shell->args = ['core', 'Basics'];
        $expected = ['core' => true, 'app' => false, 'plugin' => null, 'output' => 'text', 'case' => 'Basics'];
        $this->assertSame($expected, $this->Shell->parseArgs());

        $this->Shell->args = ['app'];
        $expected = ['core' => false, 'app' => true, 'plugin' => null, 'output' => 'text'];
        $this->assertSame($expected, $this->Shell->parseArgs());

        $this->Shell->args = ['TestPlugin', 'Model/TestPluginPost'];
        $expected = [
            'core' => false,
            'app' => false,
            'plugin' => 'TestPlugin',
            'output' => 'text',
            'case' => 'Model/TestPluginPost',
        ];
        $this->assertSame($expected, $this->Shell->parseArgs());
    }

    /**
     * Test that a file path is taken as a plugin name, unlike the test shell
     *
     * @return void
     */
    public function testParseArgsDoesNotMapFiles(): void
    {
        $this->Shell->args = ['tests/TestCase/BasicsTest.php'];
        $expected = ['core' => false, 'app' => false, 'plugin' => 'tests/TestCase/BasicsTest.php', 'output' => 'text'];
        $this->assertSame($expected, $this->Shell->parseArgs());
    }

    /**
     * Test that main() runs the given test case
     *
     * @return void
     */
    public function testMainRunsCase(): void
    {
        $this->Shell->args = ['core', 'Basics'];
        $this->Shell->params = ['filter' => 'myFilter', 'verbose' => true];

        $this->Shell->expects($this->once())->method('out')->with('CakePHP Test Shell');
        $this->Shell->expects($this->once())->method('hr');
        $this->Shell->expects($this->never())->method('available');
        $this->Shell->expects($this->once())
            ->method('_run')
            ->with(
                ['core' => true, 'app' => false, 'plugin' => null, 'output' => 'text', 'case' => 'Basics'],
                ['--filter', 'myFilter', '--verbose', '--colors'],
            );
        $this->Shell->main();
    }

    /**
     * Test that main() lists the test cases when no case is given
     *
     * @return void
     */
    public function testMainWithoutCase(): void
    {
        $this->Shell->args = ['app'];

        $this->Shell->expects($this->once())->method('available');
        $this->Shell->expects($this->never())->method('_run');
        $this->Shell->main();
    }

    /**
     * Test that main() lists the test cases without arguments
     *
     * @return void
     */
    public function testMainWithoutArguments(): void
    {
        $this->Shell->expects($this->once())->method('available');
        $this->Shell->expects($this->never())->method('_run');
        $this->Shell->main();
    }
}
