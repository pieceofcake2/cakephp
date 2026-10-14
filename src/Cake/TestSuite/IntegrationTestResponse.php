<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @package       Cake.TestSuite
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

App::uses('CakeResponse', 'Network');
App::uses('IntegrationTestStop', 'TestSuite');

/**
 * Response used by `IntegrationTestTrait` for dispatched requests.
 *
 * It behaves like `CakeResponse`, except that `send()` never outputs anything:
 * it applies the status code change `CakeResponse::send()` makes for redirects and
 * throws `IntegrationTestStop`, so the request stops where the application would
 * have called `exit` after sending the response (e.g. `Controller::redirect()`).
 * The response can then be inspected by the assertions of `IntegrationTestTrait`.
 *
 * This class has no CakePHP 5.x equivalent (CakePHP 5 responses are PSR-7 objects
 * that are never sent during tests).
 *
 * @package       Cake.TestSuite
 */
class IntegrationTestResponse extends CakeResponse
{
    /**
     * Stops the request instead of sending the response.
     *
     * @return void
     * @throws IntegrationTestStop Always.
     */
    public function send()
    {
        if (isset($this->_headers['Location']) && $this->_status === 200) {
            $this->statusCode(302);
        }

        throw new IntegrationTestStop($this);
    }

    /**
     * Never sends headers.
     *
     * @param string $name the header name
     * @param string|null $value the header value
     * @return void
     */
    protected function _sendHeader($name, $value = null)
    {
    }

    /**
     * Never sends content.
     *
     * @param string $content The content to send.
     * @return void
     */
    protected function _sendContent($content)
    {
    }
}
