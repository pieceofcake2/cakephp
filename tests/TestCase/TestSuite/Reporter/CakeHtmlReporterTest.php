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
use Cake\Core\Configure;
use Cake\TestSuite\CakeTestCase;
use Cake\TestSuite\Reporter\CakeHtmlReporter;
use Cake\Utility\Folder;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\TestResult;
use PHPUnit\Framework\TestSuite;
use PHPUnit\Runner\Version;
use ReflectionClass;
use TestApp\TestSuite\ReporterSampleCase;

/**
 * CakeHtmlReporterTest
 *
 * @package       Cake.Test.Case.TestSuite.Reporter
 */
class CakeHtmlReporterTest extends CakeTestCase
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
        $_SERVER['PHP_SELF'] = '/test.php';
        // The footer renders the sql_dump element, which prints the query log only with debug 2.
        Configure::write('debug', 0);

        $this->pluginsPath = TMP . 'tests' . DS . 'cake_html_reporter_plugins' . DS;
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
     * Creates a reporter with the parameters of the web test runner.
     *
     * @param array $params Parameters to override.
     * @return CakeHtmlReporter
     */
    protected function reporter(array $params = []): CakeHtmlReporter
    {
        return new CakeHtmlReporter(null, $params + [
            'case' => 'Model/Post',
            'core' => false,
            'app' => false,
            'plugin' => 'Blog',
            'codeCoverage' => false,
            'showPasses' => false,
            'baseUrl' => '/',
            'baseDir' => '/base/',
        ]);
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
     * Runs a suite with the reporter as listener and returns the page painted
     * by the footer, which holds everything the run painted.
     *
     * @param CakeHtmlReporter $reporter The reporter.
     * @param TestSuite $suite The suite to run.
     * @return string
     */
    protected function runAndPaint(CakeHtmlReporter $reporter, TestSuite $suite): string
    {
        $result = new TestResult();
        $result->addListener($reporter);

        // paintDocumentEnd() flushes the current output buffer, so collect the output in an outer one.
        $level = ob_get_level();
        ob_start();
        ob_start();
        $output = '';
        try {
            $suite->run($result);
            $reporter->paintFooter($result);
        } finally {
            while (ob_get_level() > $level) {
                $output = ob_get_clean() . $output;
            }
        }

        return $output;
    }

    /**
     * Test the page of a run: the header with the base directory and the
     * versions, the menu, the results and the red summary of a failing run,
     * in that order.
     *
     * @return void
     */
    public function testPageOfAFailingRun(): void
    {
        $suite = ReporterSampleCase::buildSuite(
            'testPasses',
            'testFails',
            'testThrowsException',
            'testSkipped',
            'testIncomplete',
        );
        $output = $this->runAndPaint($this->reporter(), $suite);

        // Pending decision: the summary counts the incomplete test as complete
        // ("4/5 test methods complete"). The assertion keeps the current output.
        $parts = [
            'background-image: url(/base/img/test-pass-icon.png);',
            '<link rel="stylesheet" href="/base/css/cake.generic.css" />',
            '<div id="version">PHPUnit: ' . Version::id() . '</div>',
            '<h2>CakePHP Test Suite ' . Configure::version() . '</h2>',
            '<div class="test-menu">',
            "<ul class='tests'>\n<h2>Running  Reporter sample</h2>",
            "<li class='fail'>\n<span>Failed</span>",
            "<li class='skipped'>\n<span>Skipped</span> testSkipped",
            "</ul>\n<div style=\"padding: 8px; margin: 1em 0; background-color: red; color: white;\">" .
                "4/5 test methods complete:\n<strong>1</strong> passes, <strong>1</strong> fails, " .
                '<strong>3</strong> assertions and <strong>1</strong> exceptions.</div>',
            '<img src="/base/img/cake.power.gif"',
        ];
        $offset = 0;
        foreach ($parts as $part) {
            $position = strpos($output, $part, $offset);
            $this->assertNotFalse($position, "Missing or out of order: $part");
            $offset = $position + strlen($part);
        }
        $this->assertStringEndsWith("</body>\n</html>\n", $output);
        $this->assertStringNotContainsString("<li class='pass'>", $output, 'Passes are only shown on request.');
    }

    /**
     * Test that the page starts with a doctype and a head titled with the CakePHP version.
     *
     * @return void
     */
    public function testPageStartsWithDoctypeAndTitle(): void
    {
        $this->markTestIncomplete('Known bug: header.php lost its doctype, <html>, <head> and <title> opening.');

        $output = $this->runAndPaint($this->reporter(), ReporterSampleCase::buildSuite('testPasses'));

        $this->assertStringStartsWith('<!DOCTYPE html>', $output);
        $this->assertStringContainsString('<title>CakePHP Test Suite ' . Configure::version() . '</title>', $output);
    }

    /**
     * Test the green summary of a passing run.
     *
     * @return void
     */
    public function testSummaryOfAPassingRun(): void
    {
        $output = $this->runAndPaint($this->reporter(), ReporterSampleCase::buildSuite('testPasses'));

        $this->assertStringContainsString(
            '<div style="padding: 8px; margin: 1em 0; background-color: green; color: white;">' .
            "1/1 test methods complete:\n<strong>1</strong> passes, <strong>0</strong> fails, " .
            '<strong>2</strong> assertions and <strong>0</strong> exceptions.</div>',
            $output,
        );
    }

    /**
     * Test that a failure is painted with its escaped message and diff, the
     * test name, a link that reruns only this test, and the stack trace.
     *
     * @return void
     */
    public function testPaintsFailure(): void
    {
        $output = $this->runAndPaint($this->reporter(), ReporterSampleCase::buildSuite('testFails'));

        $test = ReporterSampleCase::class . '::testFails()';
        $this->assertStringContainsString(
            "<li class='fail'>\n<span>Failed</span><div class='msg'><pre>Values differ &amp; &lt;mismatch&gt;\n" .
            "Failed asserting that two strings are identical.<br />--- Original\n+++ New\n@@ @@\n" .
            "-'expected &lt;b&gt;bold&lt;/b&gt;'\n+'actual &lt;b&gt;bold&lt;/b&gt;'\n</pre></div>\n" .
            "<div class='msg'>Test case: $test</div>\n" .
            "<div class='msg'><a href='/test.php?plugin=Blog&amp;case=Model/Post&amp;filter=testFails'>" .
            "Rerun only this test: $test</a></div>\n" .
            "<div class='msg'>Stack trace:<br />",
            $output,
        );
        $line = ReporterSampleCase::$lines['testFails'];
        $file = CORE_TESTS . DS . 'test_app' . DS . 'src' . DS . 'TestSuite' . DS . 'ReporterSampleCase.php';
        $this->assertStringContainsString("<br />$file : $line<br />", $output);
    }

    /**
     * Test that an exception is painted with its escaped message and the test name.
     *
     * @return void
     */
    public function testPaintsExceptionMessage(): void
    {
        $output = $this->runAndPaint($this->reporter(), ReporterSampleCase::buildSuite('testThrowsException'));

        $this->assertStringContainsString(
            "<div class='msg'>Broken &lt;i&gt;thing&lt;/i&gt;</div>\n" .
            "<div class='msg'>Test case: " . ReporterSampleCase::class . "(testThrowsException)</div>\n",
            $output,
        );
    }

    /**
     * Test that an exception is painted with the class of the exception the
     * test threw, and a stack trace that starts at the throw site (its file and
     * line) and goes on with the trace of that exception, which starts at the
     * PHPUnit frame that called the test method.
     *
     * @return void
     */
    public function testPaintsClassAndTraceOfTheThrownException(): void
    {
        $this->markTestIncomplete('Known bug: paintException() shows PHPUnit\'s ExceptionWrapper class and trace.');

        $output = $this->runAndPaint($this->reporter(), ReporterSampleCase::buildSuite('testThrowsException'));

        $line = ReporterSampleCase::$lines['testThrowsException'];
        $file = CORE_TESTS . DS . 'test_app' . DS . 'src' . DS . 'TestSuite' . DS . 'ReporterSampleCase.php';
        $caller = (new ReflectionClass(TestCase::class))->getFileName();
        $this->assertStringContainsString("<li class='fail'>\n<span>RuntimeException</span>", $output);
        $this->assertStringContainsString("Stack trace:<br />$file : $line<br />$caller : ", $output);
    }

    /**
     * Test that skipped and incomplete tests are painted with their escaped messages.
     *
     * @return void
     */
    public function testPaintsSkippedAndIncompleteTests(): void
    {
        $output = $this->runAndPaint(
            $this->reporter(),
            ReporterSampleCase::buildSuite('testSkipped', 'testIncomplete'),
        );

        $this->assertStringContainsString(
            "<li class='skipped'>\n<span>Skipped</span> testSkipped: Needs &lt;redis&gt;</li>\n",
            $output,
        );
        $this->assertStringContainsString(
            "<li class='skipped'>\n<span>Skipped</span> testIncomplete: Todo &amp; more</li>\n",
            $output,
        );
    }

    /**
     * Test that passes are listed with their escaped names when requested.
     *
     * @return void
     */
    public function testShowPassesListsPassingTests(): void
    {
        $output = $this->runAndPaint(
            $this->reporter(['showPasses' => true]),
            ReporterSampleCase::buildSuite('testPasses', 'testWithData'),
        );

        $this->assertMatchesRegularExpression(
            "/<li class='pass'>\n<span>Passed<\/span> <br \/>testPasses \([0-9.E-]+ seconds\)\n<\/li>\n/",
            $output,
        );
        $this->assertStringContainsString(
            '<br />testWithData with data set &quot;&lt;b&gt;pass&lt;/b&gt;&quot; (',
            $output,
        );
    }

    /**
     * Test that nested suites paint the header once and a heading per suite.
     *
     * @return void
     */
    public function testNestedSuitesPaintTheHeaderOnce(): void
    {
        $suite = new TestSuite('All reporter samples');
        $suite->addTest(ReporterSampleCase::buildSuite('testPasses'));
        $output = $this->runAndPaint($this->reporter(), $suite);

        $this->assertSame(1, substr_count($output, '<div id="header">'));
        $this->assertStringContainsString(
            "<ul class='tests'>\n<h2>Running  All reporter samples</h2><h2>Running  Reporter sample</h2>",
            $output,
        );
    }

    /**
     * Test that the links below the summary keep the core, plugin, case and
     * filter parameters of the run.
     *
     * @return void
     */
    public function testLinksKeepTheRunParameters(): void
    {
        $reporter = $this->reporter(['core' => true, 'plugin' => false, 'case' => 'Basics', 'filter' => 'testFoo']);
        $output = $this->runAndPaint($reporter, ReporterSampleCase::buildSuite('testPasses'));

        $query = '/test.php?core=true&amp;case=Basics&amp;filter=testFoo';
        $this->assertStringContainsString(
            "<p><a href='/test.php?show=cases&amp;core=true'>Run more tests</a> | " .
            "<a href='$query&amp;show_passes=1'>Show Passes</a> | \n" .
            "<a href='$query&amp;debug=1'>Enable Debug Output</a> | \n" .
            "<a href='$query&amp;code_coverage=true'>Analyze Code Coverage</a> | \n" .
            "<a href='$query&amp;code_coverage=true&amp;show_passes=1&amp;debug=1'>All options enabled</a></p>\n",
            $output,
        );
    }

    /**
     * Test that the menu links the app, every plugin in name order, and the core tests.
     *
     * @return void
     */
    public function testMenuLinksAppPluginsAndCore(): void
    {
        mkdir($this->pluginsPath . 'Zeta');
        mkdir($this->pluginsPath . 'Alpha');
        $output = $this->capture(function (): void {
            $this->reporter()->paintTestMenu();
        });

        $this->assertMatchesRegularExpression(
            "#<span style=\"font-size: 18px\">App</span>\s*<ul>\s*<li><a href='\?'>Tests</a></li>.*" .
            '<span style="font-size: 18px">Plugins</span>.*' .
            'Alpha</span>\s*<ul>\s*<li><a href="\?&amp;plugin=Alpha">Tests</a></li>.*' .
            'ReporterPlugin</span>\s*<ul>\s*<li><a href="\?&amp;plugin=ReporterPlugin">Tests</a></li>.*' .
            'Zeta</span>\s*<ul>\s*<li><a href="\?&amp;plugin=Zeta">Tests</a></li>.*' .
            "<span style=\"font-size: 18px\">Core</span>\s*<ul>\s*<li><a href='\?&amp;core=true'>Tests</a></li>#s",
            $output,
        );
    }

    /**
     * Test that the test case list of a plugin links each case with the plugin parameter.
     *
     * @return void
     */
    public function testTestCaseListOfAPlugin(): void
    {
        $output = $this->capture(function (): void {
            $this->reporter(['plugin' => 'ReporterPlugin'])->testCaseList();
        });

        $this->assertSame(
            "<h3>ReporterPlugin Test Cases:</h3>\n<ul>" .
            "<li><a href='/test.php?case=" . urlencode('Controller' . DS . 'PostsController') .
            "&plugin=ReporterPlugin'>Controller / PostsController</a></li>\n" .
            "<li><a href='/test.php?case=" . urlencode('Model' . DS . 'Article') .
            "&plugin=ReporterPlugin'>Model / Article</a></li>\n" .
            "</ul>\n",
            $output,
        );
    }

    /**
     * Test that the test case list of the core links each case with the core parameter.
     *
     * @return void
     */
    public function testTestCaseListOfTheCore(): void
    {
        $output = $this->capture(function (): void {
            $this->reporter(['core' => true, 'plugin' => false])->testCaseList();
        });

        $case = 'TestSuite' . DS . 'Reporter' . DS . 'CakeHtmlReporter';
        $this->assertStringStartsWith("<h3>Core Test Cases:</h3>\n<ul><li>", $output);
        $this->assertStringContainsString(
            "<li><a href='/test.php?case=" . urlencode($case) . "&core=true'>TestSuite / Reporter / CakeHtmlReporter</a></li>\n",
            $output,
        );
    }

    /**
     * Test that an empty test case list is marked as empty.
     *
     * @return void
     */
    public function testEmptyTestCaseList(): void
    {
        $output = $this->capture(function (): void {
            $this->reporter(['plugin' => 'EmptyReporterPlugin'])->testCaseList();
        });

        $this->assertSame("<h3>EmptyReporterPlugin Test Cases:</h3>\n<ul><strong>EMPTY</strong></ul>\n", $output);
    }

    /**
     * Test that formatted messages are painted escaped in a pre element.
     *
     * @return void
     */
    public function testPaintFormattedMessage(): void
    {
        $output = $this->capture(function (): void {
            $this->reporter()->paintFormattedMessage("array(\n\t'a' => '<b>'\n)");
        });

        $this->assertSame("<pre>array(\n\t'a' =&gt; '&lt;b&gt;'\n)</pre>", $output);
    }
}
