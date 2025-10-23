<?php
/**
 * ExceptionRendererTest file
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
 * @package       Cake.Test.Case.Error
 * @since         CakePHP(tm) v 2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Test\TestCase\Error;

use Cake\Controller\Component;
use Cake\Controller\Controller;
use Cake\Core\App;
use Cake\Core\CakePlugin;
use Cake\Core\Configure;
use Cake\Error\CakeException;
use Cake\Error\ConfigureException;
use Cake\Error\ExceptionRenderer;
use Cake\Error\InternalErrorException;
use Cake\Error\MethodNotAllowedException;
use Cake\Error\MissingActionException;
use Cake\Error\MissingBehaviorException;
use Cake\Error\MissingComponentException;
use Cake\Error\MissingConnectionException;
use Cake\Error\MissingControllerException;
use Cake\Error\MissingDatabaseException;
use Cake\Error\MissingDatasourceConfigException;
use Cake\Error\MissingDatasourceException;
use Cake\Error\MissingHelperException;
use Cake\Error\MissingLayoutException;
use Cake\Error\MissingPluginException;
use Cake\Error\MissingTableException;
use Cake\Error\MissingViewException;
use Cake\Error\NotFoundException;
use Cake\Error\PrivateActionException;
use Cake\Error\SocketException;
use Cake\Event\CakeEventManager;
use Cake\Network\CakeRequest;
use Cake\Network\CakeResponse;
use Cake\Routing\Router;
use Cake\TestSuite\CakeTestCase;
use Cake\TestSuite\Fixture\CakeTestModel;
use Exception;
use OutOfBoundsException;
use PDOException;
use RuntimeException;
use TestAppsExceptionRenderer;

/**
 * Short description for class.
 *
 * @package       Cake.Test.Case.Error
 */
class AuthBlueberryUser extends CakeTestModel
{
    /**
     * useTable property
     *
     * @var string
     */
    public $useTable = false;
}
class_alias(AuthBlueberryUser::class, 'App\\Model\\AuthBlueberryUser');

/**
 * BlueberryComponent class
 *
 * @package       Cake.Test.Case.Error
 */
class BlueberryComponent extends Component
{
    /**
     * testName property
     *
     * @return void
     */
    public $testName = null;

    /**
     * initialize method
     *
     * @param Controller $controller
     * @return void
     */
    public function initialize(Controller $controller)
    {
        $this->testName = 'BlueberryComponent';
    }
}

/**
 * TestErrorController class
 *
 * @package       Cake.Test.Case.Error
 */
class TestErrorController extends Controller
{
    /**
     * uses property
     *
     * @var array
     */
    public $uses = [];

    /**
     * components property
     *
     * @return void
     */
    public $components = ['Blueberry'];

    /**
     * beforeRender method
     *
     * @return void
     */
    public function beforeRender()
    {
        echo $this->Blueberry->testName;
    }

    /**
     * index method
     *
     * @return string
     */
    public function index()
    {
        $this->autoRender = false;

        return 'what up';
    }
}

/**
 * MyCustomExceptionRenderer class
 *
 * @package       Cake.Test.Case.Error
 */
class MyCustomExceptionRenderer extends ExceptionRenderer
{
    /**
     * custom error message type.
     *
     * @return void
     */
    public function missingWidgetThing()
    {
        echo 'widget thing is missing';
    }
}

/**
 * Exception class for testing app error handlers and custom errors.
 *
 * @package       Cake.Test.Case.Error
 */
class MissingWidgetThingException extends NotFoundException
{
}

/**
 * ExceptionRendererTest class
 *
 * @package       Cake.Test.Case.Error
 */
class ExceptionRendererTest extends CakeTestCase
{
    protected $_restoreError = false;

    /**
     * setup create a request object to get out of router later.
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        Configure::write('Config.language', 'eng');
        App::build([
            'View' => [
                CORE_TESTS . DS . 'test_app' . DS . 'src' . DS . 'View' . DS,
                CORE_TESTS . DS . 'test_app' . DS . 'templates' . DS,
            ],
        ], App::RESET);
        Router::reload();

        $request = new CakeRequest(null, false);
        $request->base = '';
        Router::setRequestInfo($request);
        Configure::write('debug', 2);
    }

    /**
     * tearDown
     *
     * @return void
     */
    public function tearDown(): void
    {
        if ($this->_restoreError) {
            restore_error_handler();
        }

        parent::tearDown();
    }

    /**
     * Mocks out the response on the ExceptionRenderer object so headers aren't modified.
     *
     * @param ExceptionRenderer $error
     * @return ExceptionRenderer
     */
    protected function _mockResponse(ExceptionRenderer $error): ExceptionRenderer
    {
        $error->controller->response = $this->getMock(CakeResponse::class, ['_sendHeader']);

        return $error;
    }

    /**
     * test that methods declared in an ExceptionRenderer subclass are not converted
     * into error400 when debug > 0
     *
     * @return void
     */
    public function testSubclassMethodsNotBeingConvertedToError()
    {
        Configure::write('debug', 2);

        $exception = new MissingWidgetThingException('Widget not found');
        $exceptionRenderer = $this->_mockResponse(new MyCustomExceptionRenderer($exception));

        ob_start();
        $exceptionRenderer->render();
        $result = ob_get_clean();

        $this->assertEquals('widget thing is missing', $result);
    }

    /**
     * test that subclass methods are not converted when debug = 0
     *
     * @return void
     */
    public function testSubclassMethodsNotBeingConvertedDebug0()
    {
        Configure::write('debug', 0);
        $exception = new MissingWidgetThingException('Widget not found');
        $exceptionRenderer = $this->_mockResponse(new MyCustomExceptionRenderer($exception));

        $this->assertEquals('missingWidgetThing', $exceptionRenderer->method);

        ob_start();
        $exceptionRenderer->render();
        $result = ob_get_clean();

        $this->assertEquals('widget thing is missing', $result, 'Method declared in subclass converted to error400');
    }

    /**
     * test that ExceptionRenderer subclasses properly convert framework errors.
     *
     * @return void
     */
    public function testSubclassConvertingFrameworkErrors()
    {
        Configure::write('debug', 0);

        $exception = new MissingControllerException('PostsController');
        $ExceptionRenderer = $this->_mockResponse(new MyCustomExceptionRenderer($exception));

        $this->assertEquals('error400', $ExceptionRenderer->method);

        ob_start();
        $ExceptionRenderer->render();
        $result = ob_get_clean();

        $this->assertMatchesRegularExpression('/Not Found/', $result, 'Method declared in error handler not converted to error400. %s');
    }

    /**
     * test things in the constructor.
     *
     * @return void
     */
    public function testConstruction()
    {
        $exception = new NotFoundException('Page not found');
        $ExceptionRenderer = new ExceptionRenderer($exception);

        $this->assertInstanceOf('CakeErrorController', $ExceptionRenderer->controller);
        $this->assertEquals('error400', $ExceptionRenderer->method);
        $this->assertEquals($exception, $ExceptionRenderer->error);
    }

    /**
     * test that method gets coerced when debug = 0
     *
     * @return void
     */
    public function testErrorMethodCoercion()
    {
        Configure::write('debug', 0);
        $exception = new MissingActionException('Page not found');
        $ExceptionRenderer = new ExceptionRenderer($exception);

        $this->assertInstanceOf('CakeErrorController', $ExceptionRenderer->controller);
        $this->assertEquals('error400', $ExceptionRenderer->method);
        $this->assertEquals($exception, $ExceptionRenderer->error);
    }

    /**
     * test that helpers in custom CakeErrorController are not lost
     *
     * @return void
     */
    public function testCakeErrorHelpersNotLost()
    {
        $testApp = CORE_TESTS . DS . 'test_app' . DS;
        App::build([
            'Controller' => [
                $testApp . 'Controller' . DS,
            ],
            'View/Helper' => [
                $testApp . 'View' . DS . 'Helper' . DS,
            ],
            'View/Layouts' => [
                $testApp . 'View' . DS . 'Layouts' . DS,
            ],
            'Error' => [
                $testApp . 'Error' . DS,
            ],
        ], App::RESET);

        $exception = new SocketException('socket exception');
        $renderer = new TestAppsExceptionRenderer($exception);

        ob_start();
        $renderer->render();
        $result = ob_get_clean();
        $this->assertStringContainsString('<b>peeled</b>', $result);
    }

    /**
     * test that unknown exception types with valid status codes are treated correctly.
     *
     * @return void
     */
    public function testUnknownExceptionTypeWithExceptionThatHasA400Code()
    {
        $exception = new MissingWidgetThingException('coding fail.');
        $ExceptionRenderer = new ExceptionRenderer($exception);
        $ExceptionRenderer->controller->response = $this->getMock(CakeResponse::class, ['statusCode', '_sendHeader']);
        $ExceptionRenderer->controller->response->expects($this->once())->method('statusCode')->with(404);

        ob_start();
        $ExceptionRenderer->render();
        $result = ob_get_clean();

        $this->assertFalse(method_exists($ExceptionRenderer, 'missingWidgetThing'), 'no method should exist.');
        $this->assertEquals('error400', $ExceptionRenderer->method, 'incorrect method coercion.');
        $this->assertStringContainsString('coding fail', $result, 'Text should show up.');
    }

    /**
     * test that unknown exception types with valid status codes are treated correctly.
     *
     * @return void
     */
    public function testUnknownExceptionTypeWithNoCodeIsA500()
    {
        $exception = new OutOfBoundsException('foul ball.');
        $ExceptionRenderer = new ExceptionRenderer($exception);
        $ExceptionRenderer->controller->response = $this->getMock(CakeResponse::class, ['statusCode', '_sendHeader']);
        $ExceptionRenderer->controller->response->expects($this->once())
            ->method('statusCode')
            ->with(500);

        ob_start();
        $ExceptionRenderer->render();
        $result = ob_get_clean();

        $this->assertEquals('error500', $ExceptionRenderer->method, 'incorrect method coercion.');
        $this->assertStringContainsString('foul ball.', $result, 'Text should show up as its debug mode.');
    }

    /**
     * test that unknown exceptions have messages ignored.
     *
     * @return void
     */
    public function testUnknownExceptionInProduction()
    {
        Configure::write('debug', 0);

        $exception = new OutOfBoundsException('foul ball.');
        $ExceptionRenderer = new ExceptionRenderer($exception);
        $ExceptionRenderer->controller->response = $this->getMock(CakeResponse::class, ['statusCode', '_sendHeader']);
        $ExceptionRenderer->controller->response->expects($this->once())
            ->method('statusCode')
            ->with(500);

        ob_start();
        $ExceptionRenderer->render();
        $result = ob_get_clean();

        $this->assertEquals('error500', $ExceptionRenderer->method, 'incorrect method coercion.');
        $this->assertStringNotContainsString('foul ball.', $result, 'Text should no show up.');
        $this->assertStringContainsString('Internal Error', $result, 'Generic message only.');
    }

    /**
     * test that unknown exception types with valid status codes are treated correctly.
     *
     * @return void
     */
    public function testUnknownExceptionTypeWithCodeHigherThan500()
    {
        $exception = new OutOfBoundsException('foul ball.', 501);
        $ExceptionRenderer = new ExceptionRenderer($exception);
        $ExceptionRenderer->controller->response = $this->getMock(CakeResponse::class, ['statusCode', '_sendHeader']);
        $ExceptionRenderer->controller->response->expects($this->once())->method('statusCode')->with(501);

        ob_start();
        $ExceptionRenderer->render();
        $result = ob_get_clean();

        $this->assertEquals('error500', $ExceptionRenderer->method, 'incorrect method coercion.');
        $this->assertStringContainsString('foul ball.', $result, 'Text should show up as its debug mode.');
    }

    /**
     * testerror400 method
     *
     * @return void
     */
    public function testError400()
    {
        Router::reload();

        $request = new CakeRequest('posts/view/1000', false);
        Router::setRequestInfo($request);

        $exception = new NotFoundException('Custom message');
        $ExceptionRenderer = new ExceptionRenderer($exception);
        $ExceptionRenderer->controller->response = $this->getMock(CakeResponse::class, ['statusCode', '_sendHeader']);
        $ExceptionRenderer->controller->response->expects($this->once())->method('statusCode')->with(404);

        ob_start();
        $ExceptionRenderer->render();
        $result = ob_get_clean();

        $this->assertMatchesRegularExpression('/<h2>Custom message<\/h2>/', $result);
        $this->assertMatchesRegularExpression("/<strong>'.*?\/posts\/view\/1000'<\/strong>/", $result);
    }

    /**
     * test that error400 only modifies the messages on CakeExceptions.
     *
     * @return void
     */
    public function testerror400OnlyChangingCakeException()
    {
        Configure::write('debug', 0);

        $exception = new NotFoundException('Custom message');
        $ExceptionRenderer = $this->_mockResponse(new ExceptionRenderer($exception));

        ob_start();
        $ExceptionRenderer->render();
        $result = ob_get_clean();
        $this->assertStringContainsString('Custom message', $result);

        $exception = new MissingActionException(['controller' => 'PostsController', 'action' => 'index']);
        $ExceptionRenderer = $this->_mockResponse(new ExceptionRenderer($exception));

        ob_start();
        $ExceptionRenderer->render();
        $result = ob_get_clean();
        $this->assertStringContainsString('Not Found', $result);
    }

    /**
     * test that error400 doesn't expose XSS
     *
     * @return void
     */
    public function testError400NoInjection()
    {
        Router::reload();

        $request = new CakeRequest('pages/<span id=333>pink</span></id><script>document.body.style.background = t=document.getElementById(333).innerHTML;window.alert(t);</script>', false);
        Router::setRequestInfo($request);

        $exception = new NotFoundException('Custom message');
        $ExceptionRenderer = $this->_mockResponse(new ExceptionRenderer($exception));

        ob_start();
        $ExceptionRenderer->render();
        $result = ob_get_clean();

        $this->assertDoesNotMatchRegularExpression('#<script>document#', $result);
        $this->assertDoesNotMatchRegularExpression('#alert\(t\);</script>#', $result);
    }

    /**
     * testError500 method
     *
     * @return void
     */
    public function testError500Message()
    {
        $exception = new InternalErrorException('An Internal Error Has Occurred');
        $ExceptionRenderer = new ExceptionRenderer($exception);
        $ExceptionRenderer->controller->response = $this->getMock(CakeResponse::class, ['statusCode', '_sendHeader']);
        $ExceptionRenderer->controller->response->expects($this->once())->method('statusCode')->with(500);

        ob_start();
        $ExceptionRenderer->render();
        $result = ob_get_clean();

        $this->assertMatchesRegularExpression('/<h2>An Internal Error Has Occurred<\/h2>/', $result);
    }

    /**
     * testExceptionResponseHeader method
     *
     * @return void
     */
    public function testExceptionResponseHeader()
    {
        $exception = new MethodNotAllowedException('Only allowing POST and DELETE');
        $exception->responseHeader(['Allow: POST, DELETE']);
        $ExceptionRenderer = new ExceptionRenderer($exception);

        //Replace response object with mocked object add back the original headers which had been set in ExceptionRenderer constructor
        $headers = $ExceptionRenderer->controller->response->header();
        $ExceptionRenderer->controller->response = $this->getMock(CakeResponse::class, ['_sendHeader']);
        $ExceptionRenderer->controller->response->header($headers);

        $sendHeaderCalls = [];
        $ExceptionRenderer->controller->response->expects($this->exactly(4))
            ->method('_sendHeader')
            ->willReturnCallback(function () use (&$sendHeaderCalls) {
                $sendHeaderCalls[] = func_get_args();
            });

        ob_start();
        $ExceptionRenderer->render();
        ob_get_clean();

        $this->assertArrayHasKey(1, $sendHeaderCalls);
        $this->assertEquals(['Allow', 'POST, DELETE'], $sendHeaderCalls[1]);
    }

    /**
     * testMissingController method
     *
     * @return void
     */
    public function testMissingController()
    {
        $exception = new MissingControllerException(['class' => 'PostsController']);
        $ExceptionRenderer = $this->_mockResponse(new ExceptionRenderer($exception));

        ob_start();
        $ExceptionRenderer->render();
        $result = ob_get_clean();

        $this->assertMatchesRegularExpression('/<h2>Missing Controller<\/h2>/', $result);
        $this->assertMatchesRegularExpression('/<em>PostsController<\/em>/', $result);
    }

    /**
     * Returns an array of tests to run for the various CakeException classes.
     *
     * @return array
     */
    public static function exceptionProvider(): array
    {
        return [
            [
                new MissingActionException(['controller' => 'PostsController', 'action' => 'index']),
                [
                    '/<h2>Missing Method in PostsController<\/h2>/',
                    '/<em>PostsController::<\/em><em>index\(\)<\/em>/',
                ],
                404,
            ],
            [
                new PrivateActionException(['controller' => 'PostsController', 'action' => '_secretSauce']),
                [
                    '/<h2>Private Method in PostsController<\/h2>/',
                    '/<em>PostsController::<\/em><em>_secretSauce\(\)<\/em>/',
                ],
                404,
            ],
            [
                new MissingTableException(['table' => 'articles', 'class' => 'Article', 'ds' => 'test']),
                [
                    '/<h2>Missing Database Table<\/h2>/',
                    '/Table <em>articles<\/em> for model <em>Article<\/em> was not found in datasource <em>test<\/em>/',
                ],
                500,
            ],
            [
                new MissingDatabaseException(['connection' => 'default']),
                [
                    '/<h2>Missing Database Connection<\/h2>/',
                    '/Confirm you have created the file/',
                ],
                500,
            ],
            [
                new MissingViewException(['file' => '/posts/about.ctp']),
                [
                    "/posts\/about.ctp/",
                ],
                500,
            ],
            [
                new MissingLayoutException(['file' => 'layouts/my_layout.ctp']),
                [
                    '/Missing Layout/',
                    "/layouts\/my_layout.ctp/",
                ],
                500,
            ],
            [
                new MissingConnectionException(['class' => 'Mysql']),
                [
                    '/<h2>Missing Database Connection<\/h2>/',
                    '/A Database connection using "Mysql" was missing or unable to connect./',
                ],
                500,
            ],
            [
                new MissingConnectionException(['class' => 'Mysql', 'enabled' => false]),
                [
                    '/<h2>Missing Database Connection<\/h2>/',
                    '/A Database connection using "Mysql" was missing or unable to connect./',
                    '/Mysql driver is NOT enabled/',
                ],
                500,
            ],
            [
                new MissingDatasourceConfigException(['config' => 'default']),
                [
                    '/<h2>Missing Datasource Configuration<\/h2>/',
                    '/The datasource configuration <em>default<\/em> was not found in database.php/',
                ],
                500,
            ],
            [
                new MissingDatasourceException(['class' => 'MyDatasource', 'plugin' => 'MyPlugin']),
                [
                    '/<h2>Missing Datasource<\/h2>/',
                    '/Datasource class <em>MyPlugin.MyDatasource<\/em> could not be found/',
                ],
                500,
            ],
            [
                new MissingHelperException(['class' => 'MyCustomHelper']),
                [
                    '/<h2>Missing Helper<\/h2>/',
                    '/<em>MyCustomHelper<\/em> could not be found./',
                    '/Create the class <em>MyCustomHelper<\/em> below in file:/',
                    '/(\/|\\\)MyCustomHelper.php/',
                ],
                500,
            ],
            [
                new MissingBehaviorException(['class' => 'MyCustomBehavior']),
                [
                    '/<h2>Missing Behavior<\/h2>/',
                    '/Create the class <em>MyCustomBehavior<\/em> below in file:/',
                    '/(\/|\\\)MyCustomBehavior.php/',
                ],
                500,
            ],
            [
                new MissingComponentException(['class' => 'SideboxComponent']),
                [
                    '/<h2>Missing Component<\/h2>/',
                    '/Create the class <em>SideboxComponent<\/em> below in file:/',
                    '/(\/|\\\)SideboxComponent.php/',
                ],
                500,
            ],
            [
                new Exception('boom'),
                [
                    '/Internal Error/',
                ],
                500,
            ],
            [
                new RuntimeException('another boom'),
                [
                    '/Internal Error/',
                ],
                500,
            ],
            [
                new CakeException('base class'),
                ['/Internal Error/'],
                500,
            ],
            [
                new ConfigureException('No file'),
                ['/Internal Error/'],
                500,
            ],
        ];
    }

    /**
     * Test the various CakeException sub classes
     *
     * @dataProvider exceptionProvider
     * @return void
     */
    public function testCakeExceptionHandling($exception, $patterns, $code)
    {
        $ExceptionRenderer = new ExceptionRenderer($exception);
        $ExceptionRenderer->controller->response = $this->getMock(CakeResponse::class, ['statusCode', '_sendHeader']);
        $ExceptionRenderer->controller->response->expects($this->once())
            ->method('statusCode')
            ->with($code);

        ob_start();
        $ExceptionRenderer->render();
        $result = ob_get_clean();

        foreach ($patterns as $pattern) {
            $this->assertMatchesRegularExpression($pattern, $result);
        }
    }

    /**
     * Test exceptions being raised when helpers are missing.
     *
     * @return void
     */
    public function testMissingRenderSafe()
    {
        $exception = new MissingHelperException(['class' => 'Fail']);
        $ExceptionRenderer = new ExceptionRenderer($exception);

        $ExceptionRenderer->controller = $this->getMock(Controller::class, ['render']);
        $ExceptionRenderer->controller->helpers = ['Fail', 'Boom'];
        $ExceptionRenderer->controller->request = $this->getMock(CakeRequest::class);
        $ExceptionRenderer->controller->expects($this->once())
            ->method('render')
            ->with('missingHelper')
            ->will($this->throwException($exception));

        $response = $this->getMock(CakeResponse::class);
        $response->expects($this->once())
            ->method('body')
            ->with($this->stringContains('Helper class Fail'));

        $ExceptionRenderer->controller->response = $response;
        $ExceptionRenderer->render();
        sort($ExceptionRenderer->controller->helpers);
        $this->assertEquals(['Form', 'Html', 'Session'], $ExceptionRenderer->controller->helpers);
    }

    /**
     * Test that exceptions in beforeRender() are handled by outputMessageSafe
     *
     * @return void
     */
    public function testRenderExceptionInBeforeRender()
    {
        $exception = new NotFoundException('Not there, sorry');
        $ExceptionRenderer = new ExceptionRenderer($exception);

        $ExceptionRenderer->controller = $this->getMock(Controller::class, ['beforeRender']);
        $ExceptionRenderer->controller->request = $this->getMock(CakeRequest::class);
        $ExceptionRenderer->controller->expects($this->any())
            ->method('beforeRender')
            ->will($this->throwException($exception));

        $response = $this->getMock(CakeResponse::class);
        $response->expects($this->once())
            ->method('body')
            ->with($this->stringContains('Not there, sorry'));

        $ExceptionRenderer->controller->response = $response;
        $ExceptionRenderer->render();
    }

    /**
     * Test that missing subDir/layoutPath don't cause other fatal errors.
     *
     * @return void
     */
    public function testMissingSubdirRenderSafe()
    {
        $exception = new NotFoundException();
        $ExceptionRenderer = new ExceptionRenderer($exception);

        $ExceptionRenderer->controller = $this->getMock(Controller::class, ['render']);
        $ExceptionRenderer->controller->helpers = ['Fail', 'Boom'];
        $ExceptionRenderer->controller->layoutPath = 'json';
        $ExceptionRenderer->controller->subDir = 'json';
        $ExceptionRenderer->controller->viewClass = 'Json';
        $ExceptionRenderer->controller->request = $this->getMock(CakeRequest::class);

        $ExceptionRenderer->controller->expects($this->once())
            ->method('render')
            ->with('error400')
            ->will($this->throwException($exception));

        $response = $this->getMock(CakeResponse::class);
        $response->expects($this->once())
            ->method('body')
            ->with($this->stringContains('Not Found'));
        $response->expects($this->once())
            ->method('type')
            ->with('html');

        $ExceptionRenderer->controller->response = $response;

        $ExceptionRenderer->render();
        $this->assertEquals('', $ExceptionRenderer->controller->layoutPath);
        $this->assertEquals('', $ExceptionRenderer->controller->subDir);
        $this->assertEquals('Errors', $ExceptionRenderer->controller->viewPath);
    }

    /**
     * Test that missing plugin disables Controller::$plugin if the two are the same plugin.
     *
     * @return void
     */
    public function testMissingPluginRenderSafe()
    {
        $exception = new NotFoundException();
        $ExceptionRenderer = new ExceptionRenderer($exception);

        $ExceptionRenderer->controller = $this->getMock(Controller::class, ['render']);
        $ExceptionRenderer->controller->plugin = 'TestPlugin';
        $ExceptionRenderer->controller->request = $this->getMock(CakeRequest::class);

        $exception = new MissingPluginException(['plugin' => 'TestPlugin']);
        $ExceptionRenderer->controller->expects($this->once())
            ->method('render')
            ->with('error400')
            ->will($this->throwException($exception));

        $response = $this->getMock(CakeResponse::class);
        $response->expects($this->once())
            ->method('body')
            ->with($this->logicalAnd(
                $this->logicalNot($this->stringContains('test plugin error500')),
                $this->stringContains('Not Found'),
            ));

        $ExceptionRenderer->controller->response = $response;
        $ExceptionRenderer->render();
    }

    /**
     * Test that missing plugin doesn't disable Controller::$plugin if the two aren't the same plugin.
     *
     * @return void
     */
    public function testMissingPluginRenderSafeWithPlugin()
    {
        App::build([
            'Plugin' => [CORE_TESTS . DS . 'test_app' . DS . 'plugins' . DS],
        ], App::RESET);
        CakePlugin::load('TestPlugin');
        $exception = new NotFoundException();
        $ExceptionRenderer = new ExceptionRenderer($exception);

        $ExceptionRenderer->controller = $this->getMock(Controller::class, ['render']);
        $ExceptionRenderer->controller->plugin = 'TestPlugin';
        $ExceptionRenderer->controller->request = $this->getMock(CakeRequest::class);

        $exception = new MissingPluginException(['plugin' => 'TestPluginTwo']);
        $ExceptionRenderer->controller->expects($this->once())
            ->method('render')
            ->with('error400')
            ->will($this->throwException($exception));

        $response = $this->getMock(CakeResponse::class);
        $response->expects($this->once())
            ->method('body')
            ->with($this->logicalAnd(
                $this->stringContains('test plugin error500'),
                $this->stringContains('Not Found'),
            ));

        $ExceptionRenderer->controller->response = $response;
        $ExceptionRenderer->render();
        CakePlugin::unload();
    }

    /**
     * Test that exceptions can be rendered when an request hasn't been registered
     * with Router
     *
     * @return void
     */
    public function testRenderWithNoRequest()
    {
        Router::reload();
        $this->assertNull(Router::getRequest(false));

        $exception = new Exception('Terrible');
        $ExceptionRenderer = new ExceptionRenderer($exception);
        $ExceptionRenderer->controller->response = $this->getMock(CakeResponse::class, ['statusCode', '_sendHeader']);
        $ExceptionRenderer->controller->response->expects($this->once())
            ->method('statusCode')
            ->with(500);

        ob_start();
        $ExceptionRenderer->render();
        $result = ob_get_clean();

        $this->assertStringContainsString('Internal Error', $result);
    }

    /**
     * Tests the output of rendering a PDOException
     *
     * @return void
     */
    public function testPDOException()
    {
        $exception = new PDOException('There was an error in the SQL query');
        $exception->queryString = 'SELECT * from poo_query < 5 and :seven';
        $exception->params = ['seven' => 7];
        $ExceptionRenderer = new ExceptionRenderer($exception);
        $ExceptionRenderer->controller->response = $this->getMock(CakeResponse::class, ['statusCode', '_sendHeader']);
        $ExceptionRenderer->controller->response->expects($this->once())->method('statusCode')->with(500);

        ob_start();
        $ExceptionRenderer->render();
        $result = ob_get_clean();

        $this->assertStringContainsString('<h2>Database Error</h2>', $result);
        $this->assertStringContainsString('There was an error in the SQL query', $result);
        $this->assertStringContainsString(h('SELECT * from poo_query < 5 and :seven'), $result);
        $this->assertStringContainsString("'seven' => (int) 7", $result);
    }

    /**
     * Test that rendering exceptions triggers shutdown events.
     *
     * @return void
     */
    public function testRenderShutdownEvents()
    {
        $fired = [];
        $listener = function ($event) use (&$fired) {
            $fired[] = $event->name();
        };

        $EventManager = CakeEventManager::instance();
        $EventManager->attach($listener, 'Controller.shutdown');
        $EventManager->attach($listener, 'Dispatcher.afterDispatch');

        $exception = new Exception('Terrible');
        $ExceptionRenderer = new ExceptionRenderer($exception);

        ob_start();
        $ExceptionRenderer->render();
        ob_get_clean();

        $expected = ['Controller.shutdown', 'Dispatcher.afterDispatch'];
        $this->assertEquals($expected, $fired);
    }
}
