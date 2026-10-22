<?php
/**
 * CakeTextReporter contains reporting features used for plain text based output
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

use Cake\TestSuite\Coverage\TextCoverageReport;
use Cake\Utility\Inflector;
use PHPUnit\Framework\ExceptionWrapper;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\TestResult;
use Throwable;

/**
 * CakeTextReporter contains reporting features used for plain text based output
 *
 * @package       Cake.TestSuite.Reporter
 */
class CakeTextReporter extends CakeBaseReporter
{
    /**
     * Sets the text/plain header if the test is not a CLI test.
     *
     * @return void
     */
    public function paintDocumentStart()
    {
        if (!headers_sent()) {
            header('Content-type: text/plain');
        }
    }

    /**
     * Paints a pass
     *
     * @return void
     */
    public function paintPass()
    {
        echo '.';
    }

    /**
     * Paints a failing test.
     *
     * The location is the line in the test method that failed: the frame of
     * the call made by the test method, which PHPUnit's TestCase::runTest()
     * called, or the place where the test method raised the failure itself.
     * Only the innermost test is looked at, so that a test that runs other
     * tests does not report its own location. When the failure was not raised
     * by the test method, the location where it was raised is painted.
     *
     * @param \PHPUnit\Framework\AssertionFailedError|\PHPUnit\Framework\Warning $message Failure object displayed in
     *   the context of the other tests.
     * @return void
     */
    public function paintFail($message)
    {
        $file = $message->getFile();
        $line = $message->getLine();
        $function = '';
        $trace = $message->getTrace();
        foreach ($trace as $i => $frame) {
            if (
                !isset($frame['class']) ||
                !is_a($frame['class'], TestCase::class, true) ||
                ($frame['function'] !== 'runTest' && $frame['function'] !== 'runBare')
            ) {
                continue;
            }
            // The frame before runTest() is the call of the test method, unless runTest() raised the failure.
            $method = $trace[$i - 1] ?? [];
            if (
                $frame['function'] === 'runTest' &&
                isset($method['class']) &&
                is_a($method['class'], TestCase::class, true)
            ) {
                if ($i === 1) {
                    $function = $method['function'];
                } elseif (isset($trace[$i - 2]['file']) && isset($trace[$i - 2]['line'])) {
                    $file = $trace[$i - 2]['file'];
                    $line = $trace[$i - 2]['line'];
                    $function = $method['function'];
                }
            }
            break;
        }

        printf(
            "FAIL on line %s\n%s in\n%s %s()\n\n",
            $line,
            $message->toString(),
            $file,
            $function,
        );
    }

    /**
     * Paints the end of the test with a summary of
     * the passes and failures.
     *
     * The summary is `Test cases run: <complete>/<total>`, where the complete
     * tests are those that were neither skipped nor incomplete, then the
     * number of passing tests, failures, exceptions, skipped and incomplete
     * tests.
     *
     * @param TestResult $result Result object
     * @return void
     */
    public function paintFooter(TestResult $result)
    {
        if ($result->failureCount() + $result->errorCount()) {
            echo "FAILURES!!!\n";
        } else {
            echo "\nOK\n";
        }

        echo 'Test cases run: ' . ($result->count() - $result->skippedCount() - $result->notImplementedCount()) .
            '/' . $result->count() .
            ', Passes: ' . count($result->passed()) .
            ', Failures: ' . $result->failureCount() .
            ', Exceptions: ' . $result->errorCount() .
            ', Skipped: ' . $result->skippedCount() .
            ', Incomplete: ' . $result->notImplementedCount() . "\n";

        echo 'Time: ' . $result->time() . " seconds\n";
        echo 'Peak memory: ' . number_format(memory_get_peak_usage()) . " bytes\n";

        if (isset($this->params['codeCoverage']) && $this->params['codeCoverage']) {
            $coverage = $result->getCodeCoverage();
            if ($coverage !== null) {
                $this->paintCoverage($coverage->getData()->lineCoverage());
            }
        }
    }

    /**
     * Paints the title only.
     *
     * @return void
     */
    public function paintHeader()
    {
        $this->paintDocumentStart();
        flush();
    }

    /**
     * Paints a PHP exception.
     *
     * PHPUnit passes the exception a test threw wrapped in an ExceptionWrapper.
     * The class of the thrown exception is painted, not that of the wrapper.
     *
     * @param Throwable $exception Exception to describe.
     * @return void
     */
    public function paintException($exception)
    {
        $className = $exception::class;
        if ($exception instanceof ExceptionWrapper) {
            $className = $exception->getClassName();
        }
        $message = 'Unexpected exception of type [' . $className .
            '] with message [' . $exception->getMessage() .
            '] in [' . $exception->getFile() .
            ' line ' . $exception->getLine() . ']';
        echo $message . "\n\n";
    }

    /**
     * Prints the message for skipping tests.
     *
     * @param Throwable $message The skip or incomplete exception.
     * @return void
     */
    public function paintSkip($message)
    {
        printf("Skip: %s\n", $message->getMessage());
    }

    /**
     * Paints formatted text such as dumped variables.
     *
     * @param string $message Text to show.
     * @return void
     */
    public function paintFormattedMessage($message)
    {
        echo "$message\n";
        flush();
    }

    /**
     * Generate a test case list in plain text.
     * Creates as series of URLs for tests that can be run.
     * One case per line, with the core or plugin parameter of the list.
     * An empty list is marked with `EMPTY`.
     *
     * @return void
     */
    public function testCaseList()
    {
        $testCases = parent::testCaseList();
        $app = $this->params['app'];
        $core = $this->params['core'];
        $plugin = $this->params['plugin'];

        $buffer = "Core Test Cases:\n";
        if ($app) {
            $buffer = "App Test Cases:\n";
        } elseif ($plugin) {
            $buffer = Inflector::humanize($plugin) . " Test Cases:\n";
        }

        $urlExtra = '';
        if ($core) {
            $urlExtra = '&core=true';
        } elseif ($plugin) {
            $urlExtra = '&plugin=' . urlencode($plugin);
        }

        if (count($testCases) < 1) {
            $buffer .= 'EMPTY';
        }

        foreach ($testCases as $testCase) {
            $buffer .= $_SERVER['SERVER_NAME'] . $this->baseUrl() . '?case=' . urlencode($testCase) . $urlExtra . "&output=text\n";
        }

        $buffer .= "\n";
        echo $buffer;
    }

    /**
     * Generates a Text summary of the coverage data.
     *
     * @param array $coverage Array of coverage data.
     * @return void
     */
    public function paintCoverage($coverage)
    {
        $reporter = new TextCoverageReport($coverage, $this);
        echo $reporter->report();
    }
}

class_alias(CakeTextReporter::class, 'CakeTextReporter');
