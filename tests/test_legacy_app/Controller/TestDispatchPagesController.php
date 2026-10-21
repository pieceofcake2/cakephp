<?php
/**
 * Test App TestDispatchPagesController Controller
 *
 * CakePHP(tm) Tests <https://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.Test.TestApp.Controller
 * @since         CakePHP(tm) v 1.2.0.4206
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

App::uses('AppController', 'Controller');

/**
 * TestDispatchPagesController class
 *
 * @package       Cake.Test.TestApp.Controller
 */
class TestDispatchPagesController extends AppController
{
    /**
     * uses property
     *
     * @var array
     */
    public $uses = [];

    /**
     * admin_index method
     *
     * @return void
     */
    public function admin_index(): mixed
    {
        return true;
    }

    /**
     * camelCased method
     *
     * @return void
     */
    public function camelCased(): mixed
    {
        return true;
    }
}
