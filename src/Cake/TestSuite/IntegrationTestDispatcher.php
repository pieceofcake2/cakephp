<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @package       Cake.TestSuite
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

App::uses('Dispatcher', 'Routing');
App::uses('IntegrationTestResponse', 'TestSuite');
App::uses('RedirectRoute', 'Routing/Route');
App::uses('Router', 'Routing');

/**
 * Dispatcher used by `IntegrationTestTrait`.
 *
 * It runs the dispatch cycle of `Dispatcher::dispatch()` (dispatcher filters, the
 * controller, `Dispatcher.afterDispatch`), but does not send the response: the final
 * response is kept in `$response`. A callback can be registered to inspect the
 * controller before it is invoked, which `IntegrationTestTrait` uses to capture the
 * controller, the rendered view and layout files and the flash messages.
 *
 * This replaces the `MiddlewareDispatcher` of CakePHP 5.x, as CakePHP 2 has no
 * middleware.
 *
 * @package       Cake.TestSuite
 */
class IntegrationTestDispatcher extends Dispatcher
{
    /**
     * The response of the last dispatched request.
     *
     * @var CakeResponse|null
     */
    public ?CakeResponse $response = null;

    /**
     * Called with the controller before it is invoked.
     *
     * @var callable|null
     */
    protected $_controllerCallback;

    /**
     * Constructor.
     *
     * @param callable|null $controllerCallback Called with the `Controller` instance
     *   before it is invoked, e.g. to attach event listeners.
     */
    public function __construct(?callable $controllerCallback = null)
    {
        parent::__construct();
        $this->_controllerCallback = $controllerCallback;
    }

    /**
     * Dispatches a request like `Dispatcher::dispatch()`, without sending the response.
     *
     * The resulting response is stored in `$response`. If something sends a response
     * while the request is dispatched (e.g. `Controller::redirect()`), the
     * `IntegrationTestStop` error thrown by `IntegrationTestResponse::send()` is not
     * caught here.
     *
     * @param CakeRequest $request Request object to dispatch.
     * @param CakeResponse $response Response object to put the results of the dispatch into.
     * @param array $additionalParams Settings array ("bare", "return") which is melded with the GET and POST params
     * @return string|null The response body if `$request['return']` is set, null otherwise.
     * @throws MissingControllerException When the controller is missing.
     * @triggers Dispatcher.beforeDispatch $this, compact('request', 'response', 'additionalParams')
     * @triggers Dispatcher.afterDispatch $this, compact('request', 'response')
     */
    public function dispatch(CakeRequest $request, CakeResponse $response, $additionalParams = [])
    {
        $this->response = $response;
        $beforeEvent = new CakeEvent('Dispatcher.beforeDispatch', $this, compact('request', 'response', 'additionalParams'));
        $this->getEventManager()->dispatch($beforeEvent);

        $request = $beforeEvent->data['request'];
        if ($beforeEvent->result instanceof CakeResponse) {
            $this->response = $beforeEvent->result;

            return isset($request->params['return']) ? $beforeEvent->result->body() : null;
        }

        $controller = $this->_getController($request, $response);

        if (!($controller instanceof Controller)) {
            throw new MissingControllerException([
                'class' => Inflector::camelize($request->params['controller']) . 'Controller',
                'plugin' => empty($request->params['plugin']) ? null : Inflector::camelize($request->params['plugin']),
            ]);
        }

        $response = $this->_invoke($controller, $request);
        $this->response = $response;
        if (isset($request->params['return'])) {
            return $response->body();
        }

        $afterEvent = new CakeEvent('Dispatcher.afterDispatch', $this, compact('request', 'response'));
        $this->getEventManager()->dispatch($afterEvent);
        $this->response = $afterEvent->data['response'];

        return null;
    }

    /**
     * Applies routing to the request, like `Dispatcher::parseParams()`.
     *
     * Redirect routes (`Router::redirect()`) send their response and call `exit`, so
     * their response is replaced with an `IntegrationTestResponse` first: the redirect
     * stops the request with `IntegrationTestStop` instead.
     *
     * @param CakeEvent $event containing the request, response and additional params
     * @return void
     */
    public function parseParams($event)
    {
        // Load the routes if they are not loaded yet.
        Router::extensions();
        foreach (Router::$routes as $route) {
            if ($route instanceof RedirectRoute) {
                $route->response = new IntegrationTestResponse();
            }
        }

        parent::parseParams($event);
    }

    /**
     * Gets the controller and passes it to the controller callback.
     *
     * @param CakeRequest $request Request object
     * @param CakeResponse $response Response for the controller.
     * @return mixed The controller, or false if it could not be loaded.
     */
    protected function _getController($request, $response)
    {
        $controller = parent::_getController($request, $response);
        if ($controller instanceof Controller && $this->_controllerCallback) {
            call_user_func($this->_controllerCallback, $controller);
        }

        return $controller;
    }
}
