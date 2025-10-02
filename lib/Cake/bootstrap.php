<?php
/**
 * Basic CakePHP functionality.
 *
 * Handles loading of core files needed on every request
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
 * @package       Cake
 * @since         CakePHP(tm) v 0.2.9
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

define('TIME_START', microtime(true));

error_reporting(E_ALL & ~E_DEPRECATED);

if (!defined('CAKE_CORE_INCLUDE_PATH')) {
    define('CAKE_CORE_INCLUDE_PATH', dirname(__DIR__));
}

if (!defined('CORE_PATH')) {
    define('CORE_PATH', CAKE_CORE_INCLUDE_PATH . DS);
}

if (!defined('WEBROOT_DIR')) {
    define('WEBROOT_DIR', 'webroot');
}

/**
 * Path to the cake directory.
 */
define('CAKE', CORE_PATH . 'Cake' . DS);

/**
 * Path to the application's directory.
 */
if (!defined('APP')) {
    define('APP', ROOT . DS . APP_DIR . DS);
}

/**
 * Config Directory
 */
if (!defined('CONFIG')) {
    define('CONFIG', ROOT . DS . APP_DIR . DS . 'Config' . DS);
}

/**
 * Path to the application's libs directory.
 */
define('APPLIBS', APP . 'Lib' . DS);

/**
 * Path to the public CSS directory.
 */
if (!defined('CSS')) {
    define('CSS', WWW_ROOT . 'css' . DS);
}

/**
 * Path to the public JavaScript directory.
 */
if (!defined('JS')) {
    define('JS', WWW_ROOT . 'js' . DS);
}

/**
 * Path to the public images directory.
 */
if (!defined('IMAGES')) {
    define('IMAGES', WWW_ROOT . 'img' . DS);
}

/**
 * Path to the temporary files directory.
 */
if (!defined('TMP')) {
    define('TMP', APP . 'tmp' . DS);
}

/**
 * Path to the logs directory.
 */
if (!defined('LOGS')) {
    define('LOGS', TMP . 'logs' . DS);
}

/**
 * Path to the cache files directory. It can be shared between hosts in a multi-server setup.
 */
if (!defined('CACHE')) {
    define('CACHE', TMP . 'cache' . DS);
}

/**
 * Path to the vendors directory.
 */
if (!defined('VENDORS')) {
    define('VENDORS', ROOT . DS . 'vendors' . DS);
}

/**
 * Web path to the public images directory.
 */
if (!defined('IMAGES_URL')) {
    define('IMAGES_URL', 'img/');
}

/**
 * Web path to the CSS files directory.
 */
if (!defined('CSS_URL')) {
    define('CSS_URL', 'css/');
}

/**
 * Web path to the js files directory.
 */
if (!defined('JS_URL')) {
    define('JS_URL', 'js/');
}

require_once CAKE . 'basics.php';
require_once CAKE . 'Core' . DS . 'App.php';
require_once CAKE . 'Error' . DS . 'exceptions.php';

spl_autoload_register(['App', 'load'], true, true);

App::uses('ErrorHandler', 'Error');
App::uses('Configure', 'Core');
App::uses('CakePlugin', 'Core');
App::uses('Cache', 'Cache');
App::uses('CakeObject', 'Core');
App::uses('Object', 'Core');
App::uses('Multibyte', 'I18n');

App::$bootstrapping = true;

/**
 * Full URL prefix
 */
if (!defined('FULL_BASE_URL')) {
    $s = null;
    if (env('HTTPS')) {
        $s = 's';
    }

    $httpHost = env('HTTP_HOST');

    if (isset($httpHost)) {
        define('FULL_BASE_URL', 'http' . $s . '://' . $httpHost);
        Configure::write('App.fullBaseUrl', FULL_BASE_URL);
    }
    unset($httpHost, $s);
}

Configure::write('App.imageBaseUrl', IMAGES_URL);
Configure::write('App.cssBaseUrl', CSS_URL);
Configure::write('App.jsBaseUrl', JS_URL);

if (!function_exists('mb_encode_mimeheader')) {
    /**
     * Encode string for MIME header
     *
     * @param string $str The string being encoded
     * @param string $charset specifies the name of the character set in which str is represented in.
     *    The default value is determined by the current NLS setting (mbstring.language).
     * @param string $transferEncoding specifies the scheme of MIME encoding.
     *    It should be either "B" (Base64) or "Q" (Quoted-Printable). Falls back to "B" if not given.
     * @param string $linefeed specifies the EOL (end-of-line) marker with which
     *    mb_encode_mimeheader() performs line-folding
     *    (a » RFC term, the act of breaking a line longer than a certain length into multiple lines.
     *    The length is currently hard-coded to 74 characters). Falls back to "\r\n" (CRLF) if not given.
     * @param int $indent [definition unknown and appears to have no affect]
     * @return string A converted version of the string represented in ASCII.
     */
    function mb_encode_mimeheader($str, $charset = 'UTF-8', $transferEncoding = 'B', $linefeed = "\r\n", $indent = 1)
    {
        return Multibyte::mimeEncode($str, $charset, $linefeed);
    }
}

Configure::bootstrap($boot ?? true);

if (function_exists('mb_internal_encoding')) {
    $encoding = Configure::read('App.encoding');
    if (!empty($encoding)) {
        mb_internal_encoding($encoding);
    }
    if (!empty($encoding) && function_exists('mb_regex_encoding')) {
        mb_regex_encoding($encoding);
    }
}
