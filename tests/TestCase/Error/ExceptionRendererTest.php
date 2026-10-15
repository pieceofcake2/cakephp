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
     * @return void
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
                CORE_TESTS . DS . 'test_app' . DS . 'View' . DS,
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
     * @return void
     */
    protected function _mockResponse($error)
    {
        $error->controller->response = $this->getMock('CakeResponse', ['_sendHeader']);

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
        $ExceptionRenderer = $this->_mockResponse(new MyCustomExceptionRenderer($exception));

        ob_start();
        $ExceptionRenderer->render();
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
        $ExceptionRenderer = $this->_mockResponse(new MyCustomExceptionRenderer($exception));

        $this->assertEquals('missingWidgetThing', $ExceptionRenderer->method);

        ob_start();
        $ExceptionRenderer->render();
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
        $ExceptionRenderer->controller->response = $this->getMock('CakeResponse', ['statusCode', '_sendHeader']);
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
        $ExceptionRenderer->controller->response = $this->getMock('CakeResponse', ['statusCode', '_sendHeader']);
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
        $ExceptionRenderer->controller->response = $this->getMock('CakeResponse', ['statusCode', '_sendHeader']);
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
        $ExceptionRenderer->controller->response = $this->getMock('CakeResponse', ['statusCode', '_sendHeader']);
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
        $ExceptionRenderer->controller->response = $this->getMock('CakeResponse', ['statusCode', '_sendHeader']);
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
        $ExceptionRenderer->controller->response = $this->getMock('CakeResponse', ['statusCode', '_sendHeader']);
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
        $ExceptionRenderer->controller->response = $this->getMock('CakeResponse', ['_sendHeader']);
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
                    '/Confirm you have created the file: "\/posts\/about.ctp"/',
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
                new MissingDatasourceException(['class' => 'MyDatasource', 'plugin' => 'MyPlugin']),
                [
                    '/<h1 class="header-title">\s*<span>Missing Datasource<\/span>/',
                    '/Datasource class <em>MyPlugin.MyDatasource<\/em> could not be found/',
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
                new MissingPluginException(['plugin' => 'TestPluginTwo']),
                [
                    '/<h1 class="header-title">\s*<span>Missing Plugin<\/span>/',
                    '/Make sure your plugin <em>TestPluginTwo<\/em> is in the/',
                    '/TestPluginTwo/',
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
    public function testCakeExceptionHandling($exception, $patterns, $code)
    {
        $ExceptionRenderer = new ExceptionRenderer($exception);
        $ExceptionRenderer->controller->response = $this->getMock('CakeResponse', ['statusCode', '_sendHeader']);
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

        $ExceptionRenderer->controller = $this->getMock('Controller', ['render']);
        $ExceptionRenderer->controller->helpers = ['Fail', 'Boom'];
        $ExceptionRenderer->controller->request = $this->getMock('CakeRequest');
        $ExceptionRenderer->controller->expects($this->once())
            ->method('render')
            ->with('missingHelper')
            ->will($this->throwException($exception));

        $response = $this->getMock('CakeResponse');
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

        $ExceptionRenderer->controller = $this->getMock('Controller', ['beforeRender']);
        $ExceptionRenderer->controller->request = $this->getMock('CakeRequest');
        $ExceptionRenderer->controller->expects($this->any())
            ->method('beforeRender')
            ->will($this->throwException($exception));

        $response = $this->getMock('CakeResponse');
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

        $ExceptionRenderer->controller = $this->getMock('Controller', ['render']);
        $ExceptionRenderer->controller->helpers = ['Fail', 'Boom'];
        $ExceptionRenderer->controller->layoutPath = 'json';
        $ExceptionRenderer->controller->subDir = 'json';
        $ExceptionRenderer->controller->viewClass = 'Json';
        $ExceptionRenderer->controller->request = $this->getMock('CakeRequest');

        $ExceptionRenderer->controller->expects($this->once())
            ->method('render')
            ->with('error400')
            ->will($this->throwException($exception));

        $response = $this->getMock('CakeResponse');
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

        $ExceptionRenderer->controller = $this->getMock('Controller', ['render']);
        $ExceptionRenderer->controller->plugin = 'TestPlugin';
        $ExceptionRenderer->controller->request = $this->getMock('CakeRequest');

        $exception = new MissingPluginException(['plugin' => 'TestPlugin']);
        $ExceptionRenderer->controller->expects($this->once())
            ->method('render')
            ->with('error400')
            ->will($this->throwException($exception));

        $response = $this->getMock('CakeResponse');
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
            'Plugin' => [CORE_TESTS . DS . 'test_app' . DS . 'Plugin' . DS],
        ], App::RESET);
        CakePlugin::load('TestPlugin');
        $exception = new NotFoundException();
        $ExceptionRenderer = new ExceptionRenderer($exception);

        $ExceptionRenderer->controller = $this->getMock('Controller', ['render']);
        $ExceptionRenderer->controller->plugin = 'TestPlugin';
        $ExceptionRenderer->controller->request = $this->getMock('CakeRequest');

        $exception = new MissingPluginException(['plugin' => 'TestPluginTwo']);
        $ExceptionRenderer->controller->expects($this->once())
            ->method('render')
            ->with('error400')
            ->will($this->throwException($exception));

        $response = $this->getMock('CakeResponse');
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
        $ExceptionRenderer->controller->response = $this->getMock('CakeResponse', ['statusCode', '_sendHeader']);
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
        $ExceptionRenderer->controller->response = $this->getMock('CakeResponse', ['statusCode', '_sendHeader']);
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

    /**
     * Test that PrivateActionException keeps the previous exception and formats its message.
     *
     * @return void
     */
    public function testPrivateActionExceptionPrevious()
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
    protected function _render($exception)
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
    protected function _useCoreTemplates()
    {
        App::build([
            'View' => [TMP . 'no_app_templates' . DS],
        ], App::RESET);
    }

    /**
     * Test that the suggested code has no App::uses() for the modern layout, where the
     * Composer classmap autoloads the application and core classes, except for plugin classes.
     *
     * @return void
     */
    public function testSuggestedCodeWithoutAppUses()
    {
        $this->assertSame('src', basename(rtrim(APP, DS)), 'The test app uses the modern layout.');
        // highlight_string() uses <br /> and &nbsp; before PHP 8.3
        $code = function ($result) {
            $text = html_entity_decode(strip_tags(str_replace('<br />', "\n", $result)), ENT_QUOTES, 'UTF-8');

            return str_replace("\u{a0}", ' ', $text);
        };

        $exceptions = [
            new MissingControllerException(['class' => 'PostsController']),
            new MissingComponentException(['class' => 'SideboxComponent']),
            new MissingHelperException(['class' => 'MyCustomHelper']),
            new MissingBehaviorException(['class' => 'MyCustomBehavior']),
        ];
        foreach ($exceptions as $exception) {
            $result = $code($this->_render($exception));
            $this->assertMatchesRegularExpression('/<\?php\s+class /', $result, get_class($exception));
            $this->assertStringNotContainsString('App::uses(', $result, get_class($exception));
        }

        $result = $code($this->_render(new MissingControllerException(['class' => 'PostsController', 'plugin' => 'Blog'])));
        $this->assertStringContainsString("App::uses('BlogAppController', 'Blog.Controller');", $result);
    }

    /**
     * Test that the core error templates use the dev_error layout in debug mode.
     *
     * @return void
     */
    public function testDevErrorLayoutInDebugMode()
    {
        $result = $this->_render(new MissingControllerException(['class' => 'PostsController']));

        $this->assertStringContainsString('<title>Error: Missing Controller</title>', $result);
        $this->assertMatchesRegularExpression('/<span class="header-type">MissingControllerException<\/span>/', $result);
        $this->assertStringContainsString('<p class="error-subheading">', $result);
        $this->assertStringContainsString('class="code-dump"', $result);
        $this->assertStringContainsString('class="stack-frames"', $result);
        $this->assertStringNotContainsString('toggle-vendor-frames', $result);
        $this->assertStringNotContainsString('code-copy', $result);
        $this->assertStringNotContainsString('<h3>Stack Trace</h3>', $result);
        $this->assertMatchesRegularExpression('/<a href="phpstorm:\/\/open\?file=[^"]+&amp;line=\d+" class="stack-frame-edit">\(edit\)<\/a>/', $result);
        $this->assertStringContainsString('In the case you tried to access a plugin controller', $result);
        $this->assertStringContainsString('Errors' . DS . 'missing_controller.ctp', $result);
        $this->assertStringNotContainsString('CakePHP: the rapid development php framework', $result, 'The app layout should not be used.');
        $this->assertStringNotContainsString('<h2>Missing Controller</h2>', $result);
    }

    /**
     * Test that the suggested code and paths use the application layout.
     *
     * @return void
     */
    public function testDevErrorSuggestedFile()
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
        $this->assertStringContainsString('NotLoadedPlugin' . DS . 'Controller' . DS . 'Component' . DS . 'SideboxComponent.php', $result);
    }

    /**
     * Test that values from the exception are escaped in the dev_error pages.
     *
     * @return void
     */
    public function testDevErrorEscaping()
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
    public function testDevErrorPreviousException()
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
     * Test the core error400 and error500 templates in debug mode.
     *
     * @return void
     */
    public function testDevErrorCoreError400And500()
    {
        $this->_useCoreTemplates();

        $result = $this->_render(new NotFoundException('Nothing to see'));
        $this->assertStringContainsString('<title>Error: Nothing to see</title>', $result);
        $this->assertStringContainsString('<span class="header-type">NotFoundException</span>', $result);
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
    public function testNoDevErrorLayoutInProduction()
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
    public function testAppErrorTemplateKeepsLayout()
    {
        $result = $this->_render(new NotFoundException('Custom message'));
        $this->assertStringContainsString('<h2>Custom message</h2>', $result);
        $this->assertStringContainsString('CakePHP: the rapid development php framework', $result);
        $this->assertStringContainsString('class="cake-stack-trace"', $result);
        $this->assertStringNotContainsString('header-title', $result);
    }

    /**
     * Test that a dev_error layout in the application overrides the core one.
     *
     * @return void
     */
    public function testAppDevErrorLayoutOverride()
    {
        $path = TMP . 'dev_error_override' . DS;
        $Folder = new Folder($path . 'Layouts', true);
        file_put_contents($path . 'Layouts' . DS . 'dev_error.ctp', "app dev_error layout\n<?php echo \$this->fetch('title'); ?>");
        App::build([
            'View' => [$path, CORE_TESTS . DS . 'test_app' . DS . 'View' . DS],
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
    public function testThemeDevErrorLayoutOverride()
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
    public function testDevErrorNotUsedForAjax()
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
    public function testOutputMessageSafeUsesDevErrorLayout()
    {
        $this->_useCoreTemplates();
        $exception = new MissingHelperException(['class' => 'Fail']);
        $ExceptionRenderer = new ExceptionRenderer($exception);

        $ExceptionRenderer->controller = $this->getMock('Controller', ['render']);
        $ExceptionRenderer->controller->request = new CakeRequest('/', false);
        $ExceptionRenderer->controller->expects($this->once())
            ->method('render')
            ->with('missingHelper')
            ->will($this->throwException($exception));

        $response = $this->getMock('CakeResponse');
        $response->expects($this->once())
            ->method('body')
            ->with($this->logicalAnd(
                $this->stringContains('<span class="header-type">MissingHelperException</span>'),
                $this->stringContains('Helper class Fail'),
            ));

        $ExceptionRenderer->controller->response = $response;
        $ExceptionRenderer->render();
        $this->assertSame('error', $ExceptionRenderer->controller->layout);

        Configure::write('debug', 0);
        $ExceptionRenderer = new ExceptionRenderer(new NotFoundException('Missing'));
        $ExceptionRenderer->controller = $this->getMock('Controller', ['render']);
        $ExceptionRenderer->controller->request = new CakeRequest('/', false);
        $ExceptionRenderer->controller->expects($this->once())
            ->method('render')
            ->will($this->throwException(new MissingHelperException(['class' => 'Fail'])));

        $response = $this->getMock('CakeResponse');
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
