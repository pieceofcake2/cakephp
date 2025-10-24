<?php

namespace Cake\TestSuite;

use Cake\Core\App;
use Cake\Core\CakePlugin;
use Cake\Error\MissingPluginException;
use Cake\Error\MissingTestLoaderException;
use Cake\TestSuite\Reporter\CakeBaseReporter;
use Exception;
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

/**
 * Class to customize loading of test suites from CLI
 *
 * @package       Cake.TestSuite
 */
class CakeTestSuiteCommand extends Command
{
    /**
     * @var array
     */
    protected array $_params = [];

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
        $className = $reporter . 'Reporter';
        $coreClassName = 'Cake' . $reporter . 'Reporter';

        $resolvedClass = App::className($coreClassName, 'TestSuite/Reporter');

        if (!$resolvedClass) {
            $resolvedClass = App::className($className, 'TestSuite/Reporter');
        }

        if (!$resolvedClass) {
            if (!class_exists($className)) {
                $className = $coreClassName;
            }
        } else {
            $className = $resolvedClass;
        }

        $object = new $className(null, $this->_params);

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
            $result = CORE_TESTS . DS . 'TestCase';
        } elseif (!empty($params['plugin'])) {
            if (!CakePlugin::loaded($params['plugin'])) {
                try {
                    CakePlugin::load($params['plugin']);
                    $pluginPath = CakePlugin::path($params['plugin']);

                    if (is_dir($pluginPath . 'tests' . DS . 'TestCase')) {
                        $result = $pluginPath . 'tests' . DS . 'TestCase';
                    } else {
                        $result = $pluginPath . 'Test' . DS . 'Case';
                    }
                } catch (MissingPluginException) {
                }
            } else {
                $pluginPath = CakePlugin::path($params['plugin']);

                if (is_dir($pluginPath . 'tests' . DS . 'TestCase')) {
                    $result = $pluginPath . 'tests' . DS . 'TestCase';
                } else {
                    $result = $pluginPath . 'Test' . DS . 'Case';
                }
            }
        } elseif (!empty($params['app'])) {
            if (is_dir(TESTS . 'TestCase')) {
                $result = TESTS . 'TestCase';
            } else {
                $result = TESTS . 'Case';
            }
        }

        return $result;
    }
}
