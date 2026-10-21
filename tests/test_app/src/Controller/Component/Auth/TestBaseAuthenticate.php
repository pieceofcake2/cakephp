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

namespace TestApp\Controller\Component\Auth;

use Cake\Controller\Component\Auth\BaseAuthenticate;
use Cake\Network\CakeRequest;
use Cake\Network\CakeResponse;

/**
 * TestFormAuthenticate class
 *
 * @package       Cake.Test.Case.Controller.Component
 */
class TestBaseAuthenticate extends BaseAuthenticate
{
    /**
     * Implemented events
     *
     * @return array of events => callbacks.
     */
    public function implementedEvents(): mixed
    {
        return [
            'Auth.afterIdentify' => 'afterIdentify',
        ];
    }

    public $afterIdentifyCallable = null;

    /**
     * Test function to be used in event dispatching
     *
     * @return void
     */
    public function afterIdentify($event): void
    {
        call_user_func($this->afterIdentifyCallable, $event);
    }

    /**
     * Authenticate a user based on the request information.
     *
     * @param CakeRequest $request Request to get authentication information from.
     * @param CakeResponse $response A response object that can have headers added.
     * @return array|false Either false on failure, or an array of user data on success.
     */
    public function authenticate(CakeRequest $request, CakeResponse $response): mixed
    {
        return [
            'id' => 1,
            'username' => 'mark',
        ];
    }
}
