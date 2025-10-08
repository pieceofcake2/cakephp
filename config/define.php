<?php

if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}

if (!defined('WEBROOT_DIR')) {
    define('WEBROOT_DIR', 'webroot');
}

if (!defined('APP')) {
    define('APP', ROOT . DS . APP_DIR . DS);
}

if (!defined('TMP')) {
    if (is_dir(ROOT . DS . 'tmp')) {
        define('TMP', ROOT . DS . 'tmp' . DS);
    } elseif (is_dir(APP . 'tmp')) {
        define('TMP', APP . 'tmp' . DS);
    } else {
        define('TMP', CORE_ROOT . DS . 'tmp' . DS);
    }
}

if (!defined('LOGS')) {
    if (is_dir(ROOT . DS . 'tmp')) {
        define('LOGS', ROOT . DS . 'logs' . DS);
    } else {
        define('LOGS', TMP . 'logs' . DS);
    }
}

if (!defined('CACHE')) {
    define('CACHE', TMP . 'cache' . DS);
}

if (!defined('CONFIG')) {
    if (file_exists(ROOT . DS . 'config' . DS)) {
        define('CONFIG', ROOT . DS . 'config' . DS);
    } else {
        define('CONFIG', ROOT . DS . APP_DIR . DS . 'Config' . DS);
    }
}

if (!defined('APPLIBS')) {
    define('APPLIBS', APP . 'Lib' . DS);
}

if (!defined('TESTS')) {
    define('TESTS', APP . 'Test' . DS);
}

if (!defined('CSS')) {
    define('CSS', WWW_ROOT . 'css' . DS);
}

if (!defined('JS')) {
    define('JS', WWW_ROOT . 'js' . DS);
}

if (!defined('IMAGES')) {
    define('IMAGES', WWW_ROOT . 'img' . DS);
}

if (!defined('IMAGES_URL')) {
    define('IMAGES_URL', 'img/');
}

if (!defined('CSS_URL')) {
    define('CSS_URL', 'css/');
}

if (!defined('JS_URL')) {
    define('JS_URL', 'js/');
}
