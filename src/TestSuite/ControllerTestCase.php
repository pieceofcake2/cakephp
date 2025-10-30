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

use BadMethodCallException;
use Cake\Controller\Component;
use Cake\Controller\Controller;
use Cake\Core\App;
use Cake\Error\MissingComponentException;
use Cake\Error\MissingControllerException;
use Cake\Event\CakeEvent;
use Cake\Network\CakeRequest;
use Cake\Network\CakeResponse;
use Cake\Routing\Route\RedirectRoute;
use Cake\Routing\Router;
use Cake\Utility\ClassRegistry;
use Cake\Utility\Inflector;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * ControllerTestCase class
 *
 * @package       Cake.TestSuite
 * @method        mixed testAction() testAction($url, $options = array())  Lets you do functional tests of a controller action.
 */
abstract class ControllerTestCase extends CakeTestCase
{
    /**
     * The controller to test in testAction
     *
     * @var Controller|null
     */
    public ?Controller $controller = null;

    /**
     * Automatically mock controllers that aren't mocked
     *
     * @var bool
     */
    public bool $autoMock = true;

    /**
     * Use custom routes during tests
     *
     * @var bool
     */
    public bool $loadRoutes = true;

    /**
     * The resulting view vars of the last testAction call
     *
     * @var array|null
     */
    public ?array $vars = null;

    /**
     * The resulting rendered view of the last testAction call
     *
     * @var string|null
     */
    public ?string $view = null;

    /**
     * The resulting rendered layout+view of the last testAction call
     *
     * @var string|null
     */
    public ?string $contents = null;

    /**
     * The returned result of the dispatch (requestAction), if any
     *
     * @var string|null
     */
    public ?string $result = null;

    /**
     * The headers that would have been sent by the action
     *
     * @var array|null
     */
    public ?array $headers = null;

    /**
     * Flag for checking if the controller instance is dirty.
     * Once a test has been run on a controller it should be rebuilt
     * to clean up properties.
     *
     * @var bool
     */
    protected bool $_dirtyController = false;

    /**
     * The class name to use for mocking the response object.
     *
     * @var string
     */
    protected string $_responseClass = CakeResponse::class;

    /**
     * Used to enable calling ControllerTestCase::testAction() without the testing
     * framework thinking that it's a test case
     *
     * @param string $name The name of the function
     * @param array $arguments Array of arguments
     * @return mixed The return of _testAction.
     * @throws BadMethodCallException when you call methods that don't exist.
     */
    public function __call($name, $arguments)
    {
        if ($name === 'testAction') {
            return call_user_func_array([$this, '_testAction'], $arguments);
        }
        throw new BadMethodCallException("Method '{$name}' does not exist.");
    }

    /**
     * Lets you do functional tests of a controller action.
     *
     * ### Options:
     *
     * - `data` Will be used as the request data. If the `method` is GET,
     *   data will be used a GET params. If the `method` is POST, it will be used
     *   as POST data. By setting `$options['data']` to a string, you can simulate XML or JSON
     *   payloads to your controllers allowing you to test REST webservices.
     * - `method` POST or GET. Defaults to POST.
     * - `return` Specify the return type you want. Choose from:
     *     - `vars` Get the set view variables.
     *     - `view` Get the rendered view, without a layout.
     *     - `contents` Get the rendered view including the layout.
     *     - `result` Get the return value of the controller action. Useful
     *       for testing requestAction methods.
     *
     * @param array|string $url The URL to test.
     * @param array $options See options
     * @return mixed The specified return type.
     * @triggers ControllerTestCase $Dispatch, array('request' => $request)
     */
    protected function _testAction($url, $options = [])
    {
        $this->vars = $this->result = $this->view = $this->contents = $this->headers = null;

        $options += [
            'data' => [],
            'method' => 'POST',
            'return' => 'result',
        ];

        if (is_array($url)) {
            $url = Router::url($url);
        }

        $restore = ['get' => $_GET, 'post' => $_POST];

        $_SERVER['REQUEST_METHOD'] = strtoupper($options['method']);
        if (is_array($options['data'])) {
            if (strtoupper($options['method']) === 'GET') {
                $_GET = $options['data'];
                $_POST = [];
            } else {
                $_POST = $options['data'];
                $_GET = [];
            }
        }

        if (str_contains($url, '?')) {
            [$url, $query] = explode('?', $url, 2);
            parse_str($query, $queryArgs);
            $_GET += $queryArgs;
        }

        $_SERVER['REQUEST_URI'] = $url;
        /** @var CakeRequest|MockObject $request */
        $request = $this->getMock(CakeRequest::class, ['_readInput']);

        if (is_string($options['data'])) {
            $request->expects($this->any())
                ->method('_readInput')
                ->will($this->returnValue($options['data']));
        }

        $dispatcher = $this->_createDispatcher();
        foreach (Router::$routes as $route) {
            if ($route instanceof RedirectRoute) {
                $route->response = $this->getMock(CakeResponse::class, ['send']);
            }
        }
        $dispatcher->loadRoutes = $this->loadRoutes;
        $dispatcher->parseParams(new CakeEvent('ControllerTestCase', $dispatcher, ['request' => $request]));
        if (!isset($request->params['controller']) && Router::currentRoute()) {
            $this->headers = Router::currentRoute()->response->header();

            return null;
        }
        if ($this->_dirtyController) {
            $this->controller = null;
        }

        $plugin = empty($request->params['plugin']) ? '' : Inflector::camelize($request->params['plugin']) . '.';
        if ($this->controller === null && $this->autoMock) {
            $this->generate($plugin . Inflector::camelize($request->params['controller']));
        }
        $params = [];
        if ($options['return'] === 'result') {
            $params['return'] = 1;
            $params['bare'] = 1;
            $params['requested'] = 1;
        }
        $dispatcher->testController = $this->controller;
        /** @var CakeResponse $response */
        $response = $this->getMock($this->_responseClass, ['send', '_clearBuffer']);
        $dispatcher->response = $response;
        $this->result = $dispatcher->dispatch($request, $dispatcher->response, $params);

        // Clear out any stored requests.
        while (Router::getRequest()) {
            Router::popRequest();
        }

        $this->controller = $dispatcher->testController;
        $this->vars = $this->controller->viewVars;
        $this->contents = $this->controller->response->body();
        if (isset($this->controller->View)) {
            $this->view = $this->controller->View->fetch('__view_no_layout__');
        }
        $this->_dirtyController = true;
        $this->headers = $dispatcher->response->header();

        $_GET = $restore['get'];
        $_POST = $restore['post'];

        return $this->{$options['return']};
    }

    /**
     * Creates the test dispatcher class
     *
     * @return ControllerTestDispatcher
     */
    protected function _createDispatcher()
    {
        return new ControllerTestDispatcher();
    }

    /**
     * Generates a mocked controller and mocks any classes passed to `$mocks`. By
     * default, `_stop()` is stubbed as is sending the response headers, so to not
     * interfere with testing.
     *
     * ### Mocks:
     *
     * - `methods` Methods to mock on the controller. `_stop()` is mocked by default
     * - `models` Models to mock. Models are added to the ClassRegistry so any
     *   time they are instantiated the mock will be created. Pass as key value pairs
     *   with the value being specific methods on the model to mock. If `true` or
     *   no value is passed, the entire model will be mocked.
     * - `components` Components to mock. Components are only mocked on this controller
     *   and not within each other (i.e., components on components)
     *
     * @param string $controller Controller name
     * @param array $mocks List of classes and methods to mock
     * @return Controller Mocked controller
     * @throws MissingControllerException When controllers could not be created.
     * @throws MissingComponentException When components could not be created.
     */
    public function generate(string $controller, array $mocks = [])
    {
        [$plugin, $controller] = pluginSplit($controller);
        if ($plugin) {
            App::className($plugin . '.' . $plugin . 'AppController', 'Controller');
            $plugin .= '.';
        }

        $fullControllerName = $plugin . $controller;
        $controllerClass = App::className($fullControllerName, 'Controller', 'Controller');
        if (!$controllerClass) {
            throw new MissingControllerException([
                'class' => $controller . 'Controller',
                'plugin' => substr($plugin, 0, -1),
            ]);
        }
        ClassRegistry::flush();

        $mocks = array_merge_recursive([
            'methods' => ['_stop'],
            'models' => [],
            'components' => [],
        ], (array)$mocks);

        [, $name] = pluginSplit($controller);
        /** @var Controller|MockObject $controllerObj */
        $controllerObj = $this->getMock($controllerClass, $mocks['methods'], [], '', false);
        $controllerObj->name = $name;
        /** @var CakeRequest|MockObject $request */
        $request = $this->getMock(CakeRequest::class);
        /** @var CakeResponse|MockObject $response */
        $response = $this->getMock($this->_responseClass, ['_sendHeader']);
        $controllerObj->__construct($request, $response);
        $controllerObj->Components->setController($controllerObj);

        $config = ClassRegistry::config('Model');
        foreach ($mocks['models'] as $model => $methods) {
            if (is_string($methods)) {
                $model = $methods;
                $methods = true;
            }
            if ($methods === true) {
                $methods = [];
            }
            $this->getMockForModel($model, $methods, $config);
        }

        foreach ($mocks['components'] as $component => $methods) {
            if (is_string($methods)) {
                $component = $methods;
                $methods = true;
            }
            if ($methods === true) {
                $methods = [];
            }
            $config = $controllerObj->components[$component] ?? [];
            if (isset($config['className'])) {
                $alias = $component;
                $component = $config['className'];
            }
            [, $name] = pluginSplit($component, true);
            if (!isset($alias)) {
                $alias = $name;
            }

            $componentClass = App::className($component, 'Controller/Component', 'Component');
            if (!$componentClass) {
                throw new MissingComponentException([
                    'class' => $name . 'Component',
                ]);
            }
            /** @var Component|MockObject $componentObj */
            $componentObj = $this->getMock($componentClass, $methods, [$controllerObj->Components, $config]);
            $controllerObj->Components->set($alias, $componentObj);
            $controllerObj->Components->enable($alias);
            unset($alias);
        }

        $controllerObj->constructClasses();
        $this->_dirtyController = false;

        $this->controller = $controllerObj;

        return $this->controller;
    }

    /**
     * Unsets some properties to free memory.
     *
     * @return void
     */
    public function tearDown(): void
    {
        parent::tearDown();

        $this->contents = null;
        $this->controller = null;
        $this->headers = null;
        $this->result = null;
        $this->view = null;
        $this->vars = null;
    }
}
