<?php
/**
 * Web Access Frontend for TestSuite
 *
 * CakePHP(tm) Tests <https://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://book.cakephp.org/2.0/en/development/testing.html
 * @package       app.webroot
 * @since         CakePHP(tm) v 1.2.0.4433
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

set_time_limit(0);
ini_set('display_errors', 1);

/**
 * Use the DS to separate the directories in other defines
 */
if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}

/**
 * These defines should only be edited if you have CakePHP installed in
 * a directory layout other than the way it is distributed.
 * When using custom settings be sure to use the DS and do not add a trailing DS.
 */

/**
 * The full path to the directory which holds "app", WITHOUT a trailing DS.
 */
if (!defined('ROOT')) {
    define('ROOT', dirname(__DIR__, 2));
}

/**
 * The actual directory name for the "app".
 */
if (!defined('APP_DIR')) {
    define('APP_DIR', basename(dirname(__DIR__)));
}

/**
 * Config Directory
 */
if (!defined('CONFIG')) {
    define('CONFIG', ROOT . DS . APP_DIR . DS . 'Config' . DS);
}

/**
 * Path to the vendors directory.
 */
if (!defined('VENDORS')) {
    define('VENDORS', ROOT . DS . 'vendors' . DS);
}

/**
 * Editing below this line should not be necessary.
 * Change at your own risk.
 */
if (!defined('WEBROOT_DIR')) {
    define('WEBROOT_DIR', basename(__DIR__));
}
if (!defined('WWW_ROOT')) {
    define('WWW_ROOT', __DIR__ . DS);
}

if (!is_dir(VENDORS)) {
    trigger_error(
        'Composer vendors directory not found at "' . VENDORS . '". ' .
        'Please run "composer install" in the project root directory to install dependencies.',
        E_USER_ERROR
    );
}

if (!is_file(VENDORS . 'autoload.php')) {
    trigger_error(
        'Composer autoload file not found at "' . VENDORS . 'autoload.php". ' .
        'Please run "composer install" to generate the autoload file.',
        E_USER_ERROR
    );
}

require_once VENDORS . 'autoload.php';

if (!include 'Cake' . DS . 'bootstrap.php') {
    trigger_error(
        'CakePHP core could not be found. ' .
        'Please run "composer require friendsofcake2/cakephp" to install CakePHP core.',
        E_USER_ERROR
    );
}

if (Configure::read('debug') < 1) {
    throw new NotFoundException(__d('cake_dev', 'Debug setting does not allow access to this URL.'));
}

require_once CAKE . 'TestSuite' . DS . 'CakeTestSuiteDispatcher.php';

CakeTestSuiteDispatcher::run();
