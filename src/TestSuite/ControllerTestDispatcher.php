<?php

/**
 * ControllerTestCase file
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
 * @package       Cake.TestSuite
 * @since         CakePHP(tm) v 2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\TestSuite;

use Cake\Controller\Controller;
use Cake\Network\CakeRequest;
use Cake\Network\CakeResponse;
use Cake\Routing\Dispatcher;
use Cake\Routing\Router;

/**
 * ControllerTestDispatcher class
 *
 * @package       Cake.TestSuite
 */
class ControllerTestDispatcher extends Dispatcher
{
    /**
     * The controller to use in the dispatch process
     *
     * @var Controller|null
     */
    public ?Controller $testController = null;

    public ?CakeResponse $response = null;

    /**
     * Use custom routes during tests
     *
     * @var bool
     */
    public $loadRoutes = true;

    /**
     * Returns the test controller
     *
     * @param CakeRequest $request The request instance.
     * @param CakeResponse $response The response instance.
     * @return Controller|null
     */
    protected function _getController(CakeRequest $request, CakeResponse $response): ?Controller
    {
        if ($this->testController === null) {
            $this->testController = parent::_getController($request, $response);
        }
        $this->testController->helpers = array_merge(['InterceptContent'], $this->testController->helpers);
        $this->testController->setRequest($request);
        $this->testController->response = $response;
        foreach ($this->testController->Components->loaded() as $component) {
            $object = $this->testController->Components->{$component};
            if (isset($object->response)) {
                $object->response = $response;
            }
            if (isset($object->request)) {
                $object->request = $request;
            }
        }

        return $this->testController;
    }

    /**
     * Loads routes and resets if the test case dictates it should
     *
     * @return void
     */
    protected function _loadRoutes(): void
    {
        if (!$this->loadRoutes) {
            Router::reload();
        }
    }
}
