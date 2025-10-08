<?php

if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
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
    } elseif (is_dir(APP . 'tmp')) {
        define('LOGS', APP . 'tmp' . DS . 'logs' . DS);
    } else {
        define('LOGS', CORE_ROOT . DS . 'logs' . DS);
    }
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
