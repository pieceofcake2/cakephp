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
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Test\TestCase\Console\Command;

use Cake\Console\Command\ServerShell;
use Cake\Console\ConsoleInput;
use Cake\Console\ConsoleOutput;
use Cake\TestSuite\CakeTestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * ServerShellTest
 *
 * @package       Cake.Test.Case.Console.Command
 */
class ServerShellTest extends CakeTestCase
{
    /**
     * @var ServerShell&MockObject
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
            ServerShell::class,
            ['in', 'out', 'err', 'hr', '_stop', '_welcome', '_runCommand'],
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
     * Test that the server listens on localhost:8765 by default
     *
     * @return void
     */
    public function testMainDefaults(): void
    {
        $this->assertSame(8765, ServerShell::DEFAULT_PORT);

        $this->Shell->initialize();
        $this->Shell->startup();

        $this->Shell->expects($this->once())
            ->method('out')
            ->with('built-in server is running in http://localhost:8765/');
        $this->Shell->expects($this->once())
            ->method('_runCommand')
            ->with($this->stringStartsWith('php -S localhost:8765 -t '));

        $this->Shell->main();
    }

    /**
     * Test the host and port options
     *
     * @return void
     */
    public function testMainHostAndPort(): void
    {
        $this->Shell->initialize();
        $this->Shell->params = ['host' => '0.0.0.0', 'port' => '8080'];
        $this->Shell->startup();

        $this->Shell->expects($this->once())
            ->method('out')
            ->with('built-in server is running in http://0.0.0.0:8080/');
        $this->Shell->expects($this->once())
            ->method('_runCommand')
            ->with($this->stringStartsWith('php -S 0.0.0.0:8080 -t '));

        $this->Shell->main();
    }

    /**
     * Test that port 80 is omitted from the URL
     *
     * @return void
     */
    public function testMainPort80(): void
    {
        $this->Shell->initialize();
        $this->Shell->params = ['port' => '80'];
        $this->Shell->startup();

        $this->Shell->expects($this->once())
            ->method('out')
            ->with('built-in server is running in http://localhost/');
        $this->Shell->expects($this->once())
            ->method('_runCommand')
            ->with($this->stringStartsWith('php -S localhost:80 -t '));

        $this->Shell->main();
    }

    /**
     * Test that the help shows the default host and port
     *
     * @return void
     */
    public function testOptionParserHelp(): void
    {
        $help = $this->Shell->getOptionParser()->help();

        $this->assertStringContainsString('ServerHost (default: localhost)', $help);
        $this->assertStringContainsString('ListenPort (default: 8765)', $help);
    }

    /**
     * Test the document root option and that a trailing separator is removed
     *
     * @return void
     */
    public function testMainDocumentRoot(): void
    {
        $this->Shell->initialize();
        $this->Shell->params = ['document_root' => DS . 'var' . DS . 'www' . DS];
        $this->Shell->startup();

        $root = DS . 'var' . DS . 'www';
        $this->Shell->expects($this->once())
            ->method('_runCommand')
            ->with('php -S localhost:8765 -t ' . escapeshellarg($root) . ' ' . escapeshellarg($root . '/index.php'));

        $this->Shell->main();
    }

    /**
     * Test that a Windows document root keeps one backslash after the drive
     *
     * @return void
     */
    public function testMainWindowsDocumentRoot(): void
    {
        $this->Shell->initialize();
        $this->Shell->params = ['document_root' => 'C:\\\\www\\app'];
        $this->Shell->startup();

        $this->Shell->expects($this->once())
            ->method('_runCommand')
            ->with($this->stringStartsWith('php -S localhost:8765 -t ' . escapeshellarg('C:\\www\\app') . ' '));

        $this->Shell->main();
    }

    /**
     * Test that the welcome text shows the document root
     *
     * @return void
     */
    public function testStartupWelcome(): void
    {
        $out = $this->getMock(ConsoleOutput::class, [], [], '', false);
        $in = $this->getMock(ConsoleInput::class, [], [], '', false);
        $Shell = $this->getMock(ServerShell::class, ['out', 'hr', '_stop', '_runCommand'], [$out, $out, $in]);

        $outputs = [];
        $Shell->expects($this->any())
            ->method('out')
            ->willReturnCallback(function ($message = null) use (&$outputs) {
                $outputs[] = $message;

                return 0;
            });
        $Shell->expects($this->exactly(2))->method('hr');

        $Shell->initialize();
        $Shell->params = ['document_root' => DS . 'srv' . DS . 'webroot'];
        $Shell->startup();

        $this->assertNull($outputs[0]);
        $this->assertStringStartsWith('<info>Welcome to CakePHP v', $outputs[1]);
        $this->assertSame('App : ' . APP_DIR, $outputs[2]);
        $this->assertSame('Path: ' . APP, $outputs[3]);
        $this->assertSame('DocumentRoot: ' . DS . 'srv' . DS . 'webroot', $outputs[4]);
        $this->assertCount(5, $outputs);
    }
}
