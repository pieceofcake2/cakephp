<?php
/**
 * Test App SomePagesController Controller
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
 * @package       Cake.Test.TestApp.Plugin.TestDispatchLegacyPlugin.Controller
 * @since         CakePHP(tm) v 1.2.0.4206
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

App::uses('AppController', 'Controller');

/**
 * SomePagesController class
 *
 * @package       Cake.Test.TestApp.Plugin.TestDispatchLegacyPlugin.Controller
 */
class SomePagesController extends AppController
{
    /**
     * uses property
     *
     * @var array
     */
    public $uses = [];

    /**
     * display method
     *
     * @param string $page
     * @return void
     */
    public function display($page = null): mixed
    {
        return $page;
    }

    /**
     * index method
     *
     * @return void
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
        $this->response->file(CORE_TESTS . DS . 'test_legacy_app' . DS . 'Vendor' . DS . 'css' . DS . 'test_asset.css');

        return $this->response;
    }
}
