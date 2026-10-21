<?php
/**
 * Test App TestComponent
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
 * @since         CakePHP(tm) v 1.2.0.5436
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

/**
 * TestComponent class
 *
 * @package       Cake.Test.TestApp.Controller.Component
 */
class TestComponent extends CakeObject
{
    /**
     * beforeRedirect method
     *
     * @return void
     */
    public function beforeRedirect(): void
    {
    }

    /**
     * initialize method
     *
     * @return void
     */
    public function initialize(Controller $controller): void
    {
    }

    /**
     * startup method
     *
     * @return void
     */
    public function startup(Controller $controller): void
    {
    }

    /**
     * shutdown method
     *
     * @return void
     */
    public function shutdown(Controller $controller): void
    {
    }

    /**
     * beforeRender callback
     *
     * @return void
     */
    public function beforeRender(Controller $controller): mixed
    {
        if ($this->viewclass) {
            $controller->viewClass = $this->viewclass;
        }

        return null;
    }
}
