<?php
/**
 * The Front Controller for handling every request
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
 * @package       app.webroot
 * @since         CakePHP(tm) v 0.2.9
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

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
 * Editing below this line should NOT be necessary.
 * Change at your own risk.
 */
if (!defined('WEBROOT_DIR')) {
    define('WEBROOT_DIR', basename(__DIR__));
}
if (!defined('WWW_ROOT')) {
    define('WWW_ROOT', __DIR__ . DS);
}

// For the built-in server
if (PHP_SAPI === 'cli-server') {
    if ($_SERVER['PHP_SELF'] !== '/' . basename(__FILE__) && file_exists(WWW_ROOT . $_SERVER['PHP_SELF'])) {
        return false;
    }
    $_SERVER['PHP_SELF'] = '/' . basename(__FILE__);
}

if (!is_dir(VENDORS)) {
    trigger_error(
        'Composer vendors directory not found at "' . VENDORS . '". ' .
        'Please run "composer install" in the project root directory to install dependencies.',
        E_USER_ERROR,
    );
}

if (!is_file(VENDORS . 'autoload.php')) {
    trigger_error(
        'Composer autoload file not found at "' . VENDORS . 'autoload.php". ' .
        'Please run "composer install" to generate the autoload file.',
        E_USER_ERROR,
    );
}

require_once VENDORS . 'autoload.php';

if (!require_once 'Cake' . DS . 'bootstrap.php') {
    trigger_error(
        'CakePHP core could not be found. ' .
        'Please run "composer require friendsofcake2/cakephp" to install CakePHP core.',
        E_USER_ERROR,
    );
}

App::uses('Dispatcher', 'Routing');

$Dispatcher = new Dispatcher();
$Dispatcher->dispatch(
    new CakeRequest(),
    new CakeResponse(),
);
