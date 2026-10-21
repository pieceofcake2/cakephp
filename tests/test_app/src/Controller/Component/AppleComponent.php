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

use Cake\Controller\Component;
use Cake\Controller\Controller;

/**
 * AppleComponent class
 *
 * @package       Cake.Test.Case.Controller
 */
class AppleComponent extends Component
{
    /**
     * components property
     *
     * @var array
     */
    public $components = ['Orange'];

    /**
     * testName property
     *
     * @var mixed
     */
    public $testName = null;

    /**
     * startup method
     *
     * @param Controller $controller
     * @return void
     */
    public function startup(Controller $controller): void
    {
        $this->testName = $controller->name;
    }
}
