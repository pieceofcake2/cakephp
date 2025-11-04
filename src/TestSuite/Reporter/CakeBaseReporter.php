<?php
/**
 * CakeBaseReporter contains common functionality to all cake test suite reporters.
 *
 * CakePHP(tm) Tests <https://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         CakePHP(tm) v 1.3
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\TestSuite\Reporter;

use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Test;
use PHPUnit\Framework\TestResult;
use PHPUnit\Framework\TestSuite;
use PHPUnit\Framework\Warning;
use PHPUnit\TextUI\ResultPrinter;
use Throwable;

/**
 * CakeBaseReporter contains common reporting features used in the CakePHP Test suite
 *
 * @package       Cake.TestSuite.Reporter
 */
abstract class CakeBaseReporter implements ResultPrinter
{
    /**
     * Headers sent
     *
     * @var bool
     */
    protected bool $_headerSent = false;

    /**
     * Array of request parameters. Usually parsed GET params.
     *
     * @var array
     */
    public array $params = [];

    /**
     * Character set for the output of test reporting.
     *
     * @var string
     */
    protected string $_characterSet;

    /**
     * @var int
     */
    protected int $numAssertions = 0;

    /**
     * Does nothing yet. The first output will
     * be sent on the first test start.
     *
     * ### Params
     *
     * - show_passes - Should passes be shown
     * - plugin - Plugin test being run?
     * - core - Core test being run.
     * - case - The case being run
     * - codeCoverage - Whether the case/group being run is being code covered.
     *
     * @param string $charset The character set to output with. Defaults to UTF-8
     * @param array $params Array of request parameters the reporter should use. See above.
     */
    public function __construct(string $charset = 'utf-8', array $params = [])
    {
        if (!$charset) {
            $charset = 'utf-8';
        }
        $this->_characterSet = $charset;
        $this->params = $params;
    }

    /**
     * Retrieves a list of test cases from the active Manager class,
     * displaying it in the correct format for the reporter subclass
     *
     * @return void
     */
    abstract public function testCaseList(): void;

    /**
     * Paints the start of the response from the test suite.
     * Used to paint things like head elements in an html page.
     *
     * @return void
     */
    abstract public function paintDocumentStart(): void;

    /**
     * Paints the end of the response from the test suite.
     * Used to paint things like </body> in an html page.
     *
     * @return void
     */
    abstract public function paintDocumentEnd(): void;

    /**
     * Paint a list of test sets, core, app, and plugin test sets
     * available.
     *
     * @return void
     */
    public function paintTestMenu(): void
    {
    }

    /**
     * Get the baseUrl if one is available.
     *
     * @return string The base URL for the request.
     */
    public function baseUrl(): string
    {
        if (!empty($_SERVER['PHP_SELF'])) {
            return $_SERVER['PHP_SELF'];
        }

        return '';
    }

    /**
     * Print result
     *
     * @param TestResult $result The result object
     * @return void
     */
    public function printResult(TestResult $result): void
    {
        $this->paintFooter($result);
    }

    /**
     * Paint result
     *
     * @param TestResult $result The result object
     * @return void
     */
    public function paintResult(TestResult $result): void
    {
        $this->paintFooter($result);
    }

    /**
     * An error occurred.
     *
     * @param Test $test The test to add an error for.
     * @param Throwable $t The exception object to add.
     * @param float $time The current time.
     * @return void
     */
    public function addError(Test $test, Throwable $t, float $time): void
    {
        $this->paintException($t, $test);
    }

    /**
     * A failure occurred.
     *
     * @param Test $test The test that failed
     * @param AssertionFailedError $e The assertion that failed.
     * @param float $time The current time.
     * @return void
     */
    public function addFailure(Test $test, AssertionFailedError $e, float $time): void
    {
        $this->paintFail($e, $test);
    }

    /**
     * Incomplete test.
     *
     * @param Test $test The test that was incomplete.
     * @param Throwable $t The incomplete exception
     * @param float $time The current time.
     * @return void
     */
    public function addIncompleteTest(Test $test, Throwable $t, float $time): void
    {
        $this->paintSkip($t, $test);
    }

    /**
     * Skipped test.
     *
     * @param Test $test The test that failed.
     * @param Throwable $t The skip object.
     * @param float $time The current time.
     * @return void
     */
    public function addSkippedTest(Test $test, Throwable $t, $time): void
    {
        $this->paintSkip($t, $test);
    }

    /**
     * A test suite started.
     *
     * @param TestSuite $suite The suite to start
     * @return void
     */
    public function startTestSuite(TestSuite $suite): void
    {
        if (!$this->_headerSent) {
            $this->paintHeader();
        }

        echo __d('cake_dev', 'Running  %s', $suite->getName()) . "\n";
    }

    /**
     * A test suite ended.
     *
     * @param TestSuite $suite The suite that ended.
     * @return void
     */
    public function endTestSuite(TestSuite $suite): void
    {
    }

    /**
     * A test started.
     *
     * @param Test $test The test that started.
     * @return void
     */
    public function startTest(Test $test): void
    {
    }

    /**
     * A test ended.
     *
     * @param Test $test The test that ended
     * @param float $time The current time.
     * @return void
     */
    public function endTest(Test $test, $time): void
    {
        if (method_exists($test, 'getNumAssertions')) {
            $this->numAssertions += $test->getNumAssertions();
        }
        if (!method_exists($test, 'hasFailed') || $test->hasFailed()) {
            return;
        }
        $this->paintPass($test, $time);
    }

    /**
     * @param string $buffer
     * @return void
     */
    public function write(string $buffer): void
    {
        echo $buffer;
    }

    /**
     * @param Test $test
     * @param Warning $e
     * @param float $time
     * @return void
     */
    public function addWarning(
        Test $test,
        Warning $e,
        float $time,
    ): void {
        $this->paintFail($e, $test);
    }

    /**
     * @param Test $test
     * @param Throwable $t
     * @param float $time
     * @return void
     */
    public function addRiskyTest(
        Test $test,
        Throwable $t,
        float $time,
    ): void {
    }

    /**
     * @return void
     */
    abstract public function paintHeader(): void;

    /**
     * @param TestResult $result
     * @return void
     */
    abstract public function paintFooter(
        TestResult $result,
    ): void;

    /**
     * @param Test $test
     * @param float|null $time
     * @return void
     */
    abstract public function paintPass(
        Test $test,
        ?float $time = null,
    ): void;

    /**
     * @param Throwable $message
     * @param Test $test
     * @return void
     */
    abstract public function paintSkip(
        Throwable $message,
        Test $test,
    ): void;

    /**
     * @param Throwable $exception
     * @param Test $test
     * @return void
     */
    abstract public function paintException(
        Throwable $exception,
        Test $test,
    ): void;

    /**
     * @param AssertionFailedError|Warning $message
     * @param Test $test
     * @return void
     */
    abstract public function paintFail(
        AssertionFailedError|Warning $message,
        Test $test,
    ): void;
}
