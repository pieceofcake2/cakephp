<?php
/**
 * Test App AjaxAuthController
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
 * @package       Cake.Test.TestApp.Controller
 * @since         CakePHP(tm) v 1.2.0.5347
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

/**
 * AjaxAuthController class
 *
 * @package       Cake.Test.TestApp.Controller
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
     * @var array
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
     * @param array|string $url
     * @param mixed $status
     * @param mixed $exit
     * @return void
     */
    public function redirect($url, $status = null, $exit = true): mixed
    {
        $this->testUrl = Router::url($url);

        return false;
    }
}
