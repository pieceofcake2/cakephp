<?php

if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}

if (!defined('CORE_ROOT')) {
    define('CORE_ROOT', dirname(__DIR__));
}

if (!defined('CAKE_CORE_INCLUDE_PATH')) {
    define('CAKE_CORE_INCLUDE_PATH', CORE_ROOT . DS . 'src');
}

if (!defined('CORE_PATH')) {
    define('CORE_PATH', CAKE_CORE_INCLUDE_PATH . DS);
}

if (!defined('CAKE')) {
    define('CAKE', CORE_PATH);
}

if (!defined('CORE_TESTS')) {
    define('CORE_TESTS', CORE_ROOT . DS . 'tests');
}
