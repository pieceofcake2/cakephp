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

use Cake\Controller\CakeErrorController;
use Cake\Controller\Component;
use Cake\Controller\Controller;
use Cake\Core\App;
use Cake\Core\CakePlugin;
use Cake\Core\Configure;
use Cake\Error\CakeException;
use Cake\Error\ConfigureException;
use Cake\Error\ExceptionRenderer;
use Cake\Error\FatalErrorException;
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
use Cake\Utility\Debugger;
use Cake\Utility\Folder;
use Error;
use Exception;
use InvalidArgumentException;
use OutOfBoundsException;
use PDOException;
use RuntimeException;
use TestApp\Error\TestAppsExceptionRenderer;

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
    public function initialize(Controller $controller): void
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
     * @var array|bool|null
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
    public function beforeRender(): void
    {
        echo $this->Blueberry->testName;
    }

    /**
     * index method
     *
     * @return string
     */
    public function index(): mixed
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
    public function missingWidgetThing(): void
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
 * PDOException with the query details that the pdo_error view shows.
 *
 * @package       Cake.Test.Case.Error
 */
class TestPDOException extends PDOException
{
    /**
     * The SQL query
     *
     * @var string
     */
    public $queryString;

    /**
     * The query parameters
     *
     * @var array
     */
    public $params;
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

        Configure::write('App.namespace', 'TestApp');

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
    protected function _mockResponse($error): mixed
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
    public function testSubclassMethodsNotBeingConvertedToError(): void
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
    public function testSubclassMethodsNotBeingConvertedDebug0(): void
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
    public function testSubclassConvertingFrameworkErrors(): void
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
    public function testConstruction(): void
    {
        $exception = new NotFoundException('Page not found');
        $ExceptionRenderer = new ExceptionRenderer($exception);

        $this->assertInstanceOf(CakeErrorController::class, $ExceptionRenderer->controller);
        $this->assertEquals('error400', $ExceptionRenderer->method);
        $this->assertEquals($exception, $ExceptionRenderer->error);
    }

    /**
     * test that method gets coerced when debug = 0
     *
     * @return void
     */
    public function testErrorMethodCoercion(): void
    {
        Configure::write('debug', 0);
        $exception = new MissingActionException('Page not found');
        $ExceptionRenderer = new ExceptionRenderer($exception);

        $this->assertInstanceOf(CakeErrorController::class, $ExceptionRenderer->controller);
        $this->assertEquals('error400', $ExceptionRenderer->method);
        $this->assertEquals($exception, $ExceptionRenderer->error);
    }

    /**
     * test that helpers in custom CakeErrorController are not lost
     *
     * @return void
     */
    public function testCakeErrorHelpersNotLost(): void
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
    public function testUnknownExceptionTypeWithExceptionThatHasA400Code(): void
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
    public function testUnknownExceptionTypeWithNoCodeIsA500(): void
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
    public function testUnknownExceptionInProduction(): void
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
    public function testUnknownExceptionTypeWithCodeHigherThan500(): void
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
    public function testError400(): void
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
    public function testerror400OnlyChangingCakeException(): void
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
    public function testError400NoInjection(): void
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
    public function testError500Message(): void
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
    public function testExceptionResponseHeader(): void
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
    public function testMissingController(): void
    {
        $exception = new MissingControllerException(['class' => 'PostsController']);
        $ExceptionRenderer = $this->_mockResponse(new ExceptionRenderer($exception));

        ob_start();
        $ExceptionRenderer->render();
        $result = ob_get_clean();

        $this->assertMatchesRegularExpression('/<h1 class="header-title">\s*<span>Missing Controller<\/span>/', $result);
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
                    '/<h1 class="header-title">\s*<span>Missing Method in PostsController<\/span>/',
                    '/<em>PostsController::<\/em><em>index\(\)<\/em>/',
                ],
                404,
            ],
            [
                new PrivateActionException(['controller' => 'PostsController', 'action' => '_secretSauce']),
                [
                    '/<h1 class="header-title">\s*<span>Private Method in PostsController<\/span>/',
                    '/<em>PostsController::<\/em><em>_secretSauce\(\)<\/em>/',
                ],
                404,
            ],
            [
                new MissingTableException(['table' => 'articles', 'class' => 'Article', 'ds' => 'test']),
                [
                    '/<h1 class="header-title">\s*<span>Missing Database Table<\/span>/',
                    '/Table <em>articles<\/em> for model <em>Article<\/em> was not found in datasource <em>test<\/em>/',
                ],
                500,
            ],
            [
                new MissingDatabaseException(['connection' => 'default']),
                [
                    '/<h1 class="header-title">\s*<span>Missing Database Connection<\/span>/',
                    '/Confirm you have created the file/',
                ],
                500,
            ],
            [
                new MissingViewException(['file' => '/posts/about.ctp']),
                [
                    '/Confirm you have created the file: "\/posts\/about.php"/',
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
                    '/<h1 class="header-title">\s*<span>Missing Database Connection<\/span>/',
                    '/A Database connection using "Mysql" was missing or unable to connect./',
                ],
                500,
            ],
            [
                new MissingConnectionException(['class' => 'Mysql', 'enabled' => false]),
                [
                    '/<h1 class="header-title">\s*<span>Missing Database Connection<\/span>/',
                    '/A Database connection using "Mysql" was missing or unable to connect./',
                    '/Mysql driver is NOT enabled/',
                ],
                500,
            ],
            [
                new MissingDatasourceConfigException(['config' => 'default']),
                [
                    '/<h1 class="header-title">\s*<span>Missing Datasource Configuration<\/span>/',
                    '/The datasource configuration <em>default<\/em> was not found in database.php/',
                ],
                500,
            ],
            [
                new MissingDatasourceException(['class' => 'MyDatasource', 'plugin' => 'TestDispatchPlugin']),
                [
                    '/<h1 class="header-title">\s*<span>Missing Datasource<\/span>/',
                    '/Datasource class <em>TestDispatchPlugin.MyDatasource<\/em> could not be found/',
                ],
                500,
            ],
            [
                new MissingHelperException(['class' => 'MyCustomHelper']),
                [
                    '/<h1 class="header-title">\s*<span>Missing Helper<\/span>/',
                    '/<em>MyCustomHelper<\/em> could not be found./',
                    '/Create the class <em>MyCustomHelper<\/em> below in file:/',
                    '/(\/|\\\)MyCustomHelper.php/',
                ],
                500,
            ],
            [
                new MissingBehaviorException(['class' => 'MyCustomBehavior']),
                [
                    '/<h1 class="header-title">\s*<span>Missing Behavior<\/span>/',
                    '/Create the class <em>MyCustomBehavior<\/em> below in file:/',
                    '/(\/|\\\)MyCustomBehavior.php/',
                ],
                500,
            ],
            [
                new MissingComponentException(['class' => 'SideboxComponent']),
                [
                    '/<h1 class="header-title">\s*<span>Missing Component<\/span>/',
                    '/Create the class <em>SideboxComponent<\/em> below in file:/',
                    '/(\/|\\\)SideboxComponent.php/',
                ],
                500,
            ],
            [
                new MissingPluginException(['plugin' => 'TestTwoPlugin']),
                [
                    '/<h1 class="header-title">\s*<span>Missing Plugin<\/span>/',
                    '/Make sure your plugin <em>TestTwoPlugin<\/em> is in the/',
                    '/TestTwoPlugin/',
                ],
                500,
            ],
            [
                new FatalErrorException('Allowed memory size exhausted', 500, __FILE__, 42),
                [
                    '/<h1 class="header-title">\s*<span>Fatal Error<\/span>/',
                    '/Allowed memory size exhausted/',
                    '/' . preg_quote(Debugger::trimPath(__FILE__), '/') . ' at line 42/',
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
    public function testCakeExceptionHandling($exception, $patterns, $code): void
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
    public function testMissingRenderSafe(): void
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
    public function testRenderExceptionInBeforeRender(): void
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
    public function testMissingSubdirRenderSafe(): void
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
    public function testMissingPluginRenderSafe(): void
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
    public function testMissingPluginRenderSafeWithPlugin(): void
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

        $exception = new MissingPluginException(['plugin' => 'TestTwoPlugin']);
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
    public function testRenderWithNoRequest(): void
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
    public function testPDOException(): void
    {
        $exception = new TestPDOException('There was an error in the SQL query');
        $exception->queryString = 'SELECT * from poo_query < 5 and :seven';
        $exception->params = ['seven' => 7];
        $ExceptionRenderer = new ExceptionRenderer($exception);
        $ExceptionRenderer->controller->response = $this->getMock(CakeResponse::class, ['statusCode', '_sendHeader']);
        $ExceptionRenderer->controller->response->expects($this->once())->method('statusCode')->with(500);

        ob_start();
        $ExceptionRenderer->render();
        $result = ob_get_clean();

        $this->assertMatchesRegularExpression('/<h1 class="header-title">\s*<span>Database Error<\/span>/', $result);
        $this->assertStringContainsString('There was an error in the SQL query', $result);
        $this->assertStringContainsString(h('SELECT * from poo_query < 5 and :seven'), $result);
        $this->assertStringContainsString(h("'seven' => (int) 7"), $result);
    }

    /**
     * Test that MissingConnectionException adds the error of the database server to its message
     * and to the error page.
     *
     * @return void
     */
    public function testMissingConnectionWithMessage(): void
    {
        $exception = new MissingConnectionException([
            'class' => 'Mysql',
            'message' => 'SQLSTATE[HY000] [2002] Connection refused',
        ]);
        $this->assertSame(
            'Database connection "Mysql" is missing, or could not be created. SQLSTATE[HY000] [2002] Connection refused',
            $exception->getMessage(),
        );
        $this->assertTrue($exception->getAttributes()['enabled']);

        $ExceptionRenderer = new ExceptionRenderer($exception);
        $ExceptionRenderer->controller->response = $this->getMock(CakeResponse::class, ['statusCode', '_sendHeader']);
        $ExceptionRenderer->controller->response->expects($this->once())->method('statusCode')->with(500);

        ob_start();
        $ExceptionRenderer->render();
        $result = ob_get_clean();

        $this->assertMatchesRegularExpression('/<h1 class="header-title">\s*<span>Missing Database Connection<\/span>/', $result);
        $this->assertStringContainsString('The database server returned this error: SQLSTATE[HY000] [2002] Connection refused', $result);
        $this->assertStringNotContainsString('driver is NOT enabled', $result);

        $exception = new MissingConnectionException(['class' => 'Mysql']);
        $this->assertSame('Database connection "Mysql" is missing, or could not be created.', $exception->getMessage());
    }

    /**
     * Test that rendering exceptions triggers shutdown events.
     *
     * @return void
     */
    public function testRenderShutdownEvents(): void
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

    /**
     * Test that PrivateActionException keeps the previous exception and formats its message.
     *
     * @return void
     */
    public function testPrivateActionExceptionPrevious(): void
    {
        $previous = new RuntimeException('previous');
        $exception = new PrivateActionException(['controller' => 'PostsController', 'action' => '_secretSauce'], 404, $previous);

        $this->assertSame($previous, $exception->getPrevious());
        $this->assertSame(404, $exception->getCode());
        $this->assertSame('Private Action PostsController::_secretSauce() is not directly accessible.', $exception->getMessage());
        $this->assertSame(['controller' => 'PostsController', 'action' => '_secretSauce'], $exception->getAttributes());
    }

    /**
     * Renders an exception and returns the output.
     *
     * @param Throwable $exception The exception to render.
     * @return string
     */
    protected function _render($exception): mixed
    {
        $ExceptionRenderer = $this->_mockResponse(new ExceptionRenderer($exception));

        ob_start();
        $ExceptionRenderer->render();

        return ob_get_clean();
    }

    /**
     * Use only the core error templates and layouts.
     *
     * @return void
     */
    protected function _useCoreTemplates(): void
    {
        App::build([
            'View' => [TMP . 'no_app_templates' . DS],
        ], App::RESET);
    }

    /**
     * Test that the suggested code has no App::uses(), which is deprecated.
     *
     * @return void
     */
    public function testSuggestedCodeWithoutAppUses(): void
    {
        $this->assertSame('src', basename(rtrim(APP, DS)), 'The test app uses the modern layout.');
        $code = [$this, '_suggestedCode'];

        $exceptions = [
            new MissingControllerException(['class' => 'PostsController']),
            new MissingComponentException(['class' => 'SideboxComponent']),
            new MissingHelperException(['class' => 'MyCustomHelper']),
            new MissingBehaviorException(['class' => 'MyCustomBehavior']),
        ];
        foreach ($exceptions as $exception) {
            $result = $code($this->_render($exception));
            $this->assertMatchesRegularExpression('/<\?php\s+namespace TestApp\\\\[^;]+;\s+use [^;]+;\s+class /', $result, get_class($exception));
            $this->assertStringNotContainsString('App::uses(', $result, get_class($exception));
        }

        $result = $code($this->_render(new MissingControllerException(['class' => 'PostsController', 'plugin' => 'Blog'])));
        $this->assertMatchesRegularExpression('/<\?php\s+namespace Blog\\\\Controller;\s+use Blog\\\\Controller\\\\BlogAppController;\s+class PostsController extends BlogAppController/', $result);
        $this->assertStringNotContainsString('App::uses(', $result);
    }

    /**
     * Test that the core error templates use the dev_error layout in debug mode.
     *
     * @return void
     */
    public function testDevErrorLayoutInDebugMode(): void
    {
        $result = $this->_render(new MissingControllerException(['class' => 'PostsController']));

        $this->assertStringContainsString('<title>Error: Missing Controller</title>', $result);
        $this->assertMatchesRegularExpression('/<span class="header-type">Cake\\\\Error\\\\MissingControllerException<\/span>/', $result);
        $this->assertStringContainsString('<p class="error-subheading">', $result);
        $this->assertStringContainsString('class="code-dump"', $result);
        $this->assertStringContainsString('class="stack-frames"', $result);
        $this->assertStringNotContainsString('toggle-vendor-frames', $result);
        $this->assertStringNotContainsString('code-copy', $result);
        $this->assertStringNotContainsString('<h3>Stack Trace</h3>', $result);
        $this->assertMatchesRegularExpression('/<a href="phpstorm:\/\/open\?file=[^"]+&amp;line=\d+" class="stack-frame-edit">\(edit\)<\/a>/', $result);
        $this->assertStringContainsString('In the case you tried to access a plugin controller', $result);
        $this->assertStringContainsString('templates' . DS . 'Error' . DS . 'missing_controller.php', $result);
        $this->assertStringNotContainsString('CakePHP: the rapid development php framework', $result, 'The app layout should not be used.');
        $this->assertStringNotContainsString('<h2>Missing Controller</h2>', $result);
    }

    /**
     * Test that the suggested code and paths use the application layout.
     *
     * @return void
     */
    public function testDevErrorSuggestedFile(): void
    {
        $result = $this->_render(new MissingHelperException(['class' => 'MyCustomHelper']));
        $path = substr(App::path('View/Helper')[0] . 'MyCustomHelper.php', strlen(ROOT . DS));
        $this->assertStringContainsString('below in file: ' . h($path), $result);
        // highlight_string() uses &nbsp; before PHP 8.3
        $text = str_replace("\xC2\xA0", ' ', html_entity_decode(strip_tags($result), ENT_QUOTES, 'UTF-8'));
        $this->assertStringContainsString('class MyCustomHelper extends AppHelper', $text);

        $result = $this->_render(new MissingComponentException(['class' => 'SideboxComponent', 'plugin' => 'NotLoadedPlugin']));
        $this->assertStringContainsString('<em>NotLoadedPlugin.SideboxComponent</em> could not be found.', $result);
        $this->assertStringContainsString('Make sure your plugin <em>NotLoadedPlugin</em> is in the', $result);
        $this->assertStringContainsString('plugins' . DS . 'NotLoadedPlugin' . DS . 'src' . DS . 'Controller' . DS . 'Component' . DS . 'SideboxComponent.php', $result);
    }

    /**
     * Returns the suggested code of an error page as text.
     *
     * @param string $result The rendered error page.
     * @return string
     */
    protected function _suggestedCode($result): string
    {
        $this->assertSame(1, preg_match('/<div class="code-dump">(.*?)<\/div>/s', $result, $match));
        // highlight_string() uses <br /> and &nbsp; before PHP 8.3
        $text = html_entity_decode(strip_tags(str_replace('<br />', "\n", $match[1])), ENT_QUOTES, 'UTF-8');

        return str_replace("\u{a0}", ' ', $text);
    }

    /**
     * Test that the missing template pages suggest the files of templates/, with the directory
     * names and the extension that the View looks for first.
     *
     * @return void
     */
    public function testDevErrorSuggestedTemplateFile(): void
    {
        $templates = CORE_TESTS . DS . 'test_app' . DS . 'templates' . DS;

        $result = $this->_render(new MissingViewException(['file' => 'Pages' . DS . 'nope.ctp']));
        $this->assertStringContainsString('Confirm you have created the file: "Pages' . DS . 'nope.php"', $result);
        $this->assertStringContainsString('<li>' . h($templates . 'Pages' . DS . 'nope.php') . '</li>', $result);
        $this->assertStringNotContainsString('nope.ctp</li>', $result);
        $this->assertStringNotContainsString('src' . DS . 'View' . DS . 'Pages', $result);
        $this->assertStringContainsString('create <em>' . h($templates . 'Error' . DS . 'missing_view.php') . '</em>', $result);

        $result = $this->_render(new MissingLayoutException(['file' => 'Layouts' . DS . 'nope.ctp']));
        $this->assertStringContainsString('Confirm you have created the file: layout' . DS . 'nope.php', $result);
        $this->assertStringContainsString('<li>' . h($templates . 'layout' . DS . 'nope.php') . '</li>', $result);
        $this->assertStringNotContainsString('nope.ctp</li>', $result);
        $this->assertStringContainsString('Error' . DS . 'missing_layout.php', $result);
    }

    /**
     * Test that the missing class pages suggest the code in App.namespace and the files of its PSR-4 path.
     *
     * @return void
     */
    public function testDevErrorSuggestedNamespacedCode(): void
    {
        $result = $this->_render(new MissingControllerException(['class' => 'NopeController']));
        $this->assertStringContainsString('below in file: ' . h('src' . DS . 'Controller' . DS . 'NopeController.php'), $result);
        $this->assertSame(
            "<?php\n\nnamespace TestApp\\Controller;\n\nuse TestApp\\Controller\\AppController;\n\nclass NopeController extends AppController\n{\n}",
            trim($this->_suggestedCode($result)),
        );

        $result = $this->_render(new MissingActionException(['controller' => 'PostsController', 'action' => 'nope']));
        $this->assertSame(
            "<?php\n\nnamespace TestApp\\Controller;\n\nuse TestApp\\Controller\\AppController;\n\nclass PostsController extends AppController\n{\n    public function nope()\n    {\n    }\n}",
            trim($this->_suggestedCode($result)),
        );

        $expected = [
            "namespace TestApp\\Controller\\Component;\n\nuse Cake\\Controller\\Component;\n\nclass NopeComponent extends Component" => new MissingComponentException(['class' => 'NopeComponent', 'plugin' => null]),
            "namespace TestApp\\View\\Helper;\n\nuse TestApp\\View\\Helper\\AppHelper;\n\nclass NopeHelper extends AppHelper" => new MissingHelperException(['class' => 'NopeHelper', 'plugin' => null]),
            "namespace TestApp\\Model\\Behavior;\n\nuse Cake\\Model\\ModelBehavior;\n\nclass NopeBehavior extends ModelBehavior" => new MissingBehaviorException(['class' => 'NopeBehavior', 'plugin' => null]),
            "namespace Blog\\Controller\\Component;\n\nuse Cake\\Controller\\Component;\n\nclass NopeComponent extends Component" => new MissingComponentException(['class' => 'NopeComponent', 'plugin' => 'Blog']),
        ];
        foreach ($expected as $code => $exception) {
            $this->assertStringContainsString($code, $this->_suggestedCode($this->_render($exception)), get_class($exception));
        }

        $result = $this->_render(new MissingControllerException(['class' => 'NopeController', 'plugin' => 'Blog']));
        $this->assertStringContainsString('below in file: ' . h('plugins' . DS . 'Blog' . DS . 'src' . DS . 'Controller' . DS . 'NopeController.php'), $result);
        $this->assertStringContainsString('Make sure your plugin <em>Blog</em> is in the plugins' . DS . ' directory', $result);
        $this->assertStringContainsString(
            "namespace Blog\\Controller;\n\nuse Blog\\Controller\\BlogAppController;\n\nclass NopeController extends BlogAppController",
            $this->_suggestedCode($result),
        );
    }

    /**
     * Test that the missing class pages suggest global classes without App.namespace,
     * and the files of templates/ for the templates.
     *
     * @return void
     */
    public function testDevErrorSuggestedGlobalCode(): void
    {
        Configure::delete('App.namespace');

        $result = $this->_render(new MissingControllerException(['class' => 'NopeController']));
        $this->assertSame("<?php\n\nclass NopeController extends AppController\n{\n}", trim($this->_suggestedCode($result)));
        $this->assertStringContainsString('below in file: ' . h('src' . DS . 'Controller' . DS . 'NopeController.php'), $result);
        $this->assertStringContainsString('Error' . DS . 'missing_controller.php', $result);

        $result = $this->_render(new MissingComponentException(['class' => 'NopeComponent', 'plugin' => 'Blog']));
        $this->assertSame("<?php\n\nclass NopeComponent extends Component\n{\n}", trim($this->_suggestedCode($result)));
        $this->assertStringContainsString(h(substr(App::path('Plugin')[0], strlen(ROOT . DS)) . 'Blog' . DS . 'Controller' . DS . 'Component' . DS . 'NopeComponent.php'), $result);

        $result = $this->_render(new MissingViewException(['file' => 'Pages' . DS . 'nope.ctp']));
        $this->assertStringContainsString('<li>' . h(CORE_TESTS . DS . 'test_app' . DS . 'templates' . DS . 'Pages' . DS . 'nope.php') . '</li>', $result);
    }

    /**
     * Test that values from the exception are escaped in the dev_error pages.
     *
     * @return void
     */
    public function testDevErrorEscaping(): void
    {
        $result = $this->_render(new MissingActionException([
            'controller' => 'PostsController',
            'action' => '<script>alert(1)</script>',
        ]));
        $this->assertStringNotContainsString('<script>alert(1)</script>', $result);
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $result);
    }

    /**
     * Test that previous exceptions are shown in the stack trace.
     *
     * @return void
     */
    public function testDevErrorPreviousException(): void
    {
        $this->_useCoreTemplates();
        $previous = new InvalidArgumentException('inner <problem>');
        $result = $this->_render(new RuntimeException('outer problem', 500, $previous));

        $this->assertStringContainsString('<title>Error: outer problem</title>', $result);
        $this->assertStringContainsString('<span class="header-type">RuntimeException</span>', $result);
        $this->assertStringContainsString('Caused by', $result);
        $this->assertStringContainsString('<span class="stack-exception-message">inner &lt;problem&gt;</span>', $result);
        $this->assertStringContainsString('<span class="stack-exception-type">InvalidArgumentException</span>', $result);
        $this->assertStringContainsString(Debugger::trimPath(__FILE__), $result);
        $this->assertStringContainsString('<span class="code-highlight">', $result);
    }

    /**
     * The dev_error page shows 4 lines above and 4 lines below the line of the
     * exception, numbered from their line in the file, with the line of the exception
     * highlighted.
     *
     * @return void
     */
    public function testDevErrorHighlightsTheExceptionLine(): void
    {
        $this->_useCoreTemplates();
        $line = __LINE__ + 1;
        $result = $this->_render(new RuntimeException('excerpt problem'));

        $source = file(__FILE__, FILE_IGNORE_NEW_LINES);
        $pattern = '#<td class="excerpt-number" data-number="(\d+)"></td>\s*'
            . '<td class="excerpt-line"><span class="code-highlight">(.*?)</span></td>#s';
        $this->assertMatchesRegularExpression($pattern, $result);
        preg_match($pattern, $result, $match);
        $this->assertSame((string)$line, $match[1]);
        preg_match_all('#data-number="(\d+)"#', $result, $numbers);
        $this->assertSame(range($line - 4, $line + 4), array_map('intval', array_slice($numbers[1], 0, 9)));
        $text = html_entity_decode(strip_tags($match[2]), ENT_QUOTES, 'UTF-8');
        $this->assertSame(trim($source[$line - 1]), trim(str_replace("\u{00A0}", ' ', $text)));
    }

    /**
     * Test the core error400 and error500 templates in debug mode.
     *
     * @return void
     */
    public function testDevErrorCoreError400And500(): void
    {
        $this->_useCoreTemplates();

        $result = $this->_render(new NotFoundException('Nothing to see'));
        $this->assertStringContainsString('<title>Error: Nothing to see</title>', $result);
        $this->assertStringContainsString('<span class="header-type">Cake\Error\NotFoundException</span>', $result);
        $this->assertStringContainsString('was not found on this server', $result);
        $this->assertStringContainsString('class="stack-frames"', $result);

        $result = $this->_render(new Error('Call to a member function on null'));
        $this->assertStringContainsString('<title>Error: Call to a member function on null</title>', $result);
        $this->assertStringContainsString('<span class="header-type">Error</span>', $result);
        $this->assertStringContainsString('Error in', $result);
    }

    /**
     * Test that production mode does not use the dev_error layout.
     *
     * @return void
     */
    public function testNoDevErrorLayoutInProduction(): void
    {
        Configure::write('debug', 0);
        $this->_useCoreTemplates();

        $result = $this->_render(new NotFoundException('Nothing to see'));
        $this->assertStringContainsString('<h2>Nothing to see</h2>', $result);
        $this->assertStringContainsString('CakePHP: the rapid development php framework', $result);
        $this->assertStringNotContainsString('header-title', $result);
        $this->assertStringNotContainsString('Stack Trace', $result);

        $result = $this->_render(new MissingControllerException(['class' => 'PostsController']));
        $this->assertStringContainsString('<h2>Not Found</h2>', $result);
        $this->assertStringNotContainsString('header-title', $result);
        $this->assertStringNotContainsString('PostsController', $result);

        $result = $this->_render(new RuntimeException('Secret details'));
        $this->assertStringContainsString('<h2>An Internal Error Has Occurred.</h2>', $result);
        $this->assertStringNotContainsString('header-title', $result);
        $this->assertStringNotContainsString('Secret details', $result);
    }

    /**
     * Test that error templates of the application keep the layout of the application.
     *
     * @return void
     */
    public function testAppErrorTemplateKeepsLayout(): void
    {
        $result = $this->_render(new NotFoundException('Custom message'));
        $this->assertStringContainsString('<h2>Custom message</h2>', $result);
        $this->assertStringContainsString('CakePHP: the rapid development php framework', $result);
        $this->assertStringContainsString('class="cake-stack-trace"', $result);
        $this->assertStringNotContainsString('header-title', $result);
    }

    /**
     * The exception_stack_trace element shows 2 lines above and 2 lines below the line
     * of each frame, with the line of the frame highlighted.
     *
     * @return void
     */
    public function testStackTraceElementHighlightsTheFrameLine(): void
    {
        $exception = new NotFoundException('Custom message');
        $frame = $exception->getTrace()[0];
        $result = $this->_render($exception);

        $this->assertMatchesRegularExpression('#<div id="file-excerpt-0"[^>]*><pre>(.*?)</pre></div>#s', $result);
        preg_match('#<div id="file-excerpt-0"[^>]*><pre>(.*?)</pre></div>#s', $result, $match);
        $lines = explode("\n", $match[1]);
        $source = file($frame['file'], FILE_IGNORE_NEW_LINES);
        $texts = [];
        foreach ($lines as $code) {
            $text = html_entity_decode(strip_tags($code), ENT_QUOTES, 'UTF-8');
            $texts[] = [trim(str_replace("\u{00A0}", ' ', $text)), str_starts_with($code, '<span class="code-highlight">')];
        }
        $expected = [];
        for ($i = $frame['line'] - 2; $i <= $frame['line'] + 2; $i++) {
            $expected[] = [trim($source[$i - 1]), $i === $frame['line']];
        }
        $this->assertSame($expected, $texts);
    }

    /**
     * Test that a dev_error layout in the application overrides the core one.
     *
     * @return void
     */
    public function testAppDevErrorLayoutOverride(): void
    {
        $path = TMP . 'dev_error_override' . DS;
        $Folder = new Folder($path . 'Layouts', true);
        file_put_contents($path . 'Layouts' . DS . 'dev_error.ctp', "app dev_error layout\n<?php echo \$this->fetch('title'); ?>");
        App::build([
            'View' => [$path, CORE_TESTS . DS . 'test_app' . DS . 'templates' . DS],
        ], App::RESET);

        try {
            $result = $this->_render(new MissingControllerException(['class' => 'PostsController']));
        } finally {
            $Folder->delete($path);
        }
        $this->assertStringContainsString('app dev_error layout', $result);
        $this->assertStringContainsString('Missing Controller', $result);
        $this->assertStringNotContainsString('header-title', $result);
    }

    /**
     * Test that a dev_error layout in the theme overrides the core one.
     *
     * @return void
     */
    public function testThemeDevErrorLayoutOverride(): void
    {
        $ExceptionRenderer = $this->_mockResponse(new ExceptionRenderer(new MissingControllerException(['class' => 'PostsController'])));
        $ExceptionRenderer->controller->theme = 'TestTheme';

        ob_start();
        $ExceptionRenderer->render();
        $result = ob_get_clean();

        $this->assertStringContainsString('test_theme dev_error layout', $result);
        $this->assertStringContainsString('Missing Controller', $result);
        $this->assertStringNotContainsString('header-title', $result);
    }

    /**
     * Test that AJAX requests keep their layout and get the error inline.
     *
     * @return void
     */
    public function testDevErrorNotUsedForAjax(): void
    {
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
        try {
            $result = $this->_render(new MissingControllerException(['class' => 'PostsController']));
            $this->_useCoreTemplates();
            $error400 = $this->_render(new NotFoundException('Nothing to see'));
        } finally {
            unset($_SERVER['HTTP_X_REQUESTED_WITH']);
        }
        $this->assertStringContainsString('<h2>Missing Controller</h2>', $result);
        $this->assertStringContainsString('<em>PostsController</em> could not be found.', $result);
        $this->assertStringContainsString('class="cake-stack-trace"', $result);
        $this->assertStringNotContainsString('header-title', $result);

        $this->assertStringContainsString('<h2>Nothing to see</h2>', $error400);
        $this->assertStringNotContainsString('header-title', $error400);
    }

    /**
     * Test that the safe rendering of error pages can use the dev_error layout.
     *
     * @return void
     */
    public function testOutputMessageSafeUsesDevErrorLayout(): void
    {
        $this->_useCoreTemplates();
        $exception = new MissingHelperException(['class' => 'Fail']);
        $ExceptionRenderer = new ExceptionRenderer($exception);

        $ExceptionRenderer->controller = $this->getMock(Controller::class, ['render']);
        $ExceptionRenderer->controller->request = new CakeRequest('/', false);
        $ExceptionRenderer->controller->expects($this->once())
            ->method('render')
            ->with('missingHelper')
            ->will($this->throwException($exception));

        $response = $this->getMock(CakeResponse::class);
        $response->expects($this->once())
            ->method('body')
            ->with($this->logicalAnd(
                $this->stringContains('<span class="header-type">Cake\Error\MissingHelperException</span>'),
                $this->stringContains('Helper class Fail'),
            ));

        $ExceptionRenderer->controller->response = $response;
        $ExceptionRenderer->render();
        $this->assertSame('error', $ExceptionRenderer->controller->layout);

        Configure::write('debug', 0);
        $ExceptionRenderer = new ExceptionRenderer(new NotFoundException('Missing'));
        $ExceptionRenderer->controller = $this->getMock(Controller::class, ['render']);
        $ExceptionRenderer->controller->request = new CakeRequest('/', false);
        $ExceptionRenderer->controller->expects($this->once())
            ->method('render')
            ->will($this->throwException(new MissingHelperException(['class' => 'Fail'])));

        $response = $this->getMock(CakeResponse::class);
        $response->expects($this->once())
            ->method('body')
            ->with($this->logicalAnd(
                $this->stringContains('An Internal Error Has Occurred.'),
                $this->logicalNot($this->stringContains('header-title')),
            ));
        $ExceptionRenderer->controller->response = $response;
        $ExceptionRenderer->render();
    }
}
