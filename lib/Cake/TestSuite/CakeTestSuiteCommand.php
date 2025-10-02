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
     * @param mixed $loader The loader instance to use.
     * @param array $params list of options to be used for this run
     * @throws MissingTestLoaderException When a loader class could not be found.
     */
    public function __construct($loader, $params = [])
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
}
