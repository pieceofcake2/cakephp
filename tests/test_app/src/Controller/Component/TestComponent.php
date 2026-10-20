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
 * TestComponent class
 *
 * @package       Cake.Test.Case.Controller
 */
class TestComponent extends Component
{
    /**
     * beforeRedirect method
     *
     * @param Controller $controller
     * @param array|string|null $url
     * @param array|int|null $status
     * @param bool $exit
     * @return array|string|false|null
     */
    public function beforeRedirect(
        $controller,
        $url,
        $status = null,
        $exit = true,
    ): mixed {
        return null;
    }

    /**
     * initialize method
     *
     * @return void
     */
    public function initialize(Controller $controller): void
    {
    }

    /**
     * startup method
     *
     * @return void
     */
    public function startup(Controller $controller): void
    {
    }

    /**
     * shutdown method
     *
     * @return void
     */
    public function shutdown(Controller $controller): void
    {
    }

    /**
     * beforeRender callback
     *
     * @return bool|null
     */
    public function beforeRender(Controller $controller): mixed
    {
        if ($this->viewclass) {
            $controller->viewClass = $this->viewclass;
        }

        return null;
    }
}
