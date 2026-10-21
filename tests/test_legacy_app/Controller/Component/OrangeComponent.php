<?php
/**
 * Test App OrangeComponent
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
 * OrangeComponent class
 *
 * @package       Cake.Test.TestApp.Controller.Component
 */
class OrangeComponent extends Component
{
    /**
     * components property
     *
     * @var array
     */
    public $components = ['Banana'];

    /**
     * initialize method
     *
     * @param Controller $controller
     * @return void
     */
    public function initialize(Controller $controller): void
    {
        $this->Controller = $controller;
        $this->Banana->testField = 'OrangeField';
    }

    /**
     * startup method
     *
     * @param Controller $controller
     * @return string
     */
    public function startup(Controller $controller): void
    {
        $controller->foo = 'pass';
    }
}
