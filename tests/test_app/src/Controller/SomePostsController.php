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

/**
 * SomePostsController class
 *
 * @package       Cake.Test.Case.Routing
 */
class SomePostsController extends AppController
{
    /**
     * uses property
     *
     * @var array|bool|null
     */
    public $uses = [];

    /**
     * autoRender property
     *
     * @var bool
     */
    public $autoRender = false;

    /**
     * beforeFilter method
     *
     * @return void
     */
    public function beforeFilter(): void
    {
        if ($this->params['action'] === 'index') {
            $this->params['action'] = 'view';
        } else {
            $this->params['action'] = 'change';
        }
        $this->params['pass'] = ['changed'];
    }

    /**
     * index method
     *
     * @return bool
     */
    public function index(): mixed
    {
        return true;
    }

    /**
     * change method
     *
     * @return bool
     */
    public function change(): mixed
    {
        return true;
    }
}
