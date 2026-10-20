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

namespace TestApp\Network\Http;

use HttpSocket;

/**
 * HttpSocket authentication in a namespace
 */
class NamespacedAuthentication
{
    /**
     * @param \HttpSocket $http The socket
     * @param array $authInfo The authentication settings
     * @return void
     */
    public static function authentication(HttpSocket $http, &$authInfo)
    {
        $http->request['header']['Authorization'] = 'Namespaced ' . $authInfo['user'];
    }

    /**
     * @param \HttpSocket $http The socket
     * @param array $proxyInfo The proxy settings
     * @return void
     */
    public static function proxyAuthentication(HttpSocket $http, &$proxyInfo)
    {
        $http->request['header']['Proxy-Authorization'] = 'Namespaced ' . $proxyInfo['user'];
    }
}
