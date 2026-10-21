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

namespace TestApp\Controller;

use Cake\Controller\Controller;

/**
 * RequestHandlerTestController class
 *
 * @package       Cake.Test.Case.Controller.Component
 */
class RequestHandlerTestController extends Controller
{
    /**
     * uses property
     *
     * @var array|bool|null
     */
    public $uses = [];

    /**
     * test method for ajax redirection
     *
     * @return void
     */
    public function destination(): void
    {
        $this->viewPath = 'Posts';
        $this->render('index');
    }

    /**
     * test method for ajax redirection + parameter parsing
     *
     * @return void
     */
    public function param_method($one = null, $two = null): void
    {
        echo "one: $one two: $two";
        $this->autoRender = false;
    }

    /**
     * test method for testing layout rendering when isAjax()
     *
     * @return void
     */
    public function ajax2_layout(): void
    {
        if ($this->autoLayout) {
            $this->layout = 'ajax2';
        }
        $this->destination();
    }
}
