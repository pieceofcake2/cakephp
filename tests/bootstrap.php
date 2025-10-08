<?php
/**
 * Bootstrap for phpunit command
 */

use PHPUnit\Util\ErrorHandler;

if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}
if (!defined('ROOT')) {
    define('ROOT', dirname(__DIR__, 1) . DS . 'vendor' . DS . 'pieceofcake2' . DS . 'app');
}
if (!defined('APP_DIR')) {
    define('APP_DIR', 'src');
}
if (!defined('APP')) {
    define('APP', ROOT . DS . APP_DIR . DS);
}
if (!defined('CONFIG')) {
    define('CONFIG', ROOT . DS . 'config' . DS);
}
if (!defined('TESTS')) {
    define('TESTS', ROOT . DS . 'tests' . DS);
}
if (!defined('TMP')) {
    define('TMP', ROOT . DS . 'tmp' . DS);
}
if (!defined('LOGS')) {
    define('LOGS', ROOT . DS . 'logs' . DS);
}
if (!defined('WEBROOT_DIR')) {
    define('WEBROOT_DIR', 'webroot');
}
if (!defined('WWW_ROOT')) {
    define('WWW_ROOT', ROOT . DS . WEBROOT_DIR . DS);
}

if (isset($GLOBALS['_composer_autoload_path'])) {
    require_once $GLOBALS['_composer_autoload_path'];
} else {
    $rootInstall = dirname(__DIR__, 1) . DS . 'vendor' . DS . 'autoload.php';
    $composerInstall = dirname(__DIR__, 3) . DS . 'autoload.php';

    if (file_exists($composerInstall)) {
        require_once $composerInstall;
    } elseif (file_exists($rootInstall)) {
        require_once $rootInstall;
    } else {
        trigger_error('Composer autoload file not found. ' .
            'Please run "composer install" to generate the autoload file.', E_USER_ERROR);
    }
}

require_once 'Cake' . DS . 'Console' . DS . 'ShellDispatcher.php';
new ShellDispatcher([$_SERVER['argv'][0]]);

/*
 * loading of src/Cake/TestSuite/CakeTestSuiteDispatcher.php
 * In bootstrap.php, it is sufficient if the const(s) are defined outside the class of CakeTestSuiteDispatcher.php.
 * However, when loading CakeTestSuiteDispatcher.php in the unit test, a double definition of const(s) error occurs,
 * so load it here.
 */
App::uses('CakeTestSuiteDispatcher', 'TestSuite');
App::load('CakeTestSuiteDispatcher');

/*
 * Classes that can be used without declaring App::uses()
 */
App::uses('ClassRegistry', 'Utility');
App::uses('CakeTestCase', 'TestSuite');
App::uses('CakeTestSuite', 'TestSuite');
App::uses('ControllerTestCase', 'TestSuite');
App::uses('CakeTestModel', 'TestSuite/Fixture');

set_error_handler(new ErrorHandler(true, true, true, true));
