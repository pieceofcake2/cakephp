<?php
/**
 * Test App TestAuthComponent
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
 * @package       Cake.Test.TestApp.Controller.Component
 * @since         CakePHP(tm) v 1.2.0.5347
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

/**
 * TestAuthComponent class
 *
 * @package       Cake.Test.TestApp.Controller.Component
 */
class TestAuthComponent extends AuthComponent
{
    /**
     * testStop property
     *
     * @var bool
     */
    public $testStop = false;

    /**
     * Helper method to add/set an authenticate object instance
     *
     * @param int $index The index at which to add/set the object
     * @param CakeObject $object The object to add/set
     * @return void
     */
    public function setAuthenticateObject($index, $object): void
    {
        $this->_authenticateObjects[$index] = $object;
    }

    /**
     * Helper method to get an authenticate object instance
     *
     * @param int $index The index at which to get the object
     * @return CakeObject $object
     */
    public function getAuthenticateObject($index): mixed
    {
        $this->constructAuthenticate();

        return $this->_authenticateObjects[$index] ?? null;
    }

    /**
     * Helper method to add/set an authorize object instance
     *
     * @param int $index The index at which to add/set the object
     * @param CakeObject $object The object to add/set
     * @return void
     */
    public function setAuthorizeObject($index, $object): void
    {
        $this->_authorizeObjects[$index] = $object;
    }

    /**
     * stop method
     *
     * @param int $status
     * @return void
     */
    protected function _stop($status = 0): void
    {
        $this->testStop = true;
    }

    public static function clearUser(): void
    {
        static::$_user = [];
    }
}
