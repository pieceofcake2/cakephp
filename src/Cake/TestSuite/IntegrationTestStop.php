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

/**
 * Thrown by `IntegrationTestResponse::send()` to stop a request dispatched by
 * `IntegrationTestTrait`.
 *
 * CakePHP 2 sends the response and calls `exit` in several places (for example
 * `Controller::redirect()`, `AuthComponent` and `AssetDispatcher`). In an integration
 * test the response is never sent: sending it throws this error instead, which unwinds
 * the call stack like `exit` would (code after the `send()` call, such as
 * `Controller::afterFilter()`, is not run) and is caught by `IntegrationTestTrait`.
 *
 * It extends `Error` instead of `Exception` so that `catch (Exception $e)` blocks in
 * application code do not swallow it. This class has no CakePHP 5.x equivalent.
 *
 * @package       Cake.TestSuite
 */
class IntegrationTestStop extends Error
{
    /**
     * The response that was being sent.
     *
     * @var CakeResponse
     */
    protected CakeResponse $_response;

    /**
     * Constructor
     *
     * @param CakeResponse $response The response that was being sent.
     */
    public function __construct(CakeResponse $response)
    {
        parent::__construct('The response was sent. This error stops the request like `exit` would.');
        $this->_response = $response;
    }

    /**
     * Returns the response that was being sent.
     *
     * @return CakeResponse
     */
    public function getResponse(): CakeResponse
    {
        return $this->_response;
    }
}
