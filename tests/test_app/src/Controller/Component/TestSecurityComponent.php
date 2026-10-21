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

namespace TestApp\Controller\Component;

use Cake\Controller\Component\SecurityComponent;
use Cake\Controller\Controller;

/**
 * TestSecurityComponent
 *
 * @package       Cake.Test.Case.Controller.Component
 */
class TestSecurityComponent extends SecurityComponent
{
    /**
     * validatePost method
     *
     * @param Controller $controller
     * @return bool
     */
    public function validatePost(Controller $controller): mixed
    {
        return $this->_validatePost($controller);
    }

    /**
     * authRequired method
     *
     * @param Controller $controller
     * @return bool
     */
    public function authRequired(Controller $controller): mixed
    {
        return $this->_authRequired($controller);
    }

    /**
     * methodRequired method
     *
     * @param Controller $controller
     * @return bool
     */
    public function methodsRequired(Controller $controller): mixed
    {
        return $this->_methodsRequired($controller);
    }
}
