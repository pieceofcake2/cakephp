<?php
/**
 * AuthComponentTest file
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
 * @package       Cake.Test.Case.Controller.Component
 * @since         CakePHP(tm) v 1.2.0.5347
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Test\TestCase\Legacy\Controller\Component;

use Cake\Controller\Component\AuthComponent;
use Cake\Controller\ComponentCollection;
use Cake\Controller\Controller;
use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Error\CakeException;
use Cake\Event\CakeEvent;
use Cake\Network\CakeRequest;
use Cake\Network\CakeResponse;
use Cake\Routing\Dispatcher;
use Cake\Routing\Router;
use Cake\TestSuite\CakeTestCase;
use Cake\Utility\ClassRegistry;
use Cake\Utility\Security;
use TestAuthComponent;

App::uses('TestAuthComponent', 'Controller/Component');

/**
 * AuthTestController class
 *
 * @package       Cake.Test.Case.Controller.Component
 */
class AuthTestController extends Controller
{
    /**
     * uses property
     *
     * @var array
     */
    public $uses = ['AuthUser'];

    /**
     * components property
     *
     * @var array
     */
    public $components = ['Session', 'Flash', 'Auth'];

    /**
     * testUrl property
     *
     * @var mixed
     */
    public $testUrl = null;

    /**
     * construct method
     */
    public function __construct($request, $response)
    {
        $request->addParams(Router::parse('/auth_test'));
        $request->here = '/auth_test';
        $request->webroot = '/';
        Router::setRequestInfo($request);
        parent::__construct($request, $response);
    }

    /**
     * login method
     *
     * @return void
     */
    public function login(): void
    {
    }

    /**
     * admin_login method
     *
     * @return void
     */
    public function admin_login(): void
    {
    }

    /**
     * admin_add method
     *
     * @return void
     */
    public function admin_add(): void
    {
    }

    /**
     * logout method
     *
     * @return void
     */
    public function logout(): void
    {
    }

    /**
     * add method
     *
     * @return void
     */
    public function add(): void
    {
        echo 'add';
    }

    /**
     * add method
     *
     * @return void
     */
    public function camelCase(): void
    {
        echo 'camelCase';
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

    /**
     * isAuthorized method
     *
     * @return void
     */
    public function isAuthorized(): void
    {
    }
}

/**
 * Mock class used to test event dispatching
 *
 * @package Cake.Test.Case.Event
 */
class AuthEventTestListener
{
    public $callStack = [];

    /**
     * Test function to be used in event dispatching
     *
     * @return void
     */
    public function listenerFunction(): void
    {
        $this->callStack[] = __FUNCTION__;
    }
}

/**
 * AuthComponentTest class
 *
 * @package       Cake.Test.Case.Controller.Component
 */
class AuthComponentTest extends CakeTestCase
{
    /**
     * name property
     *
     * @var string
     */
    public $name = 'Auth';

    /**
     * fixtures property
     *
     * @var array
     */
    public $fixtures = ['core.auth_user'];

    /**
     * initialized property
     *
     * @var bool
     */
    public $initialized = false;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        App::build([
            'Model' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Model' . DS],
            'Controller' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Controller' . DS],
            'Controller/Component' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Controller' . DS . 'Component' . DS],
            'Controller/Component/Auth' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Controller' . DS . 'Component' . DS . 'Auth' . DS],
        ]);
        Configure::write('Security.salt', 'YJfIxfs2guVoUubWDYhG93b0qyJfIxfs2guwvniR2G0FgaC9mi');
        Configure::write('Security.cipherSeed', '3770011223');

        $request = new CakeRequest(null, false);

        $this->Controller = new AuthTestController($request, $this->getMock('CakeResponse'));

        $collection = new ComponentCollection();
        $collection->init($this->Controller);
        $this->Auth = new TestAuthComponent($collection);
        $this->Auth->request = $request;
        $this->Auth->response = $this->getMock('CakeResponse');
        AuthComponent::$sessionKey = 'Auth.User';

        $this->Controller->Components->init($this->Controller);

        $this->initialized = true;
        Router::reload();
        Router::connect('/:controller/:action/*');

        $User = ClassRegistry::init('AuthUser');
        $User->updateAll(['password' => $User->getDataSource()->value(Security::hash('cake', null, true))]);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        TestAuthComponent::clearUser();
        $this->Auth->Session->delete('Auth');
        $this->Auth->Session->delete('Message.auth');
        $this->Auth->Session->destroy();
        unset($this->Controller, $this->Auth);

        parent::tearDown();
    }

    /**
     * testLogin afterIdentify event method
     *
     * @return void
     */
    public function testLoginAfterIdentify(): void
    {
        $this->Auth->authenticate = [
            'TestBase',
        ];

        $user = [
            'id' => 1,
            'username' => 'mark',
        ];

        $auth = $this->Auth->getAuthenticateObject(0);
        $listener = $this->getMock(AuthEventTestListener::class);
        $auth->afterIdentifyCallable = [$listener, 'listenerFunction'];
        $event = new CakeEvent('Auth.afterIdentify', $this->Auth, ['user' => $user]);
        $listener->expects($this->once())->method('listenerFunction')->with($event);

        $result = $this->Auth->login();
        $this->assertTrue($result);
        $this->assertTrue($this->Auth->loggedIn());
        $this->assertEquals($user, $this->Auth->user());
    }

    /**
     * @return void
     */
    public function testIsAuthorizedMissingFile(): void
    {
        $this->expectException(CakeException::class);
        $this->Controller->Auth->authorize = 'Missing';
        $this->Controller->Auth->isAuthorized(['User' => ['id' => 1]]);
    }

    /**
     * @return void
     */
    public function testLoadAuthenticateNoFile(): void
    {
        $this->expectException(CakeException::class);
        $this->Controller->Auth->authenticate = 'Missing';
        $this->Controller->Auth->identify($this->Controller->request, $this->Controller->response);
    }

    /**
     * test the * key with authenticate
     *
     * @return void
     */
    public function testAllConfigWithAuthorize(): void
    {
        $this->Controller->Auth->authorize = [
            AuthComponent::ALL => ['actionPath' => 'controllers/'],
            'Actions',
        ];
        $objects = $this->Controller->Auth->constructAuthorize();
        $result = $objects[0];
        $this->assertEquals('controllers/', $result->settings['actionPath']);
    }

    /**
     * test the * key with authenticate
     *
     * @return void
     */
    public function testAllConfigWithAuthenticate(): void
    {
        $this->Controller->Auth->authenticate = [
            AuthComponent::ALL => ['userModel' => 'AuthUser'],
            'Form',
        ];
        $objects = $this->Controller->Auth->constructAuthenticate();
        $result = $objects[0];
        $this->assertEquals('AuthUser', $result->settings['userModel']);
    }

    /**
     * test defining the same Authenticate object but with different password hashers
     *
     * @return void
     */
    public function testSameAuthenticateWithDifferentHashers(): void
    {
        $this->Controller->Auth->authenticate = [
            'FormSimple' => ['className' => 'Form', 'passwordHasher' => 'Simple'],
            'FormBlowfish' => ['className' => 'Form', 'passwordHasher' => 'Blowfish'],
        ];

        $objects = $this->Controller->Auth->constructAuthenticate();
        $this->assertEquals(2, count($objects));

        $this->assertInstanceOf('FormAuthenticate', $objects[0]);
        $this->assertInstanceOf('FormAuthenticate', $objects[1]);

        $this->assertInstanceOf('SimplePasswordHasher', $objects[0]->passwordHasher());
        $this->assertInstanceOf('BlowfishPasswordHasher', $objects[1]->passwordHasher());
    }

    /**
     * testAjaxLogin method
     *
     * @return void
     */
    public function testAjaxLogin(): void
    {
        App::build([
            'View' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'View' . DS],
        ]);
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
        $Response = new CakeResponse();
        ob_start();
        $Dispatcher = new Dispatcher();
        $Dispatcher->dispatch(new CakeRequest('/ajax_auth/add'), $Response, ['return' => 1]);
        $result = ob_get_clean();

        $this->assertEquals(403, $Response->statusCode());
        $this->assertEquals("Ajax!\nthis is the test element", str_replace("\r\n", "\n", $result));
        unset($_SERVER['HTTP_X_REQUESTED_WITH']);
    }

    /**
     * testAjaxLoginResponseCode
     *
     * @return void
     */
    public function testAjaxLoginResponseCode(): void
    {
        App::build([
            'View' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'View' . DS],
        ]);
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';

        $url = '/ajax_auth/add';
        $this->Auth->request->addParams(Router::parse($url));
        $this->Auth->request->query['url'] = ltrim($url, '/');
        $this->Auth->request->base = '';
        $this->Auth->ajaxLogin = 'test_element';

        Router::setRequestInfo($this->Auth->request);

        $this->Controller->response = $this->getMock('CakeResponse', ['_sendHeader']);

        $sendHeaderCalls = [];
        $this->Controller->response->expects($this->exactly(3))
            ->method('_sendHeader')
            ->willReturnCallback(function () use (&$sendHeaderCalls) {
                $sendHeaderCalls[] = func_get_args();
            });

        $this->Auth->initialize($this->Controller);

        ob_start();
        $result = $this->Auth->startup($this->Controller);
        ob_end_clean();

        $this->assertFalse($result);
        $this->assertEquals('this is the test element', $this->Controller->response->body());
        $this->assertArrayNotHasKey('Location', $this->Controller->response->header());
        $this->assertNull($this->Controller->testUrl, 'redirect() not called');

        $this->assertNotEmpty($sendHeaderCalls);
        $this->assertEquals(['HTTP/1.1 403 Forbidden', null], $sendHeaderCalls[0]);

        unset($_SERVER['HTTP_X_REQUESTED_WITH']);
    }

    /**
     * test $settings in Controller::$components
     *
     * @return void
     */
    public function testComponentSettings(): void
    {
        $request = new CakeRequest(null, false);
        $this->Controller = new AuthTestController($request, $this->getMock('CakeResponse'));

        $this->Controller->components = [
            'Auth' => [
                'loginAction' => ['controller' => 'people', 'action' => 'login'],
                'logoutRedirect' => ['controller' => 'people', 'action' => 'login'],
            ],
            'Session',
        ];
        $this->Controller->Components->init($this->Controller);
        $this->Controller->Components->trigger('initialize', [&$this->Controller]);
        Router::reload();

        $expected = [
            'loginAction' => ['controller' => 'people', 'action' => 'login'],
            'logoutRedirect' => ['controller' => 'people', 'action' => 'login'],
        ];
        $this->assertEquals($expected['loginAction'], $this->Controller->Auth->loginAction);
        $this->assertEquals($expected['logoutRedirect'], $this->Controller->Auth->logoutRedirect);
    }
}
