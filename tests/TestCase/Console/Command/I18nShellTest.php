<?php
/**
 * I18nShellTest file
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

use Cake\Console\Command\I18nShell;
use Cake\Console\Command\Task\ExtractTask;
use Cake\Console\ConsoleInput;
use Cake\Console\ConsoleOutput;
use Cake\TestSuite\CakeTestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * I18nShellTest
 *
 * @package       Cake.Test.Case.Console.Command
 */
class I18nShellTest extends CakeTestCase
{
    /**
     * The shell under test
     *
     * @var I18nShell&MockObject
     */
    public $Shell;

    /**
     * The lines the shell printed with out()
     *
     * @var array
     */
    public $outputs = [];

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
            I18nShell::class,
            ['in', 'out', 'err', 'hr', '_stop', 'dispatchShell'],
            [$out, $out, $in],
        );
        $this->Shell->loadTasks();
        $this->Shell->Extract = $this->getMock(
            ExtractTask::class,
            ['execute', 'runCommand', 'in', 'out', 'err', '_stop'],
            [$out, $out, $in],
        );

        $this->outputs = [];
        $this->Shell->expects($this->any())
            ->method('out')
            ->willReturnCallback(function ($message = null) {
                $this->outputs[] = $message;

                return 0;
            });
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
     * Test that startup() takes the datasource and finds the database config
     *
     * @return void
     */
    public function testStartup(): void
    {
        $this->Shell->params['datasource'] = 'test';
        $this->Shell->command = 'initdb';
        $this->Shell->expects($this->never())->method('err');
        $this->Shell->expects($this->never())->method('_stop');
        $this->Shell->startup();

        $this->assertSame('test', $this->Shell->dataSource);
        $this->assertStringContainsString('Welcome to CakePHP', $this->outputs[1]);
    }

    /**
     * Test that startup() keeps the default datasource
     *
     * @return void
     */
    public function testStartupDefaultDatasource(): void
    {
        $this->Shell->command = 'help';
        $this->Shell->expects($this->never())->method('err');
        $this->Shell->startup();

        $this->assertSame('default', $this->Shell->dataSource);
    }

    /**
     * Test the option parser
     *
     * @return void
     */
    public function testGetOptionParser(): void
    {
        $parser = $this->Shell->getOptionParser();

        $this->assertStringContainsString('I18n Shell initializes i18n database table', $parser->description());
        $subcommands = $parser->subcommands();
        $this->assertSame(['initdb', 'extract'], array_keys($subcommands));
        $this->assertStringContainsString('Initialize the i18n table.', $subcommands['initdb']->help());
        $this->assertNotNull($subcommands['extract']->parser());
    }

    /**
     * Test that initdb creates the i18n table with the schema shell
     *
     * @return void
     */
    public function testInitdb(): void
    {
        $this->Shell->expects($this->once())
            ->method('dispatchShell')
            ->with('schema create i18n');
        $this->Shell->initdb();
    }

    /**
     * Test the extract choice of the menu
     *
     * @return void
     */
    public function testMainExtract(): void
    {
        $this->Shell->expects($this->exactly(2))
            ->method('in')
            ->with('What would you like to do?', ['E', 'I', 'H', 'Q'])
            ->willReturnOnConsecutiveCalls('E', 'q');
        $this->Shell->Extract->expects($this->once())->method('execute');
        $this->Shell->expects($this->never())->method('dispatchShell');
        $this->Shell->expects($this->once())->method('_stop');
        $this->Shell->main();

        $this->assertSame('<info>I18n Shell</info>', $this->outputs[0]);
        $this->assertSame('[E]xtract POT file from sources', $this->outputs[1]);
        $this->assertSame('[Q]uit', $this->outputs[4]);
    }

    /**
     * Test the initdb choice of the menu
     *
     * @return void
     */
    public function testMainInitdb(): void
    {
        $this->Shell->expects($this->exactly(2))
            ->method('in')
            ->willReturnOnConsecutiveCalls('i', 'Q');
        $this->Shell->Extract->expects($this->never())->method('execute');
        $this->Shell->expects($this->once())
            ->method('dispatchShell')
            ->with('schema create i18n');
        $this->Shell->main();
    }

    /**
     * Test the help choice of the menu
     *
     * @return void
     */
    public function testMainHelp(): void
    {
        $this->Shell->OptionParser = $this->Shell->getOptionParser();
        $this->Shell->expects($this->exactly(2))
            ->method('in')
            ->willReturnOnConsecutiveCalls('h', 'q');
        $this->Shell->main();

        $this->assertSame($this->Shell->OptionParser->help(), $this->outputs[5]);
    }

    /**
     * Test an invalid choice of the menu
     *
     * @return void
     */
    public function testMainInvalidChoice(): void
    {
        $this->Shell->expects($this->exactly(2))
            ->method('in')
            ->willReturnOnConsecutiveCalls('x', 'q');
        $this->Shell->expects($this->once())->method('_stop');
        $this->Shell->main();

        $this->assertSame(
            'You have made an invalid selection. Please choose a command to execute by entering E, I, H, or Q.',
            $this->outputs[5],
        );
    }

    /**
     * Test that runCommand() sends initdb to its method
     *
     * @return void
     */
    public function testRunCommandInitdb(): void
    {
        $this->Shell->expects($this->once())
            ->method('dispatchShell')
            ->with('schema create i18n');
        $this->Shell->runCommand('initdb', ['initdb']);

        $this->assertSame('initdb', $this->Shell->command);
    }

    /**
     * Test that runCommand() sends extract to the task
     *
     * @return void
     */
    public function testRunCommandExtract(): void
    {
        $this->Shell->Extract->expects($this->once())
            ->method('runCommand')
            ->with('execute', ['--paths', '/tmp'])
            ->willReturn(true);
        $this->Shell->expects($this->never())->method('dispatchShell');

        $this->assertTrue($this->Shell->runCommand('extract', ['extract', '--paths', '/tmp']));
    }
}
