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
use Cake\Network\CakeResponse;
use Cake\Routing\Router;

/**
 * AjaxAuthController class
 *
 * @package       Cake.Test.Case.Controller.Component
 */
class AjaxAuthController extends Controller
{
    /**
     * components property
     *
     * @var array
     */
    public $components = ['Session', 'TestAuth'];

    /**
     * uses property
     *
     * @var array|bool|null
     */
    public $uses = [];

    /**
     * testUrl property
     *
     * @var mixed
     */
    public $testUrl = null;

    /**
     * beforeFilter method
     *
     * @return void
     */
    public function beforeFilter(): void
    {
        $this->TestAuth->ajaxLogin = 'test_element';
        $this->TestAuth->userModel = 'AuthUser';
        $this->TestAuth->RequestHandler->ajaxLayout = 'ajax2';
    }

    /**
     * add method
     *
     * @return void
     */
    public function add(): void
    {
        if ($this->TestAuth->testStop !== true) {
            echo 'Added Record';
        }
    }

    /**
     * redirect method
     *
     * @param array|string|null $url
     * @param array|string|int|null  $status
     * @param bool $exit
     * @return CakeResponse|null
     */
    public function redirect($url, $status = null, $exit = true): mixed
    {
        $this->testUrl = Router::url($url);

        return null;
    }
}
