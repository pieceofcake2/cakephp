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

use Cake\Network\CakeResponse;

/**
 * SomePagesController class
 *
 * @package       Cake.Test.Case.Routing
 */
class SomePagesController extends AppController
{
    /**
     * uses property
     *
     * @var array|bool|null
     */
    public $uses = [];

    /**
     * display method
     *
     * @param string|null $page
     * @return string|null
     */
    public function display($page = null): mixed
    {
        return $page;
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
     * Test method for returning responses.
     *
     * @return CakeResponse
     */
    public function responseGenerator(): mixed
    {
        return new CakeResponse(['body' => 'new response']);
    }

    /**
     * Test file sending
     *
     * @return CakeResponse
     */
    public function sendfile(): mixed
    {
        $this->response->file(CORE_TESTS . DS . 'test_app' . DS . 'vendor' . DS . 'css' . DS . 'test_asset.css');

        return $this->response;
    }
}
