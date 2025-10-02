<?php
/**
 * Bootstrap for phpunit command
 */

use PHPUnit\Util\ErrorHandler;

if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}
require_once __DIR__ . DS . 'bootstrap' . DS . 'cake_dot_php.php';

/*
 * loading of lib/Cake/TestSuite/CakeTestSuiteDispatcher.php
 * In bootstrap.php, it is sufficient if the const(s) are defined outside the class of CakeTestSuiteDispatcher.php.
 * However, when loading CakeTestSuiteDispatcher.php in the unit test, a double definition of const(s) error occurs,
 * so load it here.
 */
App::uses('CakeTestSuiteDispatcher', 'TestSuite');
require_once 'Cake' . DS . 'Console' . DS . 'ShellDispatcher.php';

/*
 * Classes that can be used without declaring App::uses()
 */
App::uses('ClassRegistry', 'Utility');
App::uses('CakeTestCase', 'TestSuite');
App::uses('CakeTestSuite', 'TestSuite');
App::uses('ControllerTestCase', 'TestSuite');
App::uses('CakeTestModel', 'TestSuite/Fixture');

set_error_handler(new ErrorHandler(true, true, true, true));
