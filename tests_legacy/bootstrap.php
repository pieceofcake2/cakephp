<?php
/**
 * Bootstrap for phpunit command
 */

use Composer\Autoload\ClassLoader;
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
if (!defined('CORE_ROOT')) {
    define('CORE_ROOT', dirname(__DIR__));
}
if (!defined('CORE_TESTS')) {
    define('CORE_TESTS', CORE_ROOT . DS . 'tests_legacy');
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

// The classes of the test application, which tests/ does not load. Looked up before the
// PSR-4 namespaces of tests/, which has classes of the same names.
$classMap = [];
$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__ . DS . 'test_app', FilesystemIterator::SKIP_DOTS));
foreach ($files as $file) {
    if ($file->getExtension() !== 'php') {
        continue;
    }
    $code = (string)file_get_contents($file->getPathname());
    $namespace = preg_match('/^namespace ([\\w\\\\]+);/m', $code, $match) ? $match[1] . '\\' : '';
    preg_match_all('/^(?:abstract |final )?(?:class|interface|trait) (\\w+)/m', $code, $matches);
    foreach ($matches[1] as $class) {
        $classMap[$namespace . $class] ??= $file->getPathname();
    }
}
// The PSR-4 namespaces of tests/ in composer.json are not for this test application
foreach (ClassLoader::getRegisteredLoaders() as $loader) {
    foreach (array_keys($loader->getPrefixesPsr4()) as $prefix) {
        if (in_array($prefix, ['Cake\\Test\\Fixture\\', 'Cake\\Test\\TestCase\\', 'TestApp\\', 'TestPlugin\\', 'TestPluginTwo\\', 'PluginJs\\', 'TestPluginModern\\'], true)) {
            $loader->setPsr4($prefix, []);
        }
    }
}
$loader = new ClassLoader();
$loader->addClassMap($classMap);
$loader->register(true);
unset($classMap, $files, $file, $code, $namespace, $match, $matches, $class, $loader);

new ShellDispatcher([$_SERVER['argv'][0]]);

// tests/ and tests_legacy/ have classes of the same names in other files, so the files
// that the other test suite cached for them are not used
Cache::delete('file_map', '_cake_core_');
Cache::delete('object_map', '_cake_core_');
Closure::bind(function () {
    static::$_map = [];
    static::$_objects = [];
}, null, App::class)();

set_error_handler(new ErrorHandler(true, true, true, true));

// Security::cipher() reports its deprecation and an out-of-range Security.cipherSeed once
// per process. Many core tests use 'cipher' on purpose, so mark both as reported here;
// SecurityTest and CookieComponentTest reset them to test the reports.
Closure::bind(function () {
    static::$_cipherDeprecationReported = true;
    static::$_cipherSeedReported = true;
}, null, Security::class)();
