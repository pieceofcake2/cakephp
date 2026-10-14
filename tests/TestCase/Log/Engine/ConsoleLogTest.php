<?php
/**
 * ConsoleLogTest file
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
 * @package       Cake.Test.Case.Log.Engine
 * @since         CakePHP(tm) v 1.3
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

/**
 * TestConsoleLog
 *
 * @package       Cake.Test.Case.Log.Engine
 */
class TestConsoleLog extends ConsoleLog
{
}

/**
 * TestCakeLog
 *
 * @package       Cake.Test.Case.Log.Engine
 */
class TestCakeLog extends CakeLog
{
    public static function replace($key, &$engine)
    {
        static::$_Collection->{$key} = $engine;
    }
}

/**
 * ConsoleLogTest class
 *
 * @package       Cake.Test.Case.Log.Engine
 */
class ConsoleLogTest extends CakeTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        CakeLog::config('debug', [
            'engine' => 'File',
            'types' => ['notice', 'info', 'debug'],
            'file' => 'debug',
        ]);
        CakeLog::config('error', [
            'engine' => 'File',
            'types' => ['error', 'warning'],
            'file' => 'error',
        ]);
    }

    public function tearDown(): void
    {
        if (file_exists(LOGS . 'error.log')) {
            unlink(LOGS . 'error.log');
        }
        if (file_exists(LOGS . 'debug.log')) {
            unlink(LOGS . 'debug.log');
        }

        parent::tearDown();
    }

    /**
     * Test writing to ConsoleOutput
     *
     * @return void
     */
    public function testConsoleOutputWrites()
    {
        TestCakeLog::config('test_console_log', [
            'engine' => 'TestConsole',
            ]);

        $mock = $this->getMock('TestConsoleLog', ['write'], [
            ['types' => 'error'],
            ]);
        TestCakeLog::replace('test_console_log', $mock);

        $message = 'Test error message';
        $mock->expects($this->once())
            ->method('write');
        TestCakeLog::write(LOG_ERR, $message);
    }

    /**
     * Test logging to both ConsoleLog and FileLog
     *
     * @return void
     */
    public function testCombinedLogWriting()
    {
        TestCakeLog::config('test_console_log', [
            'engine' => 'TestConsole',
            ]);
        $mock = $this->getMock('TestConsoleLog', ['write'], [
            ['types' => 'error'],
            ]);
        TestCakeLog::replace('test_console_log', $mock);

        // log to both file and console
        $message = 'Test error message';
        $mock->expects($this->once())
            ->method('write');
        TestCakeLog::write(LOG_ERR, $message);
        $this->assertTrue(file_exists(LOGS . 'error.log'), 'error.log missing');
        $logOutput = file_get_contents(LOGS . 'error.log');
        $this->assertStringContainsString($message, $logOutput);

        // TestConsoleLog is only interested in `error` type
        $message = 'Test info message';
        $mock->expects($this->never())
            ->method('write');
        TestCakeLog::write(LOG_INFO, $message);

        // checks that output is correctly written in the correct logfile
        $this->assertTrue(file_exists(LOGS . 'error.log'), 'error.log missing');
        $this->assertTrue(file_exists(LOGS . 'debug.log'), 'debug.log missing');
        $logOutput = file_get_contents(LOGS . 'error.log');
        $this->assertStringNotContainsString($message, $logOutput);
        $logOutput = file_get_contents(LOGS . 'debug.log');
        $this->assertStringContainsString($message, $logOutput);
    }

    /**
     * test default value of stream 'outputAs'
     *
     * @return void
     */
    public function testDefaultOutputAs()
    {
        TestCakeLog::config('test_console_log', [
            'engine' => 'TestConsole',
            ]);
        if (
            (DS === '\\' && !(bool)env('ANSICON') && env('ConEmuANSI') !== 'ON') ||
            !stream_isatty(STDERR)
        ) {
            $expected = ConsoleOutput::PLAIN;
        } else {
            $expected = ConsoleOutput::COLOR;
        }
        $stream = TestCakeLog::stream('test_console_log');
        $config = $stream->config();
        $this->assertEquals($expected, $config['outputAs']);
    }

    /**
     * Test that a stream which is not a terminal gets plain output, regardless of STDIN.
     *
     * @return void
     */
    public function testOutputAsPlainForNonTerminalStream()
    {
        $file = TMP . 'console_log_test.log';
        try {
            $log = new ConsoleLog(['stream' => $file]);
            $config = $log->config();
            $this->assertSame(ConsoleOutput::PLAIN, $config['outputAs']);

            $log->write('error', 'Plain message');
            unset($log);
            $this->assertStringNotContainsString("\033[", file_get_contents($file));
        } finally {
            if (file_exists($file)) {
                unlink($file);
            }
        }

        $log = new ConsoleLog(['stream' => 'php://memory']);
        $config = $log->config();
        $this->assertSame(ConsoleOutput::PLAIN, $config['outputAs']);
    }

    /**
     * Test that the output type of a given ConsoleOutput is decided by that output, not by STDIN.
     *
     * @return void
     */
    public function testOutputAsFollowsGivenConsoleOutput()
    {
        $this->skipIf(DS === '\\', 'Windows consoles without ANSI support are always plain.');

        $output = new ConsoleOutput('php://memory');
        $output->outputAs(ConsoleOutput::COLOR);
        $log = new ConsoleLog(['stream' => $output]);
        $config = $log->config();
        $this->assertSame(ConsoleOutput::COLOR, $config['outputAs']);
        $this->assertSame(ConsoleOutput::COLOR, $output->outputAs());
    }

    /**
     * Test that an explicitly configured outputAs is honoured.
     *
     * @return void
     */
    public function testExplicitOutputAs()
    {
        $output = new ConsoleOutput('php://memory');
        $log = new ConsoleLog(['stream' => $output, 'outputAs' => ConsoleOutput::COLOR]);
        $config = $log->config();
        $this->assertSame(ConsoleOutput::COLOR, $config['outputAs']);
        $this->assertSame(ConsoleOutput::COLOR, $output->outputAs());

        new ConsoleLog(['stream' => $output, 'outputAs' => ConsoleOutput::RAW]);
        $this->assertSame(ConsoleOutput::RAW, $output->outputAs());
    }

    /**
     * Test that the constructor does not pass null to posix_isatty() (deprecated as of PHP 8.1).
     *
     * @return void
     */
    public function testConstructorDoesNotPassNullToPosixIsatty()
    {
        $deprecations = [];
        $level = error_reporting(E_ALL);
        set_error_handler(function ($errno, $errstr) use (&$deprecations) {
            if (str_contains($errstr, 'Passing null')) {
                $deprecations[] = $errstr;
            }

            return true;
        }, E_DEPRECATED);
        try {
            new ConsoleLog(['stream' => 'php://memory']);
        } finally {
            restore_error_handler();
            error_reporting($level);
        }
        $this->assertSame([], $deprecations);
    }
}
