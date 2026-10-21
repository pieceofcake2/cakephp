<?php
/**
 * ConsoleErrorHandler Test case
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
 * @package       Cake.Test.Case.Console
 * @since         CakePHP(tm) v 2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Test\TestCase\Console;

use Cake\Console\ConsoleErrorHandler;
use Cake\Console\ConsoleOutput;
use Cake\Core\Configure;
use Cake\Error\InternalErrorException;
use Cake\Error\MissingActionException;
use Cake\Error\NotFoundException;
use Cake\Log\CakeLog;
use Cake\Log\Engine\ConsoleLog;
use Cake\Test\TestCase\Log\Engine\TestCakeLog;
use Cake\TestSuite\CakeTestCase;
use Exception;
use InvalidArgumentException;
use ReflectionClass;

/**
 * ConsoleErrorHandler Test case.
 *
 * @package       Cake.Test.Case.Console
 */
class ConsoleErrorHandlerTest extends CakeTestCase
{
    /**
     * setup, create mocks
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->Error = $this->getMock(ConsoleErrorHandler::class, ['_stop']);
        ConsoleErrorHandler::$stderr = $this->getMock(ConsoleOutput::class, [], [], '', false);
    }

    /**
     * tearDown
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->Error);
        ConsoleErrorHandler::$stderr = null;

        parent::tearDown();
    }

    /**
     * test that the console error handler can deal with CakeExceptions.
     *
     * @return void
     */
    public function testHandleError(): void
    {
        $content = "<error>Notice Error:</error> This is a notice error in [/some/file, line 275]\n";
        ConsoleErrorHandler::$stderr
            ->expects($this->once())
            ->method('write')
            ->with($content);

        $this->Error->handleError(E_NOTICE, 'This is a notice error', '/some/file', 275);
    }

    /**
     * test that the console error handler can deal with fatal errors.
     *
     * @return void
     */
    public function testHandleFatalError(): void
    {
        $content = "<error>Fatal Error Error:</error> This is a fatal error in [/some/file, line 275]\n";
        ConsoleErrorHandler::$stderr
            ->expects($this->once())
            ->method('write')
            ->with($content);

        $this->Error->expects($this->once())
            ->method('_stop')
            ->with(1);

        $this->Error->handleError(E_USER_ERROR, 'This is a fatal error', '/some/file', 275);
    }

    /**
     * test that the console error handler can deal with CakeExceptions.
     *
     * @return void
     */
    public function testCakeErrors(): void
    {
        $exception = new MissingActionException('Missing action');
        ConsoleErrorHandler::$stderr
            ->expects($this->once())
            ->method('write')
            ->with($this->stringContains('Missing action'));

        $this->Error->expects($this->once())
            ->method('_stop')
            ->with(404);

        $this->Error->handleException($exception);
    }

    /**
     * test a non CakeException exception.
     *
     * @return void
     */
    public function testNonCakeExceptions(): void
    {
        $exception = new InvalidArgumentException('Too many parameters.');

        ConsoleErrorHandler::$stderr
            ->expects($this->once())
            ->method('write')
            ->with($this->stringContains('Too many parameters.'));

        $this->Error->expects($this->once())
            ->method('_stop')
            ->with(1);

        $this->Error->handleException($exception);
    }

    /**
     * test a Error404 exception.
     *
     * @return void
     */
    public function testError404Exception(): void
    {
        $exception = new NotFoundException('dont use me in cli.');

        ConsoleErrorHandler::$stderr
            ->expects($this->once())
            ->method('write')
            ->with($this->stringContains('dont use me in cli.'));

        $this->Error->expects($this->once())
            ->method('_stop')
            ->with(404);

        $this->Error->handleException($exception);
    }

    /**
     * test a Error500 exception.
     *
     * @return void
     */
    public function testError500Exception(): void
    {
        $exception = new InternalErrorException('dont use me in cli.');

        ConsoleErrorHandler::$stderr
            ->expects($this->once())
            ->method('write')
            ->with($this->stringContains('dont use me in cli.'));

        $this->Error->expects($this->once())
            ->method('_stop')
            ->with(500);

        $this->Error->handleException($exception);
    }

    /**
     * test a exception with non-integer code
     *
     * @return void
     */
    public function testNonIntegerExceptionCode(): void
    {
        $exception = new Exception('Non-integer exception code');

        $class = new ReflectionClass(Exception::class);
        $property = $class->getProperty('code');
        if (PHP_VERSION_ID < 80100) {
            $property->setAccessible(true);
        }
        $property->setValue($exception, '42S22');

        ConsoleErrorHandler::$stderr
            ->expects($this->once())
            ->method('write')
            ->with($this->stringContains('Non-integer exception code'));

        $this->Error->expects($this->once())
            ->method('_stop')
            ->with(1);

        $this->Error->handleException($exception);
    }

    /**
     * Test that the stderr output is created when none is set.
     *
     * @return void
     */
    public function testGetStderr(): void
    {
        ConsoleErrorHandler::$stderr = null;

        $stderr = ConsoleErrorHandler::getStderr();
        $this->assertInstanceOf(ConsoleOutput::class, $stderr);
        $this->assertSame($stderr, ConsoleErrorHandler::getStderr());
    }

    /**
     * Test that errors left out of error_reporting are ignored.
     *
     * @return void
     */
    public function testHandleErrorNotReported(): void
    {
        ConsoleErrorHandler::$stderr
            ->expects($this->never())
            ->method('write');
        $this->Error->expects($this->never())
            ->method('_stop');

        $level = error_reporting(E_ALL & ~E_USER_NOTICE);
        try {
            $this->Error->handleError(E_USER_NOTICE, 'This is not reported', '/some/file', 275);
        } finally {
            error_reporting($level);
        }
    }

    /**
     * Test that errors are logged when debug is off.
     *
     * @return void
     */
    public function testHandleErrorLogsWithoutDebug(): void
    {
        Configure::write('debug', 0);
        ConsoleErrorHandler::$stderr
            ->expects($this->once())
            ->method('write')
            ->with("<error>Notice Error:</error> This is a notice error in [/some/file, line 275]\n");

        $streams = CakeLog::configured();
        foreach ($streams as $stream) {
            CakeLog::disable($stream);
        }
        $log = $this->getMock(ConsoleLog::class, ['write'], [['types' => ['notice']]]);
        $log->expects($this->once())
            ->method('write')
            ->with('notice', 'This is a notice error in [/some/file, line 275]');
        TestCakeLog::config('console_error_handler_test', ['engine' => 'Console']);
        TestCakeLog::replace('console_error_handler_test', $log);
        try {
            $this->Error->handleError(E_NOTICE, 'This is a notice error', '/some/file', 275);
        } finally {
            CakeLog::drop('console_error_handler_test');
            foreach ($streams as $stream) {
                CakeLog::enable($stream);
            }
        }
    }
}
