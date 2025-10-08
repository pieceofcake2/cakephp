<?php
/**
 * src/Cake/Console/cake initialize
 */

if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}

$rootInstall = dirname(__DIR__, 2) . DS . 'vendor' . DS . 'autoload.php';
$composerInstall = dirname(__DIR__, 4) . DS . 'autoload.php';

if (isset($GLOBALS['_composer_autoload_path'])) {
    require_once $GLOBALS['_composer_autoload_path'];
} else {
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

// In src/Cake/Console/cake makes app root path.
$appPath = dirname(__DIR__, 2) . DS . 'vendor' . DS . 'pieceofcake2' . DS . 'app' . DS . 'src';

new ShellDispatcher([$_SERVER['argv'][0], '-working', $appPath]);

unset($paths, $path, $found, $dispatcher, $appPath);
