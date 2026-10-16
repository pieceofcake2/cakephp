<?php

/**
 * Test case for HtmlCoverageReport
 *
 * PHP5
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
 * @package       Cake.Test.Case.TestSuite
 * @since         CakePHP(tm) v 2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

use PHPUnit\Framework\TestResult;
use SebastianBergmann\CodeCoverage\CodeCoverage;
use SebastianBergmann\CodeCoverage\Driver\Driver;
use SebastianBergmann\CodeCoverage\Filter;
use SebastianBergmann\CodeCoverage\ProcessedCodeCoverageData;
use SebastianBergmann\CodeCoverage\RawCodeCoverageData;

/**
 * HtmlCoverageReportTest
 *
 * @package       Cake.Test.Case.TestSuite
 */
class HtmlCoverageReportTest extends CakeTestCase
{
    /**
     * setUp
     *
     * @return void
     */
    public function setUp(): void
    {
        // This test can not be run with PHPUnit 9+.
        // Because CakeBaseReporter depend on PHPUnit_TextUI_ResultPrinter.
        // If run the test with the phpunit command, Result Printer is also PHPUnit's to be used.
        // Therefore, CakeBaseReporter are unnecessary.
        parent::setUp();
        App::build([
            'Plugin' => [CORE_TESTS . DS . 'test_app' . DS . 'Plugin' . DS],
        ], App::RESET);
        CakePlugin::load(['TestPlugin']);
        $reporter = new CakeHtmlReporter();
        $reporter->params = ['app' => false, 'plugin' => false, 'group' => false];
        $coverage = [];
        $this->Coverage = new HtmlCoverageReport($coverage, $reporter);
    }

    /**
     * test getting the path filters.
     *
     * @return void
     */
    public function testGetPathFilter(): void
    {
        $this->Coverage->appTest = false;
        $result = $this->Coverage->getPathFilter();
        $this->assertEquals(CAKE, $result);

        $this->Coverage->appTest = true;
        $result = $this->Coverage->getPathFilter();
        $this->assertEquals(ROOT . DS . APP_DIR . DS, $result);

        $this->Coverage->appTest = false;
        $this->Coverage->pluginTest = 'TestPlugin';
        $result = $this->Coverage->getPathFilter();
        $this->assertEquals(CakePlugin::path('TestPlugin'), $result);
    }

    /**
     * test filtering coverage data.
     *
     * @return void
     */
    public function testFilterCoverageDataByPathRemovingElements(): void
    {
        $data = [
            CAKE . 'dispatcher.php' => [
                10 => -1,
                12 => 1,
            ],
            APP . 'app_model.php' => [
                50 => 1,
                52 => -1,
            ],
        ];
        $this->Coverage->setCoverage($data);
        $result = $this->Coverage->filterCoverageDataByPath(CAKE);
        $this->assertTrue(isset($result[CAKE . 'dispatcher.php']));
        $this->assertFalse(isset($result[APP . 'app_model.php']));
    }

    /**
     * test generating HTML reports from file arrays.
     *
     * @return void
     */
    public function testGenerateDiff(): void
    {
        $file = [
            'line 1',
            'line 2',
            'line 3',
            'line 4',
            'line 5',
            'line 6',
            'line 7',
            'line 8',
            'line 9',
            'line 10',
        ];
        $coverage = [
            1 => [['id' => 'HtmlCoverageReportTest::testGenerateDiff']],
            2 => -2,
            3 => [['id' => 'HtmlCoverageReportTest::testGenerateDiff']],
            4 => [['id' => 'HtmlCoverageReportTest::testGenerateDiff']],
            5 => -1,
            6 => [['id' => 'HtmlCoverageReportTest::testGenerateDiff']],
            7 => [['id' => 'HtmlCoverageReportTest::testGenerateDiff']],
            8 => [['id' => 'HtmlCoverageReportTest::testGenerateDiff']],
            9 => -1,
            10 => [['id' => 'HtmlCoverageReportTest::testGenerateDiff']],
        ];
        $result = $this->Coverage->generateDiff('myfile.php', $file, $coverage);
        $this->assertMatchesRegularExpression('/myfile\.php Code coverage\: \d+\.?\d*\%/', $result);
        $this->assertMatchesRegularExpression('/<div class="code-coverage-results" id\="coverage\-myfile\.php-' . md5('myfile.php') . '"/', $result);
        $this->assertMatchesRegularExpression('/<pre>/', $result);
        foreach ($file as $i => $line) {
            $this->assertTrue(!str_starts_with($line, $result), 'Content is missing ' . $i);
            $class = 'covered';
            if (in_array($i + 1, [5, 9, 2])) {
                $class = 'uncovered';
            }
            if ($i + 1 === 2) {
                $class .= ' dead';
            }
            $this->assertTrue(!str_starts_with($class, $result), 'Class name is wrong ' . $i);
        }
    }

    /**
     * Test that coverage works with phpunit 3.6 as the data formats from coverage are totally different.
     *
     * @return void
     */
    public function testPhpunit36Compatibility(): void
    {
        $file = [
            'line 1',
            'line 2',
            'line 3',
            'line 4',
            'line 5',
            'line 6',
            'line 7',
            'line 8',
            'line 9',
            'line 10',
        ];
        $coverage = [
            1 => ['HtmlCoverageReportTest::testGenerateDiff'],
            2 => null,
            3 => ['HtmlCoverageReportTest::testGenerateDiff'],
            4 => ['HtmlCoverageReportTest::testGenerateDiff'],
            5 => [],
            6 => ['HtmlCoverageReportTest::testGenerateDiff'],
            7 => ['HtmlCoverageReportTest::testGenerateDiff'],
            8 => ['HtmlCoverageReportTest::testGenerateDiff'],
            9 => [],
            10 => ['HtmlCoverageReportTest::testSomething', 'HtmlCoverageReportTest::testGenerateDiff'],
        ];

        $result = $this->Coverage->generateDiff('myfile.php', $file, $coverage);
        $this->assertMatchesRegularExpression('/myfile\.php Code coverage\: \d+\.?\d*\%/', $result);
        $this->assertMatchesRegularExpression('/<div class="code-coverage-results" id\="coverage\-myfile\.php-' . md5('myfile.php') . '"/', $result);
        $this->assertMatchesRegularExpression('/<pre>/', $result);
        foreach ($file as $i => $line) {
            $this->assertTrue(!str_starts_with($line, $result), 'Content is missing ' . $i);
            $class = 'covered';
            if (in_array($i + 1, [5, 9, 2])) {
                $class = 'uncovered';
            }
            if ($i + 1 === 2) {
                $class .= ' dead';
            }
            $this->assertTrue(!str_starts_with($class, $result), 'Class name is wrong ' . $i);
        }
    }

    /**
     * test that covering methods show up as title attributes for lines.
     *
     * @return void
     */
    public function testCoveredLinesTitleAttributes(): void
    {
        $file = [
            'line 1',
            'line 2',
            'line 3',
            'line 4',
            'line 5',
        ];

        $coverage = [
            1 => [['id' => 'HtmlCoverageReportTest::testAwesomeness']],
            2 => -2,
            3 => [['id' => 'HtmlCoverageReportTest::testCakeIsSuperior']],
            4 => [['id' => 'HtmlCoverageReportTest::testOther']],
            5 => -1,
        ];

        $result = $this->Coverage->generateDiff('myfile.php', $file, $coverage);

        $this->assertTrue(
            str_contains($result, "title=\"Covered by:\nHtmlCoverageReportTest::testAwesomeness\n\"><span class=\"line-num\">1"),
            'Missing method coverage for line 1',
        );
        $this->assertTrue(
            str_contains($result, "title=\"Covered by:\nHtmlCoverageReportTest::testCakeIsSuperior\n\"><span class=\"line-num\">3"),
            'Missing method coverage for line 3',
        );
        $this->assertTrue(
            str_contains($result, "title=\"Covered by:\nHtmlCoverageReportTest::testOther\n\"><span class=\"line-num\">4"),
            'Missing method coverage for line 4',
        );
        $this->assertTrue(
            str_contains($result, 'title=""><span class="line-num">5'),
            'Coverage report is wrong for line 5',
        );
    }

    /**
     * tearDown
     *
     * @return void
     */
    public function tearDown(): void
    {
        CakePlugin::unload();
        unset($this->Coverage);

        parent::tearDown();
    }

    /**
     * Creates a test result carrying line coverage data for one file.
     *
     * @param string $file The covered file.
     * @return TestResult
     */
    protected function _resultWithCoverage($file): TestResult
    {
        $driver = new class extends Driver {
            public function nameAndVersion(): string
            {
                return 'Fake driver';
            }

            public function start(): void
            {
            }

            public function stop(): RawCodeCoverageData
            {
                return RawCodeCoverageData::fromXdebugWithoutPathCoverage([]);
            }
        };
        $data = new ProcessedCodeCoverageData();
        $data->setLineCoverage([$file => [1 => ['HtmlCoverageReportTest::testReporterFooter'], 2 => []]]);
        $coverage = new CodeCoverage($driver, new Filter());
        $coverage->setData($data);

        $result = new TestResult();
        $result->setCodeCoverage($coverage);

        return $result;
    }

    /**
     * Test that the text reporter paints the code coverage of a test run.
     *
     * @return void
     */
    public function testTextReporterPaintsCoverage(): void
    {
        $reporter = new CakeTextReporter(null, ['codeCoverage' => true, 'app' => false, 'plugin' => false]);

        ob_start();
        $reporter->paintFooter($this->_resultWithCoverage(CAKE . 'basics.php'));
        $output = ob_get_clean();

        $this->assertStringContainsString('Coverage Report', $output);
        $this->assertStringContainsString(CAKE . 'basics.php', $output);
    }

    /**
     * Test that the HTML reporter paints the code coverage of a test run.
     *
     * @return void
     */
    public function testHtmlReporterPaintsCoverage(): void
    {
        $reporter = new CakeHtmlReporter(null, ['codeCoverage' => true, 'app' => false, 'plugin' => false, 'baseDir' => '', 'baseUrl' => '/', 'core' => true, 'case' => 'Basics']);

        // paintDocumentEnd() flushes the current output buffer, so collect the output in an outer one.
        $level = ob_get_level();
        ob_start();
        ob_start();
        $reporter->paintFooter($this->_resultWithCoverage(CAKE . 'basics.php'));
        $output = '';
        while (ob_get_level() > $level) {
            $output = ob_get_clean() . $output;
        }

        $this->assertStringContainsString('Code coverage', $output);
        $this->assertStringContainsString('basics.php', $output);
    }
}
