<?php
/**
 * CakeTestSuiteDispatcher controls dispatching TestSuite web based requests.
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
 * @package       Cake.TestSuite
 * @since         CakePHP(tm) v 1.3
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\TestSuite;

use Cake\Error\MissingConnectionException;
use Cake\TestSuite\Reporter\CakeBaseReporter;
use PHPUnit\Framework\TestCase;

/**
 * Path to the tests directory of the app.
 */

/**
 * CakeTestSuiteDispatcher handles web requests to the test suite and runs the correct action.
 *
 * @package       Cake.TestSuite
 */
class CakeTestSuiteDispatcher
{
    /**
     * 'Request' parameters
     *
     * @var array
     */
    public $params = [
        'codeCoverage' => false,
        'case' => null,
        'core' => false,
        'app' => false,
        'plugin' => null,
        'output' => 'html',
        'show' => 'groups',
        'show_passes' => false,
        'filter' => false,
        'fixture' => null,
    ];

    /**
     * Baseurl for the request
     *
     * @var string
     */
    protected $_baseUrl;

    /**
     * Base dir of the request. Used for accessing assets.
     *
     * @var string
     */
    protected $_baseDir;

    /**
     * boolean to set auto parsing of params.
     *
     * @var bool
     */
    protected $_paramsParsed = false;

    /**
     * reporter instance used for the request
     *
     * @var CakeBaseReporter
     */
    protected static $_Reporter = null;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->_baseUrl = $_SERVER['PHP_SELF'];
        $dir = rtrim(dirname($this->_baseUrl), '\\');
        $this->_baseDir = $dir === '/' ? $dir : $dir . '/';
    }

    /**
     * Runs the actions required by the URL parameters.
     *
     * @return void
     */
    public function dispatch()
    {
        $this->_checkPHPUnit();
        $this->_parseParams();

        if ($this->params['case']) {
            $this->_runTestCase();
        } else {
            $this->_testCaseList();
        }

        $output = ob_get_clean();
        echo $output;
    }

    /**
     * Static method to initialize the test runner, keeps global space clean
     *
     * @return void
     */
    public static function run()
    {
        $dispatcher = new CakeTestSuiteDispatcher();
        $dispatcher->dispatch();
    }

    /**
     * Checks that PHPUnit is installed. Will exit if it doesn't
     *
     * @return void
     */
    protected function _checkPHPUnit()
    {
        $found = $this->loadTestFramework();
        if (!$found) {
            $baseDir = $this->_baseDir;
            include CAKE . 'TestSuite' . DS . 'templates' . DS . 'phpunit.php';
            exit;
        }
    }

    /**
     * Checks for the existence of the test framework files
     *
     * @return bool true if found, false otherwise
     */
    public function loadTestFramework()
    {
        return class_exists(TestCase::class);
    }

    /**
     * Checks for the xdebug extension required to do code coverage. Displays an error
     * if xdebug isn't installed.
     *
     * @return void
     */
    protected function _checkXdebug()
    {
        if (!extension_loaded('xdebug')) {
            $baseDir = $this->_baseDir;
            include CAKE . 'TestSuite' . DS . 'templates' . DS . 'xdebug.php';
            exit;
        }
    }

    /**
     * Generates a page containing the a list of test cases that could be run.
     *
     * @return void
     */
    protected function _testCaseList()
    {
        $command = new CakeTestSuiteCommand('', $this->params);
        $Reporter = $command->handleReporter($this->params['output']);
        $Reporter->paintDocumentStart();
        $Reporter->paintTestMenu();
        $Reporter->testCaseList();
        $Reporter->paintDocumentEnd();
    }

    /**
     * Sets the params, calling this will bypass the auto parameter parsing.
     *
     * @param array $params Array of parameters for the dispatcher
     * @return void
     */
    public function setParams($params)
    {
        $this->params = $params;
        $this->_paramsParsed = true;
    }

    /**
     * Parse URL params into a 'request'
     *
     * @return void
     */
    protected function _parseParams()
    {
        if (!$this->_paramsParsed) {
            if (!isset($_SERVER['SERVER_NAME'])) {
                $_SERVER['SERVER_NAME'] = '';
            }
            foreach ($this->params as $key => $value) {
                if (isset($_GET[$key])) {
                    $this->params[$key] = $_GET[$key];
                }
            }
            if (isset($_GET['code_coverage'])) {
                $this->params['codeCoverage'] = true;
                $this->_checkXdebug();
            }
        }
        if (empty($this->params['plugin']) && empty($this->params['core'])) {
            $this->params['app'] = true;
        }
        $this->params['baseUrl'] = $this->_baseUrl;
        $this->params['baseDir'] = $this->_baseDir;
    }

    /**
     * Runs a test case file.
     *
     * @return void
     */
    protected function _runTestCase()
    {
        $commandArgs = [
            'case' => $this->params['case'],
            'core' => $this->params['core'],
            'app' => $this->params['app'],
            'plugin' => $this->params['plugin'],
            'codeCoverage' => $this->params['codeCoverage'],
            'showPasses' => !empty($this->params['show_passes']),
            'baseUrl' => $this->_baseUrl,
            'baseDir' => $this->_baseDir,
        ];

        $options = [
            '--filter', $this->params['filter'],
            '--output', $this->params['output'],
            '--fixture', $this->params['fixture'],
        ];
        restore_error_handler();

        try {
            static::time();
            $command = new CakeTestSuiteCommand(CakeTestLoader::class, $commandArgs);
            $command->run($options, false);
        } catch (MissingConnectionException $exception) {
            ob_end_clean();
            $baseDir = $this->_baseDir;
            include CAKE . 'TestSuite' . DS . 'templates' . DS . 'missing_connection.php';
            exit;
        }
    }

    /**
     * Sets a static timestamp
     *
     * @param bool $reset to set new static timestamp.
     * @return int timestamp
     */
    public static function time($reset = false)
    {
        static $now;
        if ($reset || !$now) {
            $now = time();
        }

        return $now;
    }

    /**
     * Returns formatted date string using static time
     * This method is being used as formatter for created, modified and updated fields in Model::save()
     *
     * @param string $format format to be used.
     * @return string formatted date
     */
    public static function date($format)
    {
        return date($format, static::time());
    }

    /**
     * Returns the path of the stylesheet or of an icon of the test runner, relative to the webroot.
     *
     * The application skeleton keeps them in `webroot/test_suite/`, apart from the assets of the
     * application. Applications created from earlier skeletons have them in `webroot/css/` and
     * `webroot/img/`, which are used when the file is not in `webroot/test_suite/`.
     *
     * @param string $file The name of the file, e.g. `cake.generic.css` or `test-pass-icon.png`.
     * @param string|null $webroot The webroot directory with a trailing separator, `WWW_ROOT` by default.
     * @return string The path, e.g. `test_suite/cake.generic.css` or `css/cake.generic.css`.
     */
    public static function assetPath($file, $webroot = null)
    {
        if ($webroot === null && defined('WWW_ROOT')) {
            $webroot = WWW_ROOT;
        }
        if ($webroot !== null && is_file($webroot . 'test_suite' . DS . $file)) {
            return 'test_suite/' . $file;
        }

        return (substr($file, -4) === '.css' ? 'css/' : 'img/') . $file;
    }
}

class_alias(CakeTestSuiteDispatcher::class, 'CakeTestSuiteDispatcher');
