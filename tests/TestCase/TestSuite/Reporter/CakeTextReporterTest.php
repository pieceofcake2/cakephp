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
use ReflectionMethod;
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
        ob_start();
        ReporterSampleCase::buildSuite(...$methods)->run($result);

        return [(string)ob_get_clean(), $result];
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
        ob_start();
        $reporter->paintFooter($result);

        return (string)ob_get_clean();
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
     * and an incomplete test: skipped tests do not count as run, and the passes
     * count the assertions.
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

        $line = (new ReflectionMethod(ReporterSampleCase::class, 'testThrowsException'))->getStartLine() + 2;
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
        $this->markTestIncomplete('Known bug: paintException() names PHPUnit\'s ExceptionWrapper instead of the thrown class.');

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
        $this->assertStringContainsString(
            "\nValues differ & <mismatch>\nFailed asserting that two strings are identical. in\n",
            $output,
        );
    }

    /**
     * Test that a failure is painted with the line, the file and the method of the
     * failing assertion in the test.
     *
     * @return void
     */
    public function testPaintsFailureLocationInTheTest(): void
    {
        $this->markTestIncomplete('Known bug: paintFail() reports a frame inside PHPUnit\'s Assert class, not the test.');

        [$output] = $this->runSample(new CakeTextReporter(), 'testFails');

        $line = (new ReflectionMethod(ReporterSampleCase::class, 'testFails'))->getStartLine() + 2;
        $file = CORE_TESTS . DS . 'test_app' . DS . 'src' . DS . 'TestSuite' . DS . 'ReporterSampleCase.php';
        $this->assertSame(
            "Running  Reporter sample\nFAIL on line $line\nValues differ & <mismatch>\n" .
            "Failed asserting that two strings are identical. in\n$file testFails()\n\n",
            $output,
        );
    }

    /**
     * Test that formatted messages are painted unescaped on their own line.
     *
     * @return void
     */
    public function testPaintFormattedMessage(): void
    {
        ob_start();
        (new CakeTextReporter())->paintFormattedMessage("array(\n\t'a' => '<b>'\n)");

        $this->assertSame("array(\n\t'a' => '<b>'\n)\n", ob_get_clean());
    }

    /**
     * Test that the test case list of a plugin is a list of URLs that run each case.
     *
     * @return void
     */
    public function testTestCaseListOfAPlugin(): void
    {
        $this->markTestIncomplete('Known bug: testCaseList() leaves the plugin and core parameters out of the URLs.');

        $reporter = new CakeTextReporter(null, ['app' => false, 'core' => false, 'plugin' => 'ReporterPlugin']);
        ob_start();
        $reporter->testCaseList();

        $this->assertSame(
            "ReporterPlugin Test Cases:\n" .
            'localhost/test.php?case=Controller' . DS . "PostsController&plugin=ReporterPlugin&output=text\n" .
            'localhost/test.php?case=Model' . DS . "Article&plugin=ReporterPlugin&output=text\n" .
            "\n",
            ob_get_clean(),
        );
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
        ob_start();
        $reporter->testCaseList();

        $this->assertSame("EmptyReporterPlugin Test Cases:\nEMPTY\n", ob_get_clean());
    }
}
