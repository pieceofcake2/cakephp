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

use Cake\Core\App;
use Cake\Core\Configure;

define('TIME_START', microtime(true));

error_reporting(E_ALL & ~E_DEPRECATED);

require_once dirname(__DIR__) . '/config/init.php';
require_once dirname(__DIR__) . '/config/define.php';

require_once CAKE . 'basics.php';
require_once CAKE . 'functions.php';

spl_autoload_register(['App', 'load'], true, true);

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

// Set default application namespace
if (!Configure::check('App.namespace')) {
    Configure::write('App.namespace', 'App');
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
