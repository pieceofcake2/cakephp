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

new ShellDispatcher([$_SERVER['argv'][0]]);

set_error_handler(new ErrorHandler(true, true, true, true));
