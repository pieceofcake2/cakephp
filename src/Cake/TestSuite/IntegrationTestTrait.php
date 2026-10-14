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

use PHPUnit\Exception as PHPUnitException;

App::uses('CakeRequest', 'Network');
App::uses('CakeSession', 'Model/Datasource');
App::uses('Hash', 'Utility');
App::uses('Router', 'Routing');
App::uses('Security', 'Utility');
App::uses('CookieCryptTrait', 'TestSuite');
App::uses('IntegrationTestDispatcher', 'TestSuite');
App::uses('IntegrationTestResponse', 'TestSuite');
App::uses('IntegrationTestStop', 'TestSuite');

/**
 * A trait intended to make integration tests of your controllers easier.
 *
 * Backport of `Cake\TestSuite\IntegrationTestTrait` from CakePHP 5.x
 * (https://github.com/cakephp/cakephp/blob/5.x/src/TestSuite/IntegrationTestTrait.php)
 * and its constraints (`Constraint/Response/*`, `Constraint/Session/*` and
 * `Constraint/View/*`).
 *
 * Requests are built from a URL and data, dispatched through the real `Dispatcher`
 * (dispatcher filters, routing, components, the controller and the view are not
 * mocked) and the resulting `CakeResponse` is captured instead of being sent. Use
 * the `assert*()` methods to check the response, the session, the cookies and the
 * flash messages:
 *
 * ```
 * class ArticlesControllerTest extends CakeTestCase
 * {
 *     use IntegrationTestTrait;
 *
 *     public function testView(): void
 *     {
 *         $this->get('/articles/view/1');
 *
 *         $this->assertResponseOk();
 *         $this->assertResponseContains('First Article');
 *         $this->assertTemplate('view');
 *     }
 * }
 * ```
 *
 * The trait can be used in any PHPUnit test case (usually `CakeTestCase`, to get
 * fixtures and the restoring of `Configure`). It does not need `ControllerTestCase`.
 *
 * Differences from CakePHP 5.x:
 *
 * - Requests are dispatched by `IntegrationTestDispatcher` (no middleware, no
 *   `Application` class). `$_GET`, `$_POST`, `$_FILES`, `$_COOKIE` and `$_SERVER` are
 *   set while the request is dispatched, as `CakeRequest` and some components read
 *   them, and restored afterwards.
 * - `exit` after sending a response (`Controller::redirect()`, `AuthComponent`, ...)
 *   is replaced by `IntegrationTestStop`, so redirects can be tested.
 * - `enableSecurityToken()` adds the `_Token` fields `SecurityComponent` validates,
 *   and its CSRF token. `enableCsrfToken()` adds only the CSRF token (`_Token.key` and
 *   the session token that `SecurityComponent` checks when `csrfCheck` is enabled).
 * - `disableErrorHandlerMiddleware()` makes exceptions propagate to the test instead of
 *   being rendered with the `Exception.renderer`.
 * - Flash messages are read from the `Message` session key (CakePHP 5: `Flash`).
 * - `cookieEncrypted()` and `assertCookieEncrypted()` use the `CookieComponent` format
 *   and default to its `cipher` mode (CakePHP 5: `aes`).
 *
 * @package       Cake.TestSuite
 */
trait IntegrationTestTrait
{
    use CookieCryptTrait;

    /**
     * The data used to build the next request.
     *
     * @var array
     */
    protected array $_request = [];

    /**
     * The response for the most recent request.
     *
     * @var CakeResponse|null
     */
    protected ?CakeResponse $_response = null;

    /**
     * The exception being thrown if the case.
     *
     * @var Throwable|null
     */
    protected ?Throwable $_exception = null;

    /**
     * Session data to use in the next request.
     *
     * @var array
     */
    protected array $_session = [];

    /**
     * Cookie data to use in the next request.
     *
     * @var array
     */
    protected array $_cookie = [];

    /**
     * The controller used in the last request.
     *
     * @var Controller|null
     */
    protected ?Controller $_controller = null;

    /**
     * The last rendered view
     *
     * @var string|null
     */
    protected ?string $_viewName = null;

    /**
     * The last rendered layout
     *
     * @var string|null
     */
    protected ?string $_layoutName = null;

    /**
     * Whether a request was dispatched in the current test.
     *
     * @var bool
     */
    protected bool $_requestDispatched = false;

    /**
     * Boolean flag for whether the request should have
     * a SecurityComponent token added.
     *
     * @var bool
     */
    protected bool $_securityToken = false;

    /**
     * Boolean flag for whether the request should have
     * a CSRF token added.
     *
     * @var bool
     */
    protected bool $_csrfToken = false;

    /**
     * Boolean flag for whether the request should re-store
     * flash messages
     *
     * @var bool
     */
    protected bool $_retainFlashMessages = false;

    /**
     * Stored flash messages before render
     *
     * @var array
     */
    protected array $_flashMessages = [];

    /**
     * The key used to encrypt cookies.
     *
     * @var string|null
     */
    protected ?string $_cookieEncryptionKey = null;

    /**
     * List of fields that are excluded from field validation.
     *
     * @var array<string>
     */
    protected array $_unlockedFields = [];

    /**
     * Whether exceptions are thrown to the test instead of being rendered.
     *
     * @var bool
     */
    protected bool $_disableErrorHandler = false;

    /**
     * Clears the state used for requests.
     *
     * Runs after each test (`@after`). If a request was dispatched in the test, the
     * session data (`$_SESSION`) is cleared too, so it does not leak into other tests.
     *
     * @after
     * @return void
     */
    public function cleanup(): void
    {
        if ($this->_requestDispatched || $this->_session) {
            $_SESSION = [];
        }
        $this->_request = [];
        $this->_session = [];
        $this->_cookie = [];
        $this->_response = null;
        $this->_exception = null;
        $this->_controller = null;
        $this->_viewName = null;
        $this->_layoutName = null;
        $this->_requestDispatched = false;
        $this->_securityToken = false;
        $this->_csrfToken = false;
        $this->_retainFlashMessages = false;
        $this->_flashMessages = [];
        $this->_cookieEncryptionKey = null;
        $this->_unlockedFields = [];
        $this->_disableErrorHandler = false;
    }

    /**
     * Calling this method will enable a SecurityComponent
     * compatible token to be added to request data. This
     * lets you easily test actions protected by SecurityComponent.
     *
     * The `_Token` fields (`fields`, `unlocked` and, in debug mode, `debug`) are
     * computed from the request data like `FormHelper::secure()` would for a form
     * with exactly these fields, and the CSRF token (`_Token.key`) is added as with
     * `enableCsrfToken()`. Tokens are added to requests with data or with a method
     * other than GET, HEAD and OPTIONS, unless the data is a string.
     *
     * Fields that are unlocked in the controller (`SecurityComponent::$unlockedFields`
     * or `FormHelper::unlockField()`) must also be passed to `setUnlockedFields()`.
     *
     * ```
     * $this->enableSecurityToken();
     * $this->post('/articles/add', ['Article' => ['title' => 'New']]);
     * ```
     *
     * @return void
     */
    public function enableSecurityToken(): void
    {
        $this->_securityToken = true;
    }

    /**
     * Set list of fields that are excluded from field validation.
     *
     * Use the dot separated field names (e.g. `Article.preview`), like
     * `SecurityComponent::$unlockedFields`.
     *
     * @param array<string> $unlockedFields List of fields that are excluded from field validation.
     * @return void
     */
    public function setUnlockedFields(array $unlockedFields = []): void
    {
        $this->_unlockedFields = $unlockedFields;
    }

    /**
     * Calling this method will add a CSRF token to the request.
     *
     * CakePHP 2 has no CSRF middleware: its CSRF protection is the `csrfCheck` of
     * `SecurityComponent`, which compares `_Token.key` in the request data with the
     * tokens stored in the session (`_Token.csrfTokens`). This method adds both. Use
     * it alone for actions where `SecurityComponent::$validatePost` is disabled;
     * `enableSecurityToken()` already adds the CSRF token.
     *
     * The CakePHP 5.x `$cookieName` parameter is not supported (CakePHP 2 has no
     * cookie based CSRF tokens).
     *
     * @return void
     */
    public function enableCsrfToken(): void
    {
        $this->_csrfToken = true;
    }

    /**
     * Calling this method will re-store flash messages into the test session
     * after being removed by the FlashHelper
     *
     * Flash messages are captured in `Controller.beforeRedirect` and
     * `Controller.beforeRender`, before `FlashHelper::render()` (or
     * `SessionHelper::flash()`) deletes them from the session, and written back to the
     * `Message` session key after the request, so `assertFlashMessage()` and
     * `assertSession()` can check them.
     *
     * @return void
     */
    public function enableRetainFlashMessages(): void
    {
        $this->_retainFlashMessages = true;
    }

    /**
     * Makes exceptions thrown while dispatching propagate to the test.
     *
     * By default, exceptions are rendered with the configured `Exception.renderer`
     * (`ExceptionRenderer` by default) like `ErrorHandler::handleException()` does,
     * and the error page becomes the response (e.g. a 404 for a
     * `NotFoundException`). After calling this method, they are thrown to the test
     * instead. This can be helpful when trying to diagnose/debug unexpected failures,
     * or to use `expectException()`.
     *
     * The name is kept from CakePHP 5.x for compatibility; CakePHP 2 has no middleware.
     *
     * ```
     * $this->disableErrorHandlerMiddleware();
     * $this->expectException(NotFoundException::class);
     * $this->get('/articles/view/999');
     * ```
     *
     * @return void
     */
    public function disableErrorHandlerMiddleware(): void
    {
        $this->_disableErrorHandler = true;
    }

    /**
     * Configures the data for the *next* request merging with existing state.
     *
     * This data is cleared in the tearDown() method.
     *
     * You can call this method multiple times to append into
     * the current state. Sub-keys will be merged with existing
     * state.
     *
     * Supported keys:
     *
     * - `headers`: Request headers, e.g. `['Accept' => 'application/json']`. They are
     *   used for the next request only.
     * - `environment`: `$_SERVER` values, e.g. `['HTTP_REFERER' => '/articles']` or
     *   `['REMOTE_ADDR' => '192.168.0.1']`.
     * - `cookies`: Request cookies (see also `cookie()`).
     * - `files`: The `$_FILES` array (in the format PHP creates).
     * - `input`: The raw request body read by `CakeRequest::input()`.
     *
     * @param array $data The request data to use.
     * @return void
     */
    public function configRequest(array $data): void
    {
        $this->_request = array_merge_recursive($data, $this->_request);
    }

    /**
     * Configures the data for the *next* request replacing existing state.
     *
     * @param array $data The request data to use. See `configRequest()`.
     * @return void
     */
    public function replaceRequest(array $data): void
    {
        $this->_request = $data;
    }

    /**
     * Sets HTTP headers for the *next* request to be identified as JSON request.
     *
     * Sets the `Accept` and `Content-Type` headers to `application/json`. Pass the
     * JSON body as a string to `post()`, `put()` or `patch()`:
     *
     * ```
     * $this->requestAsJson();
     * $this->post('/api/articles.json', json_encode(['title' => 'New']));
     * ```
     *
     * @return void
     */
    public function requestAsJson(): void
    {
        $this->configRequest([
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    /**
     * Sets session data.
     *
     * This method lets you configure the session data
     * you want to be used for requests that follow. The session
     * state is reset in each tearDown().
     *
     * You can call this method multiple times to append into
     * the current state.
     *
     * ```
     * $this->session(['Auth' => ['User' => ['id' => 1, 'username' => 'mark']]]);
     * ```
     *
     * @param array $data The session data to use.
     * @return void
     */
    public function session(array $data): void
    {
        $this->_session = $data + $this->_session;
    }

    /**
     * Sets a request cookie for future requests.
     *
     * This method lets you configure the session data
     * you want to be used for requests that follow. The session
     * state is reset in each tearDown().
     *
     * You can call this method multiple times to append into
     * the current state.
     *
     * Names with brackets are parsed like PHP does, so `CakeCookie[remember]` is
     * available as `$_COOKIE['CakeCookie']['remember']`.
     *
     * @param string $name The cookie name to use.
     * @param string $value The value of the cookie.
     * @return void
     */
    public function cookie(string $name, string $value): void
    {
        $this->_cookie[$name] = $value;
    }

    /**
     * Returns the encryption key to be used.
     *
     * @return string The key set with `cookieEncrypted()` or `assertCookieEncrypted()`,
     *   or the default key of `CookieComponent`: `Cookie.key` when it is a non-empty
     *   string, otherwise `Security.salt`.
     */
    protected function _getCookieEncryptionKey(): string
    {
        if ($this->_cookieEncryptionKey !== null) {
            return $this->_cookieEncryptionKey;
        }
        $cookieKey = Configure::read('Cookie.key');

        return is_string($cookieKey) && $cookieKey !== '' ? $cookieKey : (string)Configure::read('Security.salt');
    }

    /**
     * Sets a encrypted request cookie for future requests.
     *
     * The difference from cookie() is this encrypts the cookie
     * value like the CookieComponent.
     *
     * `CookieComponent` stores its values in a cookie named after its `name` setting
     * (`CakeCookie` by default), so use `CakeCookie[key]` as the name to set a value
     * that `$this->Cookie->read('key')` returns:
     *
     * ```
     * $this->cookieEncrypted('CakeCookie[remember]', 'yes');
     * $this->cookieEncrypted('CakeCookie[user]', ['id' => 1], 'aes');
     * ```
     *
     * @param string $name The cookie name to use.
     * @param array|string $value The value of the cookie. Arrays are JSON encoded like
     *   `CookieComponent` does.
     * @param string|false $encrypt Encryption mode to use (`cipher`, `aes` or
     *   `rijndael`, matching the `type` setting of `CookieComponent`), or false to not
     *   encrypt. Defaults to `cipher`, the default of `CookieComponent`.
     * @param string|null $key Encryption key used. Defaults
     *   to Security.salt.
     * @return void
     * @see CookieComponent::_encrypt()
     */
    public function cookieEncrypted(
        string $name,
        array|string $value,
        string|false $encrypt = 'cipher',
        ?string $key = null,
    ): void {
        $this->_cookieEncryptionKey = $key;
        $this->_cookie[$name] = $this->_encrypt($value, $encrypt);
    }

    /**
     * Performs a GET request using the current request data.
     *
     * The response of the dispatched request will be stored as
     * a property. You can use various assert methods to check the
     * response.
     *
     * @param array|string $url The URL to request. A string URL (with an optional
     *   query string, e.g. `/articles?page=2`) or a routing array.
     * @return void
     */
    public function get(array|string $url): void
    {
        $this->_sendRequest($url, 'GET');
    }

    /**
     * Performs a POST request using the current request data.
     *
     * The response of the dispatched request will be stored as
     * a property. You can use various assert methods to check the
     * response.
     *
     * @param array|string $url The URL to request.
     * @param array|string $data The data for the request. An array is used as the
     *   POST data (`$this->request->data`); a string is used as the request body
     *   (`$this->request->input()`), e.g. JSON.
     * @return void
     */
    public function post(array|string $url, array|string $data = []): void
    {
        $this->_sendRequest($url, 'POST', $data);
    }

    /**
     * Performs a PATCH request using the current request data.
     *
     * The response of the dispatched request will be stored as
     * a property. You can use various assert methods to check the
     * response.
     *
     * @param array|string $url The URL to request.
     * @param array|string $data The data for the request. See `post()`.
     * @return void
     */
    public function patch(array|string $url, array|string $data = []): void
    {
        $this->_sendRequest($url, 'PATCH', $data);
    }

    /**
     * Performs a PUT request using the current request data.
     *
     * The response of the dispatched request will be stored as
     * a property. You can use various assert methods to check the
     * response.
     *
     * @param array|string $url The URL to request.
     * @param array|string $data The data for the request. See `post()`.
     * @return void
     */
    public function put(array|string $url, array|string $data = []): void
    {
        $this->_sendRequest($url, 'PUT', $data);
    }

    /**
     * Performs a DELETE request using the current request data.
     *
     * The response of the dispatched request will be stored as
     * a property. You can use various assert methods to check the
     * response.
     *
     * @param array|string $url The URL to request.
     * @return void
     */
    public function delete(array|string $url): void
    {
        $this->_sendRequest($url, 'DELETE');
    }

    /**
     * Performs a HEAD request using the current request data.
     *
     * The response of the dispatched request will be stored as
     * a property. You can use various assert methods to check the
     * response.
     *
     * @param array|string $url The URL to request.
     * @return void
     */
    public function head(array|string $url): void
    {
        $this->_sendRequest($url, 'HEAD');
    }

    /**
     * Performs an OPTIONS request using the current request data.
     *
     * The response of the dispatched request will be stored as
     * a property. You can use various assert methods to check the
     * response.
     *
     * @param array|string $url The URL to request.
     * @return void
     */
    public function options(array|string $url): void
    {
        $this->_sendRequest($url, 'OPTIONS');
    }

    /**
     * Creates and send the request into a Dispatcher instance.
     *
     * Receives and stores the response for future inspection.
     *
     * @param array|string $url The URL
     * @param string $method The HTTP method
     * @param array|string $data The request data.
     * @return void
     * @throws \PHPUnit\Exception|\Throwable
     */
    protected function _sendRequest(array|string $url, string $method, array|string $data = []): void
    {
        $url = $this->resolveUrl($url);
        $this->_response = null;
        $this->_exception = null;
        $this->_controller = null;
        $this->_viewName = null;
        $this->_layoutName = null;
        $this->_flashMessages = [];
        $this->_requestDispatched = true;

        $globals = [$_GET, $_POST, $_FILES, $_COOKIE, $_SERVER];
        try {
            try {
                $request = $this->_buildRequest($url, $method, $data);
                $this->_response = $this->_dispatchRequest($request);
                $this->_restoreFlashMessages();
            } catch (PHPUnitException $e) {
                throw $e;
            } catch (Throwable $e) {
                $this->_exception = $e;
                if ($this->_disableErrorHandler) {
                    throw $e;
                }
                // Simulate the global exception handler being invoked.
                $this->_handleError($e);
            }
        } finally {
            [$_GET, $_POST, $_FILES, $_COOKIE, $_SERVER] = $globals;
            while (Router::getRequest()) {
                Router::popRequest();
            }
        }
    }

    /**
     * Writes the flash messages captured during the request back to the session.
     *
     * Only done when `enableRetainFlashMessages()` was called.
     *
     * @return void
     */
    protected function _restoreFlashMessages(): void
    {
        if ($this->_retainFlashMessages && $this->_flashMessages) {
            CakeSession::write('Message', $this->_flashMessages);
        }
    }

    /**
     * Dispatches a request and returns the response.
     *
     * @param CakeRequest $request The request to dispatch.
     * @return CakeResponse The response.
     */
    protected function _dispatchRequest(CakeRequest $request): CakeResponse
    {
        $dispatcher = $this->_makeDispatcher();
        try {
            $dispatcher->dispatch($request, new IntegrationTestResponse());
        } catch (IntegrationTestStop $stop) {
            return $stop->getResponse();
        }

        return $dispatcher->response;
    }

    /**
     * Resolve the provided URL into a string.
     *
     * Routing arrays are converted with `Router::url()`. String URLs are returned as is.
     *
     * @param array|string $url The URL array/string to resolve.
     * @return string
     */
    public function resolveUrl(array|string $url): string
    {
        if (is_array($url)) {
            return Router::url($url);
        }

        return $url;
    }

    /**
     * Get the correct dispatcher instance.
     *
     * @return IntegrationTestDispatcher A dispatcher instance
     */
    protected function _makeDispatcher(): IntegrationTestDispatcher
    {
        return new IntegrationTestDispatcher(function (Controller $controller): void {
            $this->controllerSpy($controller);
        });
    }

    /**
     * Adds additional event spies to the controller/view event manager.
     *
     * Stores the controller, captures the flash messages (see
     * `enableRetainFlashMessages()`) and the view and layout files that are rendered.
     *
     * @param Controller $controller Controller instance.
     * @return void
     */
    public function controllerSpy(Controller $controller): void
    {
        $this->_controller = $controller;
        $events = $controller->getEventManager();
        $flashCapture = function (CakeEvent $event): void {
            if (!$this->_retainFlashMessages) {
                return;
            }
            $this->_flashMessages = Hash::merge(
                $this->_flashMessages,
                (array)CakeSession::read('Message'),
            );
        };
        $events->attach($flashCapture, 'Controller.beforeRedirect', ['priority' => 100]);
        $events->attach($flashCapture, 'Controller.beforeRender', ['priority' => 100]);
        $events->attach(function (CakeEvent $event): void {
            if (!$this->_viewName) {
                $this->_viewName = $event->data[0];
            }
        }, 'View.beforeRender');
        $events->attach(function (CakeEvent $event): void {
            $this->_layoutName = $event->data[0];
        }, 'View.beforeLayout');
    }

    /**
     * Attempts to render an error response for a given exception.
     *
     * This method will attempt to use the configured exception renderer
     * (`Exception.renderer`, `ExceptionRenderer` by default), like
     * `ErrorHandler::handleException()`. The output is discarded and the response of
     * the error controller is stored.
     *
     * @param Throwable $exception Exception to handle.
     * @return void
     */
    protected function _handleError(Throwable $exception): void
    {
        $renderer = Configure::read('Exception.renderer') ?: 'ExceptionRenderer';
        [$plugin, $renderer] = pluginSplit($renderer, true);
        App::uses($renderer, $plugin . 'Error');
        if (!class_exists($renderer)) {
            $renderer = 'ExceptionRenderer';
        }
        /** @var ExceptionRenderer $instance */
        $instance = new $renderer($exception);
        $level = ob_get_level();
        ob_start();
        try {
            $instance->render();
        } catch (IntegrationTestStop $stop) {
            // The error page was "sent"; its response is read below.
        } finally {
            while (ob_get_level() > $level) {
                ob_end_clean();
            }
        }
        $this->_response = $instance->controller->response;
    }

    /**
     * Creates a request object with the configured options and parameters.
     *
     * Sets `$_GET`, `$_POST`, `$_FILES`, `$_COOKIE` and `$_SERVER` for the request (they
     * are restored by `_sendRequest()`), writes the configured session data and adds
     * the security and CSRF tokens.
     *
     * @param string $url The URL
     * @param string $method The HTTP method
     * @param array|string $data The request data.
     * @return CakeRequest The request
     */
    protected function _buildRequest(string $url, string $method, array|string $data = []): CakeRequest
    {
        [$path, $query, $hostInfo] = $this->_url($url);
        parse_str($query, $queryData);

        $env = [
            'REQUEST_METHOD' => $method,
            'QUERY_STRING' => $query,
            'REQUEST_URI' => $query === '' ? $path : $path . '?' . $query,
            'PHP_SELF' => '/index.php',
            'SCRIPT_NAME' => '/index.php',
        ];
        if (!empty($hostInfo['https'])) {
            $env['HTTPS'] = 'on';
        }
        if (isset($hostInfo['host'])) {
            $env['HTTP_HOST'] = $hostInfo['host'];
        }
        if (isset($this->_request['headers'])) {
            foreach ($this->_request['headers'] as $k => $v) {
                $name = strtoupper(str_replace('-', '_', $k));
                if (!in_array($name, ['CONTENT_LENGTH', 'CONTENT_TYPE'], true)) {
                    $name = 'HTTP_' . $name;
                }
                $env[$name] = $v;
            }
            unset($this->_request['headers']);
        }
        $env = (array)($this->_request['environment'] ?? []) + $env;

        $server = $_SERVER;
        unset($server['PATH_INFO'], $server['HTTP_X_HTTP_METHOD_OVERRIDE'], $server['CONTENT_TYPE']);
        foreach (array_keys($server) as $name) {
            if (str_starts_with((string)$name, 'HTTP_')) {
                unset($server[$name]);
            }
        }
        $_SERVER = $env + $server;
        $_GET = $queryData;
        $_FILES = (array)($this->_request['files'] ?? []);
        $_COOKIE = $this->_buildCookies((array)($this->_request['cookies'] ?? []) + $this->_cookie);
        $_POST = is_array($data) ? $this->_castToString($data) : [];

        if ($this->_session) {
            CakeSession::write($this->_session);
        }

        $request = new CakeRequest();
        $input = is_string($data) ? $data : ($this->_request['input'] ?? null);
        if (is_string($input)) {
            // CakeRequest::_readInput() returns the cached input instead of reading php://input.
            $property = new ReflectionProperty(CakeRequest::class, '_input');
            $property->setAccessible(true);
            $property->setValue($request, $input);
        }
        if (is_array($data) && (!in_array($method, ['GET', 'HEAD', 'OPTIONS'], true) || $data !== [])) {
            $request->data = $this->_addTokens($request, (array)$request->data);
            $_POST = $request->data;
        }

        return $request;
    }

    /**
     * Converts cookie names with brackets into nested arrays, like PHP does.
     *
     * @param array $cookies The cookies, as name => value.
     * @return array The `$_COOKIE` array.
     */
    protected function _buildCookies(array $cookies): array
    {
        $result = [];
        foreach ($cookies as $name => $value) {
            if (is_array($value) || !str_contains((string)$name, '[')) {
                $result[$name] = $value;
                continue;
            }
            parse_str($name . '=' . rawurlencode((string)$value), $parsed);
            $result = array_replace_recursive($result, $parsed);
        }

        return $result;
    }

    /**
     * Add the CSRF and SecurityComponent tokens if necessary.
     *
     * @param CakeRequest $request The request the form is being submitted with.
     * @param array $data The request body data.
     * @return array The request body with tokens added.
     */
    protected function _addTokens(CakeRequest $request, array $data): array
    {
        if ($this->_securityToken) {
            $url = $request->here();
            $unlocked = $this->_unlockedFields;
            sort($unlocked, SORT_STRING);
            $unlocked = implode('|', $unlocked);
            $fields = $this->_securedFields($data);
            $hash = Security::hash($url . serialize($fields) . $unlocked . Configure::read('Security.salt'), 'sha1');

            $data['_Token']['fields'] = urlencode($hash . ':');
            $data['_Token']['unlocked'] = urlencode($unlocked);
            if (Configure::read('debug')) {
                $data['_Token']['debug'] = urlencode((string)json_encode([$url, $fields, $this->_unlockedFields]));
            } else {
                unset($data['_Token']['debug']);
            }
        }

        if ($this->_securityToken || $this->_csrfToken) {
            if (!isset($data['_Token']['key'])) {
                $data['_Token']['key'] = $this->_createCsrfToken();
            }
            $tokens = (array)CakeSession::read('_Token.csrfTokens');
            $tokens[$data['_Token']['key']] = strtotime('+30 minutes');
            CakeSession::write('_Token.csrfTokens', $tokens);
        }

        return $data;
    }

    /**
     * Returns the list of fields `SecurityComponent` hashes for the given data.
     *
     * Mirrors `SecurityComponent::_fieldsList()` for a form without locked (hidden)
     * fields: numeric suffixes of multiple value fields are removed, unlocked fields
     * are excluded and the list is sorted.
     *
     * @param array $data The request data.
     * @return array<string> The field names.
     */
    protected function _securedFields(array $data): array
    {
        unset($data['_Token'], $data['_csrfToken']);
        $fieldList = array_keys(Hash::flatten($data));
        $multi = [];
        foreach ($fieldList as $i => $key) {
            if (preg_match('/(\.\d+){1,10}$/', (string)$key)) {
                $multi[$i] = preg_replace('/(\.\d+){1,10}$/', '', (string)$key);
                unset($fieldList[$i]);
            } else {
                $fieldList[$i] = (string)$key;
            }
        }
        if ($multi) {
            $fieldList += array_unique($multi);
        }

        foreach ($fieldList as $i => $key) {
            foreach ($this->_unlockedFields as $off) {
                $off = explode('.', $off);
                $field = array_values(array_intersect(explode('.', $key), $off));
                if ($field === $off) {
                    unset($fieldList[$i]);
                    break;
                }
            }
        }
        sort($fieldList, SORT_STRING);

        return $fieldList;
    }

    /**
     * Creates a CSRF token in the format of `SecurityComponent::_createCsrfToken()`.
     *
     * @return string
     */
    protected function _createCsrfToken(): string
    {
        $value = Security::randomBytes(16);
        $hmac = hash_hmac('sha1', $value, (string)Configure::read('Security.salt'), true);

        return base64_encode($value . $hmac);
    }

    /**
     * Recursively casts all data to string as that is how data would be POSTed in
     * the real world
     *
     * Arrays that look like uploaded files (with `error`, `tmp_name` and `size` keys)
     * are kept as is.
     *
     * @param array $data POST data
     * @return array
     */
    protected function _castToString(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_scalar($value)) {
                $data[$key] = $value === false ? '0' : (string)$value;

                continue;
            }

            if (is_array($value)) {
                $looksLikeFile = isset($value['error'], $value['tmp_name'], $value['size']);
                if ($looksLikeFile) {
                    continue;
                }

                $data[$key] = $this->_castToString($value);
            }
        }

        return $data;
    }

    /**
     * Creates a valid request url and parameter array more like Request::_url()
     *
     * @param string $url The URL
     * @return array Qualified URL, the query parameters, and host data
     */
    protected function _url(string $url): array
    {
        $parts = parse_url($url);
        if ($parts === false) {
            $parts = ['path' => $url];
        }
        $path = $parts['path'] ?? '/';
        if ($path === '' || $path[0] !== '/') {
            $path = '/' . $path;
        }
        $query = $parts['query'] ?? '';

        $hostData = [];
        if (!empty($parts['host'])) {
            $hostData['host'] = $parts['host'] . (isset($parts['port']) ? ':' . $parts['port'] : '');
        }
        if (!empty($parts['scheme'])) {
            $hostData['https'] = $parts['scheme'] === 'https';
        }

        return [$path, $query, $hostData];
    }

    /**
     * Get the response body as string
     *
     * @return string The response body.
     */
    protected function _getBodyAsString(): string
    {
        if (!$this->_response) {
            $this->fail('No response set, cannot assert content.');
        }

        return (string)$this->_response->body();
    }

    /**
     * Fetches a view variable by name.
     *
     * If the view variable does not exist, null will be returned.
     *
     * @param string $name The view variable to get.
     * @return mixed The view variable if set.
     */
    public function viewVariable(string $name): mixed
    {
        return $this->_controller?->viewVars[$name] ?? null;
    }

    /**
     * Asserts that the response status code is in the 2xx range.
     *
     * @param string $message Custom message for failure.
     * @return void
     */
    public function assertResponseOk(string $message = ''): void
    {
        $verboseMessage = $this->extractVerboseMessage($message);
        $this->assertThat(null, new StatusOkConstraint($this->_response), $verboseMessage);
    }

    /**
     * Asserts that the response status code is in the 2xx/3xx range.
     *
     * @param string $message Custom message for failure.
     * @return void
     */
    public function assertResponseSuccess(string $message = ''): void
    {
        $verboseMessage = $this->extractVerboseMessage($message);
        $this->assertThat(null, new StatusSuccessConstraint($this->_response), $verboseMessage);
    }

    /**
     * Asserts that the response status code is in the 4xx range.
     *
     * @param string $message Custom message for failure.
     * @return void
     */
    public function assertResponseError(string $message = ''): void
    {
        $this->assertThat(null, new StatusErrorConstraint($this->_response), $message);
    }

    /**
     * Asserts that the response status code is in the 5xx range.
     *
     * @param string $message Custom message for failure.
     * @return void
     */
    public function assertResponseFailure(string $message = ''): void
    {
        $this->assertThat(null, new StatusFailureConstraint($this->_response), $message);
    }

    /**
     * Asserts a specific response status code.
     *
     * @param int $code Status code to assert.
     * @param string $message Custom message for failure.
     * @return void
     */
    public function assertResponseCode(int $code, string $message = ''): void
    {
        $this->assertThat($code, new StatusCodeConstraint($this->_response), $message);
    }

    /**
     * Asserts that the Location header is correct.
     *
     * This method normalizes both the expected URL and Location header value to absolute URLs
     * for comparison (with `Router::url($url, true)`).
     *
     * @param array|string|null $url The URL you expected the client to go to. This
     *   can either be a string URL or an array compatible with Router::url(). Use null to
     *   simply check for the existence of this header.
     * @param string $message The failure message that will be appended to the generated message.
     * @return void
     */
    public function assertRedirect(array|string|null $url = null, string $message = ''): void
    {
        if (!$this->_response) {
            $this->fail('No response set, cannot assert header.');
        }

        $verboseMessage = $this->extractVerboseMessage($message);
        $this->assertThat(null, new HeaderSetConstraint($this->_response, 'Location'), $verboseMessage);

        if ($url) {
            $this->assertThat(
                Router::url($url, true),
                new HeaderEqualsConstraint($this->_normalizedLocationResponse(), 'Location'),
                $verboseMessage,
            );
        }
    }

    /**
     * Asserts that the Location header is correct. Comparison is made against exactly the URL provided.
     *
     * Like in CakePHP 5.x, both URLs are normalized to absolute URLs, so this is the same
     * as `assertRedirect()`.
     *
     * @param array|string|null $url The URL you expected the client to go to. This
     *   can either be a string URL or an array compatible with Router::url(). Use null to
     *   simply check for the existence of this header.
     * @param string $message The failure message that will be appended to the generated message.
     * @return void
     */
    public function assertRedirectEquals(array|string|null $url = null, string $message = ''): void
    {
        $this->assertRedirect($url, $message);
    }

    /**
     * Asserts that the response redirects back to the referer.
     *
     * The referer is read from the `HTTP_REFERER` environment value set with
     * `configRequest(['environment' => ['HTTP_REFERER' => '/articles']])`.
     *
     * @param int|null $code Specific status code to validate against, defaults to success (2xx-3xx) range.
     * @param string $message The failure message that will be appended to the generated message.
     * @return void
     */
    public function assertRedirectBackToReferer(?int $code = null, string $message = ''): void
    {
        if (!$this->_response) {
            $this->fail('No response set, cannot assert header.');
        }

        $verboseMessage = $this->extractVerboseMessage($message);
        $this->assertThat(null, new HeaderSetConstraint($this->_response, 'Location'), $verboseMessage);
        if ($code !== null) {
            $this->assertThat($code, new StatusCodeConstraint($this->_response), $message);
        } else {
            $this->assertThat(null, new StatusSuccessConstraint($this->_response), $verboseMessage);
        }

        $referer = $this->_request['environment']['HTTP_REFERER'] ?? null;
        if (!$referer) {
            $this->fail('No `HTTP_REFERER` set in request environment, cannot assert header.');
        }

        $this->assertThat(
            Router::url($referer, true),
            new HeaderEqualsConstraint($this->_normalizedLocationResponse(), 'Location'),
            $verboseMessage,
        );
    }

    /**
     * Returns a copy of the response with an absolute Location header.
     *
     * @return CakeResponse
     */
    protected function _normalizedLocationResponse(): CakeResponse
    {
        $response = clone $this->_response;
        $location = $response->header()['Location'] ?? '';
        $response->header('Location', Router::url(is_array($location) ? implode(', ', $location) : $location, true));

        return $response;
    }

    /**
     * Asserts that the Location header contains a substring
     *
     * @param string $url The URL you expected the client to go to.
     * @param string $message The failure message that will be appended to the generated message.
     * @return void
     */
    public function assertRedirectContains(string $url, string $message = ''): void
    {
        if (!$this->_response) {
            $this->fail('No response set, cannot assert header.');
        }

        $verboseMessage = $this->extractVerboseMessage($message);
        $this->assertThat(null, new HeaderSetConstraint($this->_response, 'Location'), $verboseMessage);
        $this->assertThat($url, new HeaderContainsConstraint($this->_response, 'Location'), $verboseMessage);
    }

    /**
     * Asserts that the Location header does not contain a substring
     *
     * @param string $url The URL you expected the client to go to.
     * @param string $message The failure message that will be appended to the generated message.
     * @return void
     */
    public function assertRedirectNotContains(string $url, string $message = ''): void
    {
        if (!$this->_response) {
            $this->fail('No response set, cannot assert header.');
        }

        $verboseMessage = $this->extractVerboseMessage($message);
        $this->assertThat(null, new HeaderSetConstraint($this->_response, 'Location'), $verboseMessage);
        $this->assertThat($url, new HeaderNotContainsConstraint($this->_response, 'Location'), $verboseMessage);
    }

    /**
     * Asserts that the Location header is not set.
     *
     * @param string $message The failure message that will be appended to the generated message.
     * @return void
     */
    public function assertNoRedirect(string $message = ''): void
    {
        $verboseMessage = $this->extractVerboseMessage($message);
        $this->assertThat(null, new HeaderNotSetConstraint($this->_response, 'Location'), $verboseMessage);
    }

    /**
     * Asserts response headers
     *
     * Header names are case-insensitive. Multiple values are joined with `, `.
     *
     * @param string $header The header to check
     * @param string $content The content to check for.
     * @param string $message The failure message that will be appended to the generated message.
     * @return void
     */
    public function assertHeader(string $header, string $content, string $message = ''): void
    {
        if (!$this->_response) {
            $this->fail('No response set, cannot assert header.');
        }

        $verboseMessage = $this->extractVerboseMessage($message);
        $this->assertThat(null, new HeaderSetConstraint($this->_response, $header), $verboseMessage);
        $this->assertThat($content, new HeaderEqualsConstraint($this->_response, $header), $verboseMessage);
    }

    /**
     * Asserts response header contains a string
     *
     * @param string $header The header to check
     * @param string $content The content to check for.
     * @param string $message The failure message that will be appended to the generated message.
     * @return void
     */
    public function assertHeaderContains(string $header, string $content, string $message = ''): void
    {
        if (!$this->_response) {
            $this->fail('No response set, cannot assert header.');
        }

        $verboseMessage = $this->extractVerboseMessage($message);
        $this->assertThat(null, new HeaderSetConstraint($this->_response, $header), $verboseMessage);
        $this->assertThat($content, new HeaderContainsConstraint($this->_response, $header), $verboseMessage);
    }

    /**
     * Asserts response header does not contain a string
     *
     * @param string $header The header to check
     * @param string $content The content to check for.
     * @param string $message The failure message that will be appended to the generated message.
     * @return void
     */
    public function assertHeaderNotContains(string $header, string $content, string $message = ''): void
    {
        if (!$this->_response) {
            $this->fail('No response set, cannot assert header.');
        }

        $verboseMessage = $this->extractVerboseMessage($message);
        $this->assertThat(null, new HeaderSetConstraint($this->_response, $header), $verboseMessage);
        $this->assertThat($content, new HeaderNotContainsConstraint($this->_response, $header), $verboseMessage);
    }

    /**
     * Asserts content type
     *
     * @param string $type The content-type to check for. A MIME type
     *   (`application/json`) or an alias known to `CakeResponse` (`json`).
     * @param string $message The failure message that will be appended to the generated message.
     * @return void
     */
    public function assertContentType(string $type, string $message = ''): void
    {
        $verboseMessage = $this->extractVerboseMessage($message);
        $this->assertThat($type, new ContentTypeConstraint($this->_response), $verboseMessage);
    }

    /**
     * Asserts content in the response body equals.
     *
     * @param mixed $content The content to check for.
     * @param string $message The failure message that will be appended to the generated message.
     * @return void
     */
    public function assertResponseEquals(mixed $content, string $message = ''): void
    {
        $verboseMessage = $this->extractVerboseMessage($message);
        if ($this->isDebug()) {
            $verboseMessage .= $this->responseBody();
        }
        $this->assertThat($content, new BodyEqualsConstraint($this->_response), $verboseMessage);
    }

    /**
     * Asserts content in the response body not equals.
     *
     * @param mixed $content The content to check for.
     * @param string $message The failure message that will be appended to the generated message.
     * @return void
     */
    public function assertResponseNotEquals(mixed $content, string $message = ''): void
    {
        $verboseMessage = $this->extractVerboseMessage($message);
        if ($this->isDebug()) {
            $verboseMessage .= $this->responseBody();
        }
        $this->assertThat($content, new BodyNotEqualsConstraint($this->_response), $verboseMessage);
    }

    /**
     * Asserts content exists in the response body.
     *
     * @param string $content The content to check for.
     * @param string $message The failure message that will be appended to the generated message.
     * @param bool $ignoreCase A flag to check whether we should ignore case or not.
     * @return void
     */
    public function assertResponseContains(string $content, string $message = '', bool $ignoreCase = false): void
    {
        if (!$this->_response) {
            $this->fail('No response set, cannot assert content.');
        }

        $verboseMessage = $this->extractVerboseMessage($message);
        if ($this->isDebug()) {
            $verboseMessage .= $this->responseBody();
        }
        $this->assertThat($content, new BodyContainsConstraint($this->_response, $ignoreCase), $verboseMessage);
    }

    /**
     * Asserts content does not exist in the response body.
     *
     * @param string $content The content to check for.
     * @param string $message The failure message that will be appended to the generated message.
     * @param bool $ignoreCase A flag to check whether we should ignore case or not.
     * @return void
     */
    public function assertResponseNotContains(string $content, string $message = '', bool $ignoreCase = false): void
    {
        if (!$this->_response) {
            $this->fail('No response set, cannot assert content.');
        }

        $verboseMessage = $this->extractVerboseMessage($message);
        if ($this->isDebug()) {
            $verboseMessage .= $this->responseBody();
        }
        $this->assertThat($content, new BodyNotContainsConstraint($this->_response, $ignoreCase), $verboseMessage);
    }

    /**
     * Asserts that the response body matches a given regular expression.
     *
     * @param string $pattern The pattern to compare against.
     * @param string $message The failure message that will be appended to the generated message.
     * @return void
     */
    public function assertResponseRegExp(string $pattern, string $message = ''): void
    {
        $verboseMessage = $this->extractVerboseMessage($message);
        if ($this->isDebug()) {
            $verboseMessage .= $this->responseBody();
        }
        $this->assertThat($pattern, new BodyRegExpConstraint($this->_response), $verboseMessage);
    }

    /**
     * Asserts that the response body does not match a given regular expression.
     *
     * @param string $pattern The pattern to compare against.
     * @param string $message The failure message that will be appended to the generated message.
     * @return void
     */
    public function assertResponseNotRegExp(string $pattern, string $message = ''): void
    {
        $verboseMessage = $this->extractVerboseMessage($message);
        if ($this->isDebug()) {
            $verboseMessage .= $this->responseBody();
        }
        $this->assertThat($pattern, new BodyNotRegExpConstraint($this->_response), $verboseMessage);
    }

    /**
     * Assert response content is not empty.
     *
     * @param string $message The failure message that will be appended to the generated message.
     * @return void
     */
    public function assertResponseNotEmpty(string $message = ''): void
    {
        if ($this->isDebug()) {
            $message .= $this->responseBody();
        }
        $this->assertThat(null, new BodyNotEmptyConstraint($this->_response), $message);
    }

    /**
     * Assert response content is empty.
     *
     * @param string $message The failure message that will be appended to the generated message.
     * @return void
     */
    public function assertResponseEmpty(string $message = ''): void
    {
        if ($this->isDebug()) {
            $message .= $this->responseBody();
        }
        $this->assertThat(null, new BodyEmptyConstraint($this->_response), $message);
    }

    /**
     * Asserts that the search string was in the template name.
     *
     * The template name is the full path of the first view file rendered (e.g.
     * `.../View/Articles/view.ctp`), so `assertTemplate('view')` or
     * `assertTemplate('Articles' . DS . 'view')` both match.
     *
     * @param string $content The content to check for.
     * @param string $message The failure message that will be appended to the generated message.
     * @return void
     */
    public function assertTemplate(string $content, string $message = ''): void
    {
        $verboseMessage = $this->extractVerboseMessage($message);
        $this->assertThat($content, new TemplateFileEqualsConstraint($this->_viewName), $verboseMessage);
    }

    /**
     * Asserts that the search string was in the layout name.
     *
     * The layout name is the full path of the rendered layout file (e.g.
     * `.../View/Layouts/default.ctp`).
     *
     * @param string $content The content to check for.
     * @param string $message The failure message that will be appended to the generated message.
     * @return void
     */
    public function assertLayout(string $content, string $message = ''): void
    {
        $verboseMessage = $this->extractVerboseMessage($message);
        $this->assertThat($content, new LayoutFileEqualsConstraint($this->_layoutName), $verboseMessage);
    }

    /**
     * Asserts session contents
     *
     * @param mixed $expected The expected contents.
     * @param string $path The session data path. Uses Hash::get() compatible notation
     * @param string $message The failure message that will be appended to the generated message.
     * @return void
     */
    public function assertSession(mixed $expected, string $path, string $message = ''): void
    {
        $verboseMessage = $this->extractVerboseMessage($message);
        $this->assertThat($expected, new SessionEqualsConstraint($path), $verboseMessage);
    }

    /**
     * Asserts session key exists.
     *
     * @param string $path The session data path. Uses Hash::get() compatible notation.
     * @param string $message The failure message that will be appended to the generated message.
     * @return void
     */
    public function assertSessionHasKey(string $path, string $message = ''): void
    {
        $verboseMessage = $this->extractVerboseMessage($message);
        $this->assertThat($path, new SessionHasKeyConstraint($path), $verboseMessage);
    }

    /**
     * Asserts a session key does not exist.
     *
     * @param string $path The session data path. Uses Hash::get() compatible notation.
     * @param string $message The failure message that will be appended to the generated message.
     * @return void
     */
    public function assertSessionNotHasKey(string $path, string $message = ''): void
    {
        $verboseMessage = $this->extractVerboseMessage($message);
        $this->assertThat($path, $this->logicalNot(new SessionHasKeyConstraint($path)), $verboseMessage);
    }

    /**
     * Returns the session data for the flash constraints.
     *
     * @return array|null The session data, or null if no request was dispatched.
     */
    protected function _requestSession(): ?array
    {
        if (!$this->_requestDispatched) {
            return null;
        }

        return (array)($_SESSION ?? []);
    }

    /**
     * Asserts a flash message was set
     *
     * Flash messages rendered by the view are removed from the session; call
     * `enableRetainFlashMessages()` before the request to check them.
     *
     * @param string $expected Expected message
     * @param string $key Flash key
     * @param string $message Assertion failure message
     * @return void
     */
    public function assertFlashMessage(string $expected, string $key = 'flash', string $message = ''): void
    {
        $verboseMessage = $this->extractVerboseMessage($message);
        $this->assertThat(
            $expected,
            new FlashParamEqualsConstraint($this->_requestSession(), $key, 'message'),
            $verboseMessage,
        );
    }

    /**
     * Asserts a flash message was set at a certain index
     *
     * @param int $at Flash index (0-based)
     * @param string $expected Expected message
     * @param string $key Flash key
     * @param string $message Assertion failure message
     * @return void
     */
    public function assertFlashMessageAt(int $at, string $expected, string $key = 'flash', string $message = ''): void
    {
        $verboseMessage = $this->extractVerboseMessage($message);
        $this->assertThat(
            $expected,
            new FlashParamEqualsConstraint($this->_requestSession(), $key, 'message', $at),
            $verboseMessage,
        );
    }

    /**
     * Asserts a flash message contains a substring
     *
     * @param string $expected Expected substring in message
     * @param string $key Flash key
     * @param string $message Assertion failure message
     * @param bool $ignoreCase Whether to ignore case
     * @return void
     */
    public function assertFlashMessageContains(
        string $expected,
        string $key = 'flash',
        string $message = '',
        bool $ignoreCase = false,
    ): void {
        $verboseMessage = $this->extractVerboseMessage($message);
        $this->assertThat(
            $expected,
            new FlashParamContainsConstraint($this->_requestSession(), $key, 'message', null, $ignoreCase),
            $verboseMessage,
        );
    }

    /**
     * Asserts a flash message contains a substring at a certain index
     *
     * @param int $at Flash index (0-based)
     * @param string $expected Expected substring in message
     * @param string $key Flash key
     * @param string $message Assertion failure message
     * @param bool $ignoreCase Whether to ignore case
     * @return void
     */
    public function assertFlashMessageContainsAt(
        int $at,
        string $expected,
        string $key = 'flash',
        string $message = '',
        bool $ignoreCase = false,
    ): void {
        $verboseMessage = $this->extractVerboseMessage($message);
        $this->assertThat(
            $expected,
            new FlashParamContainsConstraint($this->_requestSession(), $key, 'message', $at, $ignoreCase),
            $verboseMessage,
        );
    }

    /**
     * Asserts a flash element was set
     *
     * In CakePHP 2 the element names stored by `FlashComponent` are prefixed with
     * `Flash/` (e.g. `Flash/success` for `$this->Flash->success()`), and
     * `SessionComponent::setFlash()` stores `default` unless an element is given.
     *
     * @param string $expected Expected element name
     * @param string $key Flash key
     * @param string $message Assertion failure message
     * @return void
     */
    public function assertFlashElement(string $expected, string $key = 'flash', string $message = ''): void
    {
        $verboseMessage = $this->extractVerboseMessage($message);
        $this->assertThat(
            $expected,
            new FlashParamEqualsConstraint($this->_requestSession(), $key, 'element'),
            $verboseMessage,
        );
    }

    /**
     * Asserts a flash element was set at a certain index
     *
     * @param int $at Flash index (0-based)
     * @param string $expected Expected element name
     * @param string $key Flash key
     * @param string $message Assertion failure message
     * @return void
     */
    public function assertFlashElementAt(int $at, string $expected, string $key = 'flash', string $message = ''): void
    {
        $verboseMessage = $this->extractVerboseMessage($message);
        $this->assertThat(
            $expected,
            new FlashParamEqualsConstraint($this->_requestSession(), $key, 'element', $at),
            $verboseMessage,
        );
    }

    /**
     * Asserts cookie values
     *
     * The name is the cookie name in the response, e.g. `CakeCookie[remember]` for
     * `$this->Cookie->write('remember', ...)` (use `assertCookieEncrypted()` to check
     * encrypted values).
     *
     * @param mixed $expected The expected contents.
     * @param string $name The cookie name.
     * @param string $message The failure message that will be appended to the generated message.
     * @return void
     */
    public function assertCookie(mixed $expected, string $name, string $message = ''): void
    {
        $verboseMessage = $this->extractVerboseMessage($message);
        $this->assertThat($name, new CookieSetConstraint($this->_response), $verboseMessage);
        $this->assertThat($expected, new CookieEqualsConstraint($this->_response, $name), $verboseMessage);
    }

    /**
     * Asserts that a cookie is set.
     *
     * Useful when you're working with cookies that have obfuscated values
     * but the cookie being set is important.
     *
     * @param string $name The cookie name.
     * @param string $message The failure message that will be appended to the generated message.
     * @return void
     */
    public function assertCookieIsSet(string $name, string $message = ''): void
    {
        $verboseMessage = $this->extractVerboseMessage($message);
        $this->assertThat($name, new CookieSetConstraint($this->_response), $verboseMessage);
    }

    /**
     * Asserts a cookie has not been set in the response
     *
     * @param string $cookie The cookie name to check
     * @param string $message The failure message that will be appended to the generated message.
     * @return void
     */
    public function assertCookieNotSet(string $cookie, string $message = ''): void
    {
        $verboseMessage = $this->extractVerboseMessage($message);
        $this->assertThat($cookie, new CookieNotSetConstraint($this->_response), $verboseMessage);
    }

    /**
     * Asserts cookie values which are encrypted by the
     * CookieComponent.
     *
     * The difference from assertCookie() is this decrypts the cookie
     * value like the CookieComponent for this assertion.
     *
     * ```
     * $this->assertCookieEncrypted('yes', 'CakeCookie[remember]');
     * $this->assertCookieEncrypted(['id' => 1], 'CakeCookie[user]', 'aes');
     * ```
     *
     * @param mixed $expected The expected contents. Arrays written by `CookieComponent`
     *   are JSON decoded.
     * @param string $name The cookie name.
     * @param string $encrypt Encryption mode to use (`cipher`, `aes` or `rijndael`).
     *   Defaults to `cipher`, the default of `CookieComponent`.
     * @param string|null $key Encryption key used. Defaults
     *   to Security.salt.
     * @param string $message The failure message that will be appended to the generated message.
     * @return void
     * @see CookieComponent::_decrypt()
     */
    public function assertCookieEncrypted(
        mixed $expected,
        string $name,
        string $encrypt = 'cipher',
        ?string $key = null,
        string $message = '',
    ): void {
        $verboseMessage = $this->extractVerboseMessage($message);
        $this->assertThat($name, new CookieSetConstraint($this->_response), $verboseMessage);

        $this->_cookieEncryptionKey = $key;
        $this->assertThat(
            $expected,
            new CookieEncryptedEqualsConstraint($this->_response, $name, $encrypt, $this->_getCookieEncryptionKey()),
            $verboseMessage,
        );
    }

    /**
     * Asserts that a file with the given name was sent in the response
     *
     * @param string $expected The absolute file path that should be sent in the response.
     * @param string $message The failure message that will be appended to the generated message.
     * @return void
     */
    public function assertFileResponse(string $expected, string $message = ''): void
    {
        $verboseMessage = $this->extractVerboseMessage($message);
        $this->assertThat(null, new FileSentConstraint($this->_response), $verboseMessage);
        $this->assertThat($expected, new FileSentAsConstraint($this->_response), $verboseMessage);
    }

    /**
     * Inspect controller to extract possible causes of the failed assertion
     *
     * @param string $message Original message to use as a base
     * @return string
     */
    protected function extractVerboseMessage(string $message): string
    {
        if ($this->_exception instanceof Throwable) {
            $message .= $this->extractExceptionMessage($this->_exception);
        }

        return $message;
    }

    /**
     * Extract verbose message for existing exception
     *
     * @param Throwable $exception Exception to extract
     * @return string
     */
    protected function extractExceptionMessage(Throwable $exception): string
    {
        $exceptions = [$exception];
        $previous = $exception->getPrevious();
        while ($previous !== null) {
            $exceptions[] = $previous;
            $previous = $previous->getPrevious();
        }
        $message = PHP_EOL;
        foreach ($exceptions as $i => $error) {
            if ($i === 0) {
                $message .= sprintf('Possibly related to `%s`: "%s"', $error::class, $error->getMessage());
                $message .= PHP_EOL;
            } else {
                $message .= sprintf('Caused by `%s`: "%s"', $error::class, $error->getMessage());
                $message .= PHP_EOL;
            }
            $message .= $error->getTraceAsString();
            $message .= PHP_EOL;
        }

        return $message;
    }

    /**
     * Checks if debug flag is set.
     *
     * Flag is set via `--debug`.
     * Allows additional stuff like non-mocking when enabling debug. Or displaying of response body.
     *
     * @return bool Success
     */
    protected function isDebug(): bool
    {
        return !empty($_SERVER['argv']) && in_array('--debug', $_SERVER['argv'], true);
    }

    /**
     * Debug content of response body.
     *
     * @return string
     */
    protected function responseBody(): string
    {
        return PHP_EOL . '------' . PHP_EOL . ($this->_response ? $this->_response->body() : '') . PHP_EOL . '------' . PHP_EOL;
    }
}
