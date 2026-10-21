<?php
/**
 * Test App TestAuthentication class
 *
 * CakePHP(tm) Tests <https://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.Test.TestApp.Lib.Network.Http
 * @since         CakePHP(tm) v 1.2.0.4206
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

/**
 * TestAuthentication class
 *
 * @package       Cake.Test.TestApp.Lib.Network.Http
 */
class TestAuthentication
{
    /**
     * authentication method
     *
     * @param HttpSocket $http A HTTP socket.
     * @param array &$authInfo Some auth info.
     * @return void
     */
    public static function authentication(HttpSocket $http, &$authInfo): void
    {
        $http->request['header']['Authorization'] = 'Test ' . $authInfo['user'] . '.' . $authInfo['pass'];
    }

    /**
     * proxyAuthentication method
     *
     * @param HttpSocket $http A HTTP socket.
     * @param array &$proxyInfo Some proxy info.
     * @return void
     */
    public static function proxyAuthentication(HttpSocket $http, &$proxyInfo): void
    {
        $http->request['header']['Proxy-Authorization'] = 'Test ' . $proxyInfo['user'] . '.' . $proxyInfo['pass'];
    }
}
