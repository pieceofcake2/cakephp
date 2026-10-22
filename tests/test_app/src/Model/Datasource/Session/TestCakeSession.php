<?php
/**
 * CakePHP(tm) Tests <https://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace TestApp\Model\Datasource\Session;

use Cake\Model\Datasource\CakeSession;

/**
 * TestCakeSession
 *
 * @package       Cake.Test.Case.Model.Datasource
 */
class TestCakeSession extends CakeSession
{
    public static function setUserAgent($value): void
    {
        static::$_userAgent = $value;
    }

    public static function setHost($host): void
    {
        static::_setHost($host);
    }

    public static function configureSession(): void
    {
        static::_configureSession();
    }
}
