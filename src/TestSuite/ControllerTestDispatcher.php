<?php

namespace Cake\TestSuite;

use BadMethodCallException;
use Cake\Controller\Component;
use Cake\Controller\Controller;
use Cake\Core\App;
use Cake\Error\MissingComponentException;
use Cake\Error\MissingControllerException;
use Cake\Event\CakeEvent;
use Cake\Network\CakeRequest;
use Cake\Network\CakeResponse;
use Cake\Routing\Dispatcher;
use Cake\Routing\Route\RedirectRoute;
use Cake\Routing\Router;
use Cake\Utility\ClassRegistry;
use Cake\Utility\Inflector;
use Cake\View\Helper;

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
     * @var Controller
     */
    public $testController = null;

    /**
     * Use custom routes during tests
     *
     * @var bool
     */
    public $loadRoutes = true;

    /**
     * The response object injected into the test controller
     *
     * @var CakeResponse|null
     */
    public $response = null;

    /**
     * Returns the test controller
     *
     * @param CakeRequest $request The request instance.
     * @param CakeResponse $response The response instance.
     * @return Controller
     */
    protected function _getController($request, $response)
    {
        if ($this->testController === null) {
            $this->testController = parent::_getController($request, $response);
        }
        $this->testController->helpers = array_merge(['InterceptContent'], $this->testController->helpers);
        $this->testController->setRequest($request);
        $this->testController->response = $this->response;
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
    protected function _loadRoutes()
    {
        if (!$this->loadRoutes) {
            Router::reload();
        }
    }
}

class_alias(ControllerTestDispatcher::class, 'ControllerTestDispatcher');
