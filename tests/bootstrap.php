<?php
/**
 * Bootstrap for phpunit command
 */

use Composer\InstalledVersions;
use PHPUnit\Util\ErrorHandler;

if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}
if (!defined('ROOT')) {
    define('ROOT', realpath(InstalledVersions::getInstallPath('pieceofcake2/app')));
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
        fwrite(STDERR, 'Composer autoload file not found. ' .
            'Please run "composer install" to generate the autoload file.' . PHP_EOL);
        exit(1);
    }
}

new ShellDispatcher([$_SERVER['argv'][0]]);

set_error_handler(new ErrorHandler(true, true, true, true));

// Security::cipher() reports its deprecation and an out-of-range Security.cipherSeed once
// per process. Many core tests use 'cipher' on purpose, so mark both as reported here;
// SecurityTest and CookieComponentTest reset them to test the reports.
Closure::bind(function () {
    static::$_cipherDeprecationReported = true;
    static::$_cipherSeedReported = true;
}, null, Security::class)();
