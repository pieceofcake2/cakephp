<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @package       Cake.Test.Case.TestSuite.Reporter
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Test\TestCase\TestSuite\Reporter;

use Cake\Core\App;
use Cake\Core\CakePlugin;
use Cake\TestSuite\CakeTestCase;
use Cake\TestSuite\Reporter\CakeTextReporter;
use Cake\Utility\Folder;
use PHPUnit\Framework\TestResult;
use PHPUnit\Framework\Warning;
use TestApp\TestSuite\ReporterSampleCase;

/**
 * CakeTextReporterTest
 *
 * @package       Cake.Test.Case.TestSuite.Reporter
 */
class CakeTextReporterTest extends CakeTestCase
{
    /**
     * Temporary plugins directory
     *
     * @var string
     */
    protected string $pluginsPath;

    /**
     * The $_SERVER values replaced by the tests.
     *
     * @var array
     */
    protected array $server = [];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->server = $_SERVER;
        $_SERVER['SERVER_NAME'] = 'localhost';
        $_SERVER['PHP_SELF'] = '/test.php';

        $this->pluginsPath = TMP . 'tests' . DS . 'cake_text_reporter_plugins' . DS;
        $files = [
            'ReporterPlugin' . DS . 'tests' . DS . 'TestCase' . DS . 'Model' . DS . 'ArticleTest.php',
            'ReporterPlugin' . DS . 'tests' . DS . 'TestCase' . DS . 'Controller' . DS . 'PostsControllerTest.php',
        ];
        foreach ($files as $file) {
            $path = $this->pluginsPath . $file;
            if (!is_dir(dirname($path))) {
                mkdir(dirname($path), 0777, true);
            }
            file_put_contents($path, '<?php' . PHP_EOL);
        }
        App::build(['Plugin' => [$this->pluginsPath]], App::RESET);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        $_SERVER = $this->server;
        CakePlugin::unload('ReporterPlugin');
        CakePlugin::unload('EmptyReporterPlugin');
        $Folder = new Folder($this->pluginsPath);
        $Folder->delete();

        parent::tearDown();
    }

    /**
     * Returns what the callback echoes, and closes the output buffer even when it throws.
     *
     * @param callable $callback The callback.
     * @return string
     */
    protected function capture(callable $callback): string
    {
        ob_start();
        try {
            $callback();
        } finally {
            $output = (string)ob_get_clean();
        }

        return $output;
    }

    /**
     * Runs a sample suite with the reporter as listener.
     *
     * @param CakeTextReporter $reporter The reporter.
     * @param string ...$methods The ReporterSampleCase methods to run.
     * @return array{string, TestResult} What the run echoed, and its result.
     */
    protected function runSample(CakeTextReporter $reporter, string ...$methods): array
    {
        $result = new TestResult();
        $result->addListener($reporter);
        $output = $this->capture(function () use ($methods, $result): void {
            ReporterSampleCase::buildSuite(...$methods)->run($result);
        });

        return [$output, $result];
    }

    /**
     * Returns what the reporter echoes for the footer of the result.
     *
     * @param CakeTextReporter $reporter The reporter.
     * @param TestResult $result The result.
     * @return string
     */
    protected function footer(CakeTextReporter $reporter, TestResult $result): string
    {
        return $this->capture(function () use ($reporter, $result): void {
            $reporter->paintFooter($result);
        });
    }

    /**
     * Test that a passing run paints a dot per test and an OK summary.
     *
     * @return void
     */
    public function testPassingRun(): void
    {
        $reporter = new CakeTextReporter();
        [$output, $result] = $this->runSample($reporter, 'testPasses');

        $this->assertSame("Running  Reporter sample\n.", $output);
        $this->assertMatchesRegularExpression(
            '/^\nOK\nTest cases run: 1\/1, Passes: 2, Failures: 0, Exceptions: 0\n' .
            'Time: [0-9.E-]+ seconds\nPeak memory: [0-9,]+ bytes\n$/',
            $this->footer($reporter, $result),
        );
    }

    /**
     * Test the summary of a run with a pass, a failure, an exception, a skipped
     * and an incomplete test. The summary is `Test cases run: <all tests>/<all
     * tests minus the skipped ones>`, then `Passes: <assertions>`, `Failures:
     * <failures>` and `Exceptions: <errors>`.
     *
     * @return void
     */
    public function testFailingRunSummary(): void
    {
        $reporter = new CakeTextReporter();
        [, $result] = $this->runSample(
            $reporter,
            'testPasses',
            'testFails',
            'testThrowsException',
            'testSkipped',
            'testIncomplete',
        );

        // Pending decision: "Test cases run: 5/4" is the reverse of the "4/5"
        // of the HTML summary, and "Passes" counts the assertions, not the
        // passing tests. The assertion keeps the current output.
        $this->assertMatchesRegularExpression(
            '/^FAILURES!!!\nTest cases run: 5\/4, Passes: 3, Failures: 1, Exceptions: 1\n' .
            'Time: [0-9.E-]+ seconds\nPeak memory: [0-9,]+ bytes\n$/',
            $this->footer($reporter, $result),
        );
    }

    /**
     * Test that the messages of skipped and incomplete tests are painted as plain text.
     *
     * @return void
     */
    public function testPaintsSkipMessages(): void
    {
        [$output] = $this->runSample(new CakeTextReporter(), 'testSkipped', 'testIncomplete');

        $this->assertStringContainsString("Skip: Needs <redis>\n", $output);
        $this->assertStringContainsString("Skip: Todo & more\n", $output);
    }

    /**
     * Test that an exception is painted with its message, file and line.
     *
     * @return void
     */
    public function testPaintsExceptionMessageAndLocation(): void
    {
        [$output] = $this->runSample(new CakeTextReporter(), 'testThrowsException');

        $line = ReporterSampleCase::$lines['testThrowsException'];
        $file = CORE_TESTS . DS . 'test_app' . DS . 'src' . DS . 'TestSuite' . DS . 'ReporterSampleCase.php';
        $this->assertStringContainsString(
            '] with message [Broken <i>thing</i>] in [' . $file . ' line ' . $line . "]\n\n",
            $output,
        );
    }

    /**
     * Test that an exception is painted with the class of the exception the test threw.
     *
     * @return void
     */
    public function testPaintsExceptionClassOfTheThrownException(): void
    {
        [$output] = $this->runSample(new CakeTextReporter(), 'testThrowsException');

        $this->assertStringContainsString(
            'Unexpected exception of type [RuntimeException] with message [Broken <i>thing</i>]',
            $output,
        );
    }

    /**
     * Test that a failure is painted with its message.
     *
     * @return void
     */
    public function testPaintsFailureMessage(): void
    {
        [$output] = $this->runSample(new CakeTextReporter(), 'testFails');

        $this->assertStringStartsWith("Running  Reporter sample\nFAIL on line ", $output);
        // The wording that PHPUnit adds after the message is not checked.
        $this->assertMatchesRegularExpression('/\nValues differ & <mismatch>\n.*? in\n/s', $output);
    }

    /**
     * Test that a failure is painted with the line, the file and the method of the
     * failing assertion in the test.
     *
     * @return void
     */
    public function testPaintsFailureLocationInTheTest(): void
    {
        [$output] = $this->runSample(new CakeTextReporter(), 'testFails');

        $line = ReporterSampleCase::$lines['testFails'];
        $file = CORE_TESTS . DS . 'test_app' . DS . 'src' . DS . 'TestSuite' . DS . 'ReporterSampleCase.php';
        $this->assertSame(
            "Running  Reporter sample\nFAIL on line $line\nValues differ & <mismatch>\n" .
            "Failed asserting that two strings are identical. in\n$file testFails()\n\n",
            $output,
        );
    }

    /**
     * Test that a failure raised outside a test method is painted with the
     * location where it was raised.
     *
     * @return void
     */
    public function testPaintsFailureLocationOutsideATest(): void
    {
        $line = __LINE__ + 1;
        $warning = new Warning('Deprecated feature');
        $output = $this->capture(function () use ($warning): void {
            (new CakeTextReporter())->paintFail($warning);
        });

        $this->assertSame("FAIL on line $line\nDeprecated feature in\n" . __FILE__ . " ()\n\n", $output);
    }

    /**
     * Test that formatted messages are painted unescaped on their own line.
     *
     * @return void
     */
    public function testPaintFormattedMessage(): void
    {
        $output = $this->capture(function (): void {
            (new CakeTextReporter())->paintFormattedMessage("array(\n\t'a' => '<b>'\n)");
        });

        $this->assertSame("array(\n\t'a' => '<b>'\n)\n", $output);
    }

    /**
     * Test that the test case list of a plugin is a list of URLs that run each
     * case of the plugin, with the case name URL-encoded as in the HTML list.
     *
     * @return void
     */
    public function testTestCaseListOfAPlugin(): void
    {
        $this->markTestIncomplete('Known bug: testCaseList() leaves the plugin and core parameters out of the URLs.');

        $reporter = new CakeTextReporter(null, ['app' => false, 'core' => false, 'plugin' => 'ReporterPlugin']);
        $output = $this->capture(function () use ($reporter): void {
            $reporter->testCaseList();
        });

        $this->assertSame(
            "ReporterPlugin Test Cases:\n" .
            'localhost/test.php?case=' . urlencode('Controller' . DS . 'PostsController') .
            "&plugin=ReporterPlugin&output=text\n" .
            'localhost/test.php?case=' . urlencode('Model' . DS . 'Article') .
            "&plugin=ReporterPlugin&output=text\n" .
            "\n",
            $output,
        );
    }

    /**
     * Test that the test case list of the core is a list of URLs that run each
     * core case, with the case name URL-encoded and the core parameter.
     *
     * @return void
     */
    public function testTestCaseListOfTheCore(): void
    {
        $this->markTestIncomplete('Known bug: testCaseList() leaves the plugin and core parameters out of the URLs.');

        $reporter = new CakeTextReporter(null, ['app' => false, 'core' => true, 'plugin' => false]);
        $output = $this->capture(function () use ($reporter): void {
            $reporter->testCaseList();
        });

        $case = urlencode('TestSuite' . DS . 'Reporter' . DS . 'CakeTextReporter');
        $this->assertStringStartsWith("Core Test Cases:\nlocalhost/test.php?case=", $output);
        $this->assertStringContainsString("\nlocalhost/test.php?case=$case&core=true&output=text\n", $output);
    }

    /**
     * Test that an empty test case list is painted once, marked as empty.
     *
     * @return void
     */
    public function testEmptyTestCaseList(): void
    {
        $this->markTestIncomplete('Known bug: testCaseList() echoes an empty list twice.');

        $reporter = new CakeTextReporter(null, ['app' => false, 'core' => false, 'plugin' => 'EmptyReporterPlugin']);
        $output = $this->capture(function () use ($reporter): void {
            $reporter->testCaseList();
        });

        $this->assertSame("EmptyReporterPlugin Test Cases:\nEMPTY\n", $output);
    }
}
