<?php
/**
 * RequestActionController file
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
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

/**
 * RequestActionController class
 *
 * @package       Cake.Test.TestApp.Controller
 */
class RequestActionController extends Controller
{
    /**
     * uses property
     *
     * @var array
     */
    public $uses = ['RequestActionPost'];

    /**
     * test_request_action method
     *
     * @return void
     */
    public function test_request_action(): mixed
    {
        return 'This is a test';
    }

    /**
     * another_ra_test method
     *
     * @param mixed $id
     * @param mixed $other
     * @return void
     */
    public function another_ra_test($id, $other): mixed
    {
        return $id + $other;
    }

    /**
     * normal_request_action method
     *
     * @return string Hello World!
     */
    public function normal_request_action(): mixed
    {
        return 'Hello World';
    }

    /**
     * returns $this->here
     *
     * @return string $this->here.
     */
    public function return_here(): mixed
    {
        return $this->request->here();
    }

    /**
     * paginate_request_action method
     *
     * @return true
     */
    public function paginate_request_action(): mixed
    {
        $this->paginate();

        return true;
    }

    /**
     * post pass, testing post passing
     *
     * @return array
     */
    public function post_pass(): mixed
    {
        return $this->request->data;
    }

    /**
     * test param passing and parsing.
     *
     * @return array
     */
    public function params_pass(): mixed
    {
        return $this->request;
    }

    public function param_check(): void
    {
        $this->autoRender = false;
        $content = '';
        if (isset($this->request->params[0])) {
            $content = 'return found';
        }
        $this->response->body($content);
    }
}
