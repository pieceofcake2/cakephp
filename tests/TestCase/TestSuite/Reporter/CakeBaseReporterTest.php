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
use Cake\TestSuite\Reporter\CakeBaseReporter;
use Cake\Utility\Folder;
use PHPUnit\Framework\Test;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\TestResult;
use PHPUnit\Framework\Warning;
use TestApp\TestSuite\ReporterSampleCase;
use Throwable;

/**
 * A reporter that records which paint method is called for which test.
 *
 * @package       Cake.Test.Case.TestSuite.Reporter
 */
class RecordingReporter extends CakeBaseReporter
{
    /**
     * The recorded calls, as `method: test name[: message]`.
     *
     * @var array<string>
     */
    public array $calls = [];

    /**
     * The results passed to paintFooter().
     *
     * @var array<TestResult>
     */
    public array $footers = [];

    /**
     * Records the header and returns its content.
     *
     * @return string
     */
    public function paintHeader(): string
    {
        $this->calls[] = 'paintHeader';

        return "[header]\n";
    }

    /**
     * Records the footer.
     *
     * @param TestResult $result The result.
     * @return void
     */
    public function paintFooter(TestResult $result): void
    {
        $this->footers[] = $result;
    }

    /**
     * Records a pass.
     *
     * @param TestCase $test The test.
     * @return void
     */
    public function paintPass(Test $test): void
    {
        $this->calls[] = 'paintPass: ' . $test->getName();
    }

    /**
     * Records a failure.
     *
     * @param Throwable $message The failure.
     * @param TestCase $test The test.
     * @return void
     */
    public function paintFail($message, $test): void
    {
        $this->calls[] = 'paintFail: ' . $test->getName() . ': ' . $message->getMessage();
    }

    /**
     * Records an error.
     *
     * @param Throwable $message The error.
     * @param TestCase $test The test.
     * @return void
     */
    public function paintException($message, $test): void
    {
        $this->calls[] = 'paintException: ' . $test->getName() . ': ' . $message->getMessage();
    }

    /**
     * Records a skipped or incomplete test.
     *
     * @param Throwable $message The skip.
     * @param TestCase $test The test.
     * @return void
     */
    public function paintSkip($message, $test): void
    {
        $this->calls[] = 'paintSkip: ' . $test->getName() . ': ' . $message->getMessage();
    }

    /**
     * Returns the number of assertions counted so far.
     *
     * @return int
     */
    public function assertionCount(): int
    {
        return $this->numAssertions;
    }
}

/**
 * CakeBaseReporterTest
 *
 * @package       Cake.Test.Case.TestSuite.Reporter
 */
class CakeBaseReporterTest extends CakeTestCase
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
        $this->pluginsPath = TMP . 'tests' . DS . 'cake_base_reporter_plugins' . DS;
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
     * Runs a sample suite with the reporter as listener and returns what it echoed.
     *
     * @param RecordingReporter $reporter The reporter.
     * @param string ...$methods The ReporterSampleCase methods to run.
     * @return string
     */
    protected function runSample(RecordingReporter $reporter, string ...$methods): string
    {
        $result = new TestResult();
        $result->addListener($reporter);

        return $this->capture(function () use ($methods, $result): void {
            ReporterSampleCase::buildSuite(...$methods)->run($result);
        });
    }

    /**
     * Test that a run paints the header and the suite name, and sends each
     * passing, failing and erroring test to its own paint method.
     *
     * @return void
     */
    public function testRunDispatchesEachOutcomeToItsPaintMethod(): void
    {
        $reporter = new RecordingReporter();
        $output = $this->runSample($reporter, 'testPasses', 'testFails', 'testThrowsException');

        $this->assertSame("[header]\nRunning  Reporter sample\n", $output);
        // The failure message goes on with the wording of PHPUnit, which is not checked.
        $this->assertCount(4, $reporter->calls);
        $this->assertSame(['paintHeader', 'paintPass: testPasses'], array_slice($reporter->calls, 0, 2));
        $this->assertStringStartsWith("paintFail: testFails: Values differ & <mismatch>\n", $reporter->calls[2]);
        $this->assertSame('paintException: testThrowsException: Broken <i>thing</i>', $reporter->calls[3]);
        $this->assertSame(3, $reporter->assertionCount(), 'Two assertions pass and one fails.');
    }

    /**
     * Test that skipped and incomplete tests are painted as skips only, not as passes.
     *
     * @return void
     */
    public function testSkippedAndIncompleteTestsAreNotPaintedAsPasses(): void
    {
        $reporter = new RecordingReporter();
        $this->runSample($reporter, 'testSkipped', 'testIncomplete');

        $this->assertSame([
            'paintHeader',
            'paintSkip: testSkipped: Needs <redis>',
            'paintSkip: testIncomplete: Todo & more',
        ], $reporter->calls);
    }

    /**
     * Test that a warning is painted as a failure.
     *
     * @return void
     */
    public function testAddWarningPaintsAFailure(): void
    {
        $reporter = new RecordingReporter();
        $reporter->addWarning(new ReporterSampleCase('testPasses'), new Warning('Deprecated feature'), 0.1);

        $this->assertSame(['paintFail: testPasses: Deprecated feature'], $reporter->calls);
    }

    /**
     * Test that printResult() and paintResult() both paint the footer of the given result.
     *
     * @return void
     */
    public function testPrintResultAndPaintResultPaintTheFooter(): void
    {
        $reporter = new RecordingReporter();
        $first = new TestResult();
        $second = new TestResult();
        $reporter->printResult($first);
        $reporter->paintResult($second);

        $this->assertSame([$first, $second], $reporter->footers);
    }

    /**
     * Test that write() echoes the buffer unchanged.
     *
     * @return void
     */
    public function testWriteEchoesTheBuffer(): void
    {
        $reporter = new RecordingReporter();
        $output = $this->capture(function () use ($reporter): void {
            $reporter->write("PHPUnit <output>\n");
        });

        $this->assertSame("PHPUnit <output>\n", $output);
    }

    /**
     * Test that the base URL is the running script, or empty without one.
     *
     * @return void
     */
    public function testBaseUrl(): void
    {
        $reporter = new RecordingReporter();
        $_SERVER['PHP_SELF'] = '/app/webroot/test.php';
        $this->assertSame('/app/webroot/test.php', $reporter->baseUrl());

        unset($_SERVER['PHP_SELF']);
        $this->assertSame('', $reporter->baseUrl());
    }

    /**
     * Test that the test case list is the sorted list of the test cases of the
     * requested plugin, without the Test.php suffix.
     *
     * @return void
     */
    public function testTestCaseListOfAPlugin(): void
    {
        $reporter = new RecordingReporter('utf-8', ['plugin' => 'ReporterPlugin']);

        $this->assertSame(
            ['Controller' . DS . 'PostsController', 'Model' . DS . 'Article'],
            $reporter->testCaseList(),
        );
    }
}
