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
 * @package       Cake.Test.Case.TestSuite
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Test\TestCase\TestSuite;

use Cake\Core\App;
use Cake\Core\CakePlugin;
use Cake\TestSuite\CakeTestCase;
use Cake\TestSuite\CakeTestSuiteDispatcher;
use Cake\Utility\Folder;
use ReflectionMethod;

/**
 * Dispatcher that records the check for Xdebug instead of exiting when it is missing
 *
 * @package       Cake.Test.Case.TestSuite
 */
class XdebugCheckingTestSuiteDispatcher extends CakeTestSuiteDispatcher
{
    /**
     * Whether the check for Xdebug was made
     *
     * @var bool
     */
    public $xdebugChecked = false;

    /**
     * Records the check for Xdebug
     *
     * @return void
     */
    protected function _checkXdebug(): void
    {
        $this->xdebugChecked = true;
    }
}

/**
 * CakeTestSuiteDispatcherTest
 *
 * @package       Cake.Test.Case.TestSuite
 */
class CakeTestSuiteDispatcherTest extends CakeTestCase
{
    /**
     * Temporary plugins directory
     *
     * @var string
     */
    protected string $pluginsPath;

    /**
     * The $_GET of the test runner
     *
     * @var array
     */
    protected array $get;

    /**
     * The $_SERVER of the test runner
     *
     * @var array
     */
    protected array $server;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->get = $_GET;
        $this->server = $_SERVER;
        $_GET = [];
        $_SERVER['PHP_SELF'] = '/test.php';
        $_SERVER['SERVER_NAME'] = 'localhost';

        $this->pluginsPath = TMP . 'tests' . DS . 'cake_test_suite_dispatcher_plugins' . DS;
        $files = [
            'ListedPlugin' . DS . 'tests' . DS . 'TestCase' . DS . 'Model' . DS . 'PostTest.php',
            'ListedPlugin' . DS . 'tests' . DS . 'TestCase' . DS . 'AllPluginTest.php',
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
        $_GET = $this->get;
        $_SERVER = $this->server;
        CakePlugin::unload('ListedPlugin');
        $Folder = new Folder($this->pluginsPath);
        $Folder->delete();

        parent::tearDown();
    }

    /**
     * Parses the query parameters with the given dispatcher.
     *
     * @param CakeTestSuiteDispatcher $dispatcher The dispatcher.
     * @return array The parsed parameters.
     */
    protected function _parse(CakeTestSuiteDispatcher $dispatcher): array
    {
        $method = new ReflectionMethod(CakeTestSuiteDispatcher::class, '_parseParams');
        if (PHP_VERSION_ID < 80100) {
            $method->setAccessible(true);
        }
        $method->invoke($dispatcher);

        return $dispatcher->params;
    }

    /**
     * Runs the test runner the way webroot/test.php does, and returns its output.
     *
     * @return string
     */
    protected function _run(): string
    {
        $level = ob_get_level();
        // dispatch() ends the buffer that test.php starts and echoes it, so a second buffer catches it.
        ob_start();
        ob_start();
        try {
            CakeTestSuiteDispatcher::run();
        } finally {
            $output = '';
            while (ob_get_level() > $level) {
                $output = ob_get_clean() . $output;
            }
        }

        return $output;
    }

    /**
     * Test that the base URL is the script and the base directory is the directory of the
     * script with a trailing slash, or the root.
     *
     * @return void
     */
    public function testBaseUrlAndBaseDir(): void
    {
        $_SERVER['PHP_SELF'] = '/app/webroot/test.php';
        $params = $this->_parse(new CakeTestSuiteDispatcher());
        $this->assertSame('/app/webroot/test.php', $params['baseUrl']);
        $this->assertSame('/app/webroot/', $params['baseDir']);

        $_SERVER['PHP_SELF'] = '/test.php';
        $params = $this->_parse(new CakeTestSuiteDispatcher());
        $this->assertSame('/test.php', $params['baseUrl']);
        $this->assertSame('/', $params['baseDir']);
    }

    /**
     * Test that the known query parameters are read, that others are ignored, and that a core
     * case is not an app case.
     *
     * @return void
     */
    public function testParseParamsReadsKnownQueryParameters(): void
    {
        unset($_SERVER['SERVER_NAME']);
        $_GET = [
            'case' => 'Model/Model',
            'core' => 'true',
            'output' => 'text',
            'show_passes' => '1',
            'filter' => 'testSave',
            'fixture' => 'MyFixtureManager',
            'unknown' => 'ignored',
        ];

        $params = $this->_parse(new CakeTestSuiteDispatcher());

        $this->assertSame([
            'codeCoverage' => false,
            'case' => 'Model/Model',
            'core' => 'true',
            'app' => false,
            'plugin' => null,
            'output' => 'text',
            'show' => 'groups',
            'show_passes' => '1',
            'filter' => 'testSave',
            'fixture' => 'MyFixtureManager',
            'baseUrl' => '/test.php',
            'baseDir' => '/',
        ], $params);
        // The reporters print the server name in the links
        $this->assertSame('', $_SERVER['SERVER_NAME']);
    }

    /**
     * Test that the app tests are chosen when neither the core nor a plugin is asked for.
     *
     * @return void
     */
    public function testParseParamsChoosesAppOrPlugin(): void
    {
        $params = $this->_parse(new CakeTestSuiteDispatcher());
        $this->assertTrue($params['app']);
        $this->assertNull($params['plugin']);

        $_GET = ['plugin' => 'ListedPlugin'];
        $params = $this->_parse(new CakeTestSuiteDispatcher());
        $this->assertFalse($params['app']);
        $this->assertSame('ListedPlugin', $params['plugin']);
    }

    /**
     * Test that the code_coverage query parameter turns on code coverage and checks for Xdebug.
     *
     * @return void
     */
    public function testParseParamsCodeCoverage(): void
    {
        $dispatcher = new XdebugCheckingTestSuiteDispatcher();
        $params = $this->_parse($dispatcher);
        $this->assertFalse($params['codeCoverage']);
        $this->assertFalse($dispatcher->xdebugChecked);

        $_GET = ['code_coverage' => '1', 'case' => 'Model/Model'];
        $dispatcher = new XdebugCheckingTestSuiteDispatcher();
        $params = $this->_parse($dispatcher);
        $this->assertTrue($params['codeCoverage']);
        $this->assertTrue($dispatcher->xdebugChecked);
    }

    /**
     * Test that parameters set with setParams() are not replaced by the query parameters.
     *
     * @return void
     */
    public function testSetParamsBypassesQueryParameters(): void
    {
        $_GET = ['case' => 'FromQuery', 'core' => 'true', 'code_coverage' => '1'];
        $dispatcher = new XdebugCheckingTestSuiteDispatcher();
        $dispatcher->setParams(['case' => 'FromParams', 'core' => false, 'plugin' => null, 'codeCoverage' => false]);

        $params = $this->_parse($dispatcher);

        $this->assertSame([
            'case' => 'FromParams',
            'core' => false,
            'plugin' => null,
            'codeCoverage' => false,
            'app' => true,
            'baseUrl' => '/test.php',
            'baseDir' => '/',
        ], $params);
        $this->assertFalse($dispatcher->xdebugChecked);
    }

    /**
     * Test that a request without a case lists the test cases of the plugin with the text
     * reporter: the plugin heading, then one line per case in name order.
     *
     * @return void
     */
    public function testDispatchListsPluginTestCasesInOrder(): void
    {
        $_GET = ['plugin' => 'ListedPlugin', 'output' => 'text'];

        $lines = explode("\n", $this->_run());

        $this->assertSame('ListedPlugin Test Cases:', $lines[0]);
        $this->assertCount(5, $lines, 'The heading, two cases, a blank line and the end.');
        $this->assertStringStartsWith('localhost/test.php?case=AllPlugin&', $lines[1]);
        $this->assertStringStartsWith('localhost/test.php?case=Model', $lines[2]);
        $this->assertSame(['', ''], [$lines[3], $lines[4]]);
    }

    /**
     * Test that each listed case of a plugin links to a run of that case of the
     * plugin, with the case name URL-encoded.
     *
     * @return void
     */
    public function testDispatchListsPluginTestCasesWithThePlugin(): void
    {
        $_GET = ['plugin' => 'ListedPlugin', 'output' => 'text'];

        $output = $this->_run();

        $expected = "ListedPlugin Test Cases:\n" .
            "localhost/test.php?case=AllPlugin&plugin=ListedPlugin&output=text\n" .
            'localhost/test.php?case=' . urlencode('Model' . DS . 'Post') . "&plugin=ListedPlugin&output=text\n" .
            "\n";
        $this->assertSame($expected, $output);
    }

    /**
     * Test that the list of a plugin that does not exist says once that it is empty.
     *
     * @return void
     */
    public function testDispatchListsMissingPluginAsEmpty(): void
    {
        $_GET = ['plugin' => 'NoSuchListedPlugin', 'output' => 'text'];

        $output = $this->_run();

        $this->assertSame("NoSuchListedPlugin Test Cases:\nEMPTY\n", $output);
    }
}
