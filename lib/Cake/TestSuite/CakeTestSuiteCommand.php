<?php

use PHPUnit\TextUI\Command;

/**
 * TestRunner for CakePHP Test suite.
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
 * @package       Cake.TestSuite
 * @since         CakePHP(tm) v 2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

App::uses('CakeTestLoader', 'TestSuite');
App::uses('CakeTestSuite', 'TestSuite');
App::uses('CakeTestCase', 'TestSuite');
App::uses('ControllerTestCase', 'TestSuite');
App::uses('CakeTestModel', 'TestSuite/Fixture');

/**
 * Class to customize loading of test suites from CLI
 *
 * @package       Cake.TestSuite
 */
class CakeTestSuiteCommand extends Command
{
    /**
     * Construct method
     *
     * @param string $loader The loader instance to use.
     * @param array $params list of options to be used for this run
     * @throws MissingTestLoaderException When a loader class could not be found.
     */
    public function __construct(string $loader, array $params = [])
    {
        if ($loader && !class_exists($loader)) {
            throw new MissingTestLoaderException(['class' => $loader]);
        }
        $this->arguments['loader'] = $loader;
        $this->arguments['test'] = $params['case'];
        $this->arguments['testFile'] = $params;
        $this->_params = $params;

        $this->longOptions['fixture='] = 'handleFixture';
        $this->longOptions['output='] = 'handleReporter';
    }

    /**
     * Run
     *
     * @param array $argv
     * @param bool $exit
     * @return int
     * @throws Exception
     */
    public function run(array $argv, bool $exit = true): int
    {
        $argv[] = $this->_resolveTestFile($this->_params['case'], $this->_params);

        return parent::run($argv, $exit);
    }

    /**
     * Handler for customizing the FixtureManager class/
     *
     * @param string $class Name of the class that will be the fixture manager
     * @return void
     */
    public function handleFixture($class)
    {
        $this->arguments['fixtureManager'] = $class;
    }

    /**
     * Handles output flag used to change printing on webrunner.
     *
     * @param string $reporter The reporter class to use.
     * @return CakeBaseReporter
     */
    public function handleReporter($reporter)
    {
        $reporter = ucwords($reporter);
        $coreClass = 'Cake' . $reporter . 'Reporter';
        App::uses($coreClass, 'TestSuite/Reporter');

        $appClass = $reporter . 'Reporter';
        App::uses($appClass, 'TestSuite/Reporter');

        if (!class_exists($appClass)) {
            $object = new $coreClass(null, $this->_params);
        } else {
            $object = new $appClass(null, $this->_params);
        }

        return $this->arguments['printer'] = $object;
    }

    /**
     * Convert path fragments used by CakePHP's test runner to absolute paths that can be fed to PHPUnit.
     *
     * @param string $filePath The file path to load.
     * @param array $params Additional parameters.
     * @return string Converted path fragments.
     */
    protected function _resolveTestFile(string $filePath, array $params): string
    {
        $basePath = static::_basePath($params) . DS . $filePath;
        $ending = 'Test.php';

        return str_ends_with($basePath, $ending) ? $basePath : $basePath . $ending;
    }

    /**
     * Generates the base path to a set of tests based on the parameters.
     *
     * @param array|null $params The path parameters.
     * @return string|null The base path.
     */
    protected static function _basePath(?array $params): ?string
    {
        $result = null;
        if (!empty($params['core'])) {
            $result = CORE_TEST_CASES;
        } elseif (!empty($params['plugin'])) {
            if (!CakePlugin::loaded($params['plugin'])) {
                try {
                    CakePlugin::load($params['plugin']);
                    $result = CakePlugin::path($params['plugin']) . 'Test' . DS . 'Case';
                } catch (MissingPluginException) {
                }
            } else {
                $result = CakePlugin::path($params['plugin']) . 'Test' . DS . 'Case';
            }
        } elseif (!empty($params['app'])) {
            $result = APP_TEST_CASES;
        }

        return $result;
    }
}
