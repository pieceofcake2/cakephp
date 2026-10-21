<?php
/**
 * Test App TestBaseAuthenticate
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
 * @package       Cake.Test.TestApp.Controller.Component.Auth
 * @since         CakePHP(tm) v 1.2.0.5347
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

/**
 * TestFormAuthenticate class
 *
 * @package       Cake.Test.TestApp.Controller.Component.Auth
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
     * @return mixed Either false on failure, or an array of user data on success.
     */
    public function authenticate(CakeRequest $request, CakeResponse $response): mixed
    {
        return [
            'id' => 1,
            'username' => 'mark',
        ];
    }
}
