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
 * @link          https://cakephp.org CakePHP Project
 * @package       Cake.Test.Case.Controller
 * @since         CakePHP(tm) v 1.2.0.5436
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Test\TestCase\Legacy\Controller;

use Cake\Controller\Controller;
use Cake\Core\App;
use Cake\Core\CakePlugin;
use Cake\Network\CakeRequest;
use Cake\Network\CakeResponse;
use Cake\Routing\Router;
use Cake\TestSuite\CakeTestCase;
use Cake\Utility\ClassRegistry;
use Cake\Utility\Hash;
use TestLegacyPluginController;

App::uses('TestLegacyPluginAppController', 'TestLegacyPlugin.Controller');
App::uses('TestLegacyPluginController', 'TestLegacyPlugin.Controller');

/**
 * AppController class
 *
 * @package       Cake.Test.Case.Controller
 */
class ControllerTestAppController extends Controller
{
    /**
     * helpers property
     *
     * @var array
     */
    public $helpers = ['Html'];

    /**
     * uses property
     *
     * @var array
     */
    public $uses = ['ControllerPost'];

    /**
     * components property
     *
     * @var array
     */
    public $components = ['Cookie'];
}

/**
 * ControllerPostsController class
 *
 * @package       Cake.Test.Case.Controller
 */
class ControllerCommentsController extends ControllerTestAppController
{
    protected $_mergeParent = 'ControllerTestAppController';
}

/**
 * TestController class
 *
 * @package       Cake.Test.Case.Controller
 */
class TestController extends ControllerTestAppController
{
    /**
     * helpers property
     *
     * @var array
     */
    public $helpers = ['Session'];

    /**
     * components property
     *
     * @var array
     */
    public $components = ['Security'];

    /**
     * uses property
     *
     * @var array
     */
    public $uses = ['ControllerComment', 'ControllerAlias'];

    protected $_mergeParent = 'ControllerTestAppController';
}

/**
 * AnotherTestController class
 *
 * @package       Cake.Test.Case.Controller
 */
class AnotherTestController extends ControllerTestAppController
{
    /**
     * uses property
     *
     * @var array
     */
    public $uses = false;

    /**
     * merge parent
     *
     * @var string
     */
    protected $_mergeParent = 'ControllerTestAppController';
}

/**
 * ControllerTest class
 *
 * @package       Cake.Test.Case.Controller
 */
class ControllerTest extends CakeTestCase
{
    /**
     * fixtures property
     *
     * @var array
     */
    public $fixtures = [
        'core.post',
        'core.comment',
    ];

    /**
     * reset environment.
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        App::objects('plugin', null, false);
        App::build();
        App::build([
            'Model' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Model' . DS],
            'Controller/Component' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Controller' . DS . 'Component' . DS],
        ]);
        Router::reload();
    }

    /**
     * tearDown
     *
     * @return void
     */
    public function tearDown(): void
    {
        CakePlugin::unload();

        parent::tearDown();
    }

    /**
     * testLoadModel method
     *
     * @return void
     */
    public function testLoadModel(): void
    {
        $request = new CakeRequest('controller_posts/index');
        $response = $this->getMock('CakeResponse');
        $Controller = new Controller($request, $response);

        $this->assertFalse(isset($Controller->ControllerPost));

        $result = $Controller->loadModel('ControllerPost');
        $this->assertTrue($result);
        $this->assertInstanceOf('ControllerPost', $Controller->ControllerPost);
        $this->assertContains('ControllerPost', $Controller->uses);
    }

    /**
     * testLoadModel method from a plugin controller
     *
     * @return void
     */
    public function testLoadModelInPlugins(): void
    {
        App::build([
            'Plugin' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Plugin' . DS],
            'Controller' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Controller' . DS],
            'Model' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Model' . DS],
        ]);
        CakePlugin::load('TestLegacyPlugin');

        $Controller = new TestLegacyPluginController();
        $Controller->plugin = 'TestLegacyPlugin';
        $Controller->uses = false;

        $this->assertFalse(isset($Controller->Comment));

        $result = $Controller->loadModel('Comment');
        $this->assertTrue($result);
        $this->assertInstanceOf('Comment', $Controller->Comment);
        $this->assertTrue(in_array('Comment', $Controller->uses));

        ClassRegistry::flush();
        unset($Controller);
    }

    /**
     * testConstructClasses method
     *
     * @return void
     */
    public function testConstructClasses(): void
    {
        $request = new CakeRequest('controller_posts/index');

        $Controller = new Controller($request);
        $Controller->uses = ['ControllerPost', 'ControllerComment'];
        $Controller->constructClasses();
        $this->assertInstanceOf('ControllerPost', $Controller->ControllerPost);
        $this->assertInstanceOf('ControllerComment', $Controller->ControllerComment);

        $this->assertEquals('Comment', $Controller->ControllerComment->name);

        unset($Controller);

        App::build(['Plugin' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Plugin' . DS]]);
        CakePlugin::load('TestLegacyPlugin');

        $Controller = new Controller($request);
        $Controller->uses = ['TestLegacyPlugin.TestPluginPost'];
        $Controller->constructClasses();

        $this->assertTrue(isset($Controller->TestPluginPost));
        $this->assertInstanceOf('TestPluginPost', $Controller->TestPluginPost);
    }

    /**
     * testConstructClassesWithComponents method
     *
     * @return void
     */
    public function testConstructClassesWithComponents(): void
    {
        App::build(['Plugin' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Plugin' . DS]]);
        CakePlugin::load('TestLegacyPlugin');

        $Controller = new TestLegacyPluginController(new CakeRequest(), new CakeResponse());
        $Controller->uses = ['NameTest'];
        $Controller->components[] = 'Test2';

        $Controller->constructClasses();
        $this->assertEquals('NameTest', $Controller->Test2->model);
        $this->assertEquals('Name', $Controller->NameTest->name);
        $this->assertEquals('Name', $Controller->NameTest->alias);
    }

    /**
     * testFlash method
     *
     * @return void
     */
    public function testFlash(): void
    {
        $request = new CakeRequest('controller_posts/index');
        $request->webroot = '/';
        $request->base = '/';

        // Render the core flash layout, not the one of the application.
        App::build([
            'View' => [CORE_ROOT . DS . 'templates' . DS],
        ], App::RESET);
        $Controller = new Controller($request, $this->getMock('CakeResponse', ['_sendHeader']));
        $Controller->flash('this should work', '/flash');
        $result = $Controller->response->body();

        $expected = '<!DOCTYPE html>
		<html>
		<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<title>this should work</title>
		<style><!--
		P { text-align:center; font:bold 1.1em sans-serif }
		A { color:#444; text-decoration:none }
		A:HOVER { text-decoration: underline; color:#44E }
		--></style>
		</head>
		<body>
		<p><a href="/flash">this should work</a></p>
		</body>
		</html>';
        $result = str_replace(["\t", "\r\n", "\n"], '', $result);
        $expected = str_replace(["\t", "\r\n", "\n"], '', $expected);
        $this->assertEquals($expected, $result);

        App::build([
            'View' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'View' . DS],
        ]);
        $Controller = new Controller($request);
        $Controller->response = $this->getMock('CakeResponse', ['_sendHeader']);
        $Controller->flash('this should work', '/flash', 1, 'ajax2');
        $result = $Controller->response->body();
        $this->assertMatchesRegularExpression('/Ajax!/', $result);
        App::build();
    }

    /**
     * testRender method
     *
     * @return void
     */
    public function testRender(): void
    {
        App::build([
            'View' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'View' . DS],
        ], App::RESET);
        ClassRegistry::flush();
        $request = new CakeRequest('controller_posts/index');
        $request->params['action'] = 'index';

        $Controller = new Controller($request, new CakeResponse());
        $Controller->viewPath = 'Posts';

        $result = $Controller->render('index');
        $this->assertMatchesRegularExpression('/posts index/', (string)$result);

        $Controller->view = 'index';
        $result = $Controller->render();
        $this->assertMatchesRegularExpression('/posts index/', (string)$result);

        $result = $Controller->render('/Elements/test_element');
        $this->assertMatchesRegularExpression('/this is the test element/', (string)$result);
        $Controller->view = null;

        $Controller = new TestController($request, new CakeResponse());
        $Controller->uses = ['ControllerAlias', 'TestLegacyPlugin.ControllerComment', 'ControllerPost'];
        $Controller->helpers = ['Html'];
        $Controller->constructClasses();
        $Controller->ControllerComment->validationErrors = ['title' => 'tooShort'];
        $expected = $Controller->ControllerComment->validationErrors;

        $Controller->viewPath = 'Posts';
        $Controller->render('index');
        $View = $Controller->View;
        $this->assertTrue(isset($View->validationErrors['ControllerComment']));
        $this->assertEquals($expected, $View->validationErrors['ControllerComment']);

        $expectedModels = [
            'ControllerAlias' => ['plugin' => null, 'className' => 'ControllerAlias'],
            'ControllerComment' => ['plugin' => 'TestLegacyPlugin', 'className' => 'ControllerComment'],
            'ControllerPost' => ['plugin' => null, 'className' => 'ControllerPost'],
        ];
        $this->assertEquals($expectedModels, $Controller->request->params['models']);

        ClassRegistry::flush();
        App::build();
    }

    /**
     * test that a component beforeRender can change the controller view class.
     *
     * @return void
     */
    public function testComponentBeforeRenderChangingViewClass(): void
    {
        App::build([
            'View' => [
                CORE_TESTS . DS . 'test_legacy_app' . DS . 'View' . DS,
            ],
        ], true);
        $Controller = new Controller($this->getMock('CakeRequest'), new CakeResponse());
        $Controller->uses = [];
        $Controller->components = ['Test'];
        $Controller->constructClasses();
        $Controller->Test->viewclass = 'Theme';
        $Controller->viewPath = 'Posts';
        $Controller->theme = 'TestTheme';
        $result = $Controller->render('index');
        $this->assertMatchesRegularExpression('/default test_theme layout/', (string)$result);
        App::build();
    }

    /**
     * testMergeVars method
     *
     * @return void
     */
    public function testMergeVars(): void
    {
        $request = new CakeRequest('controller_posts/index');

        $TestController = new TestController($request);
        $TestController->constructClasses();

        $testVars = get_class_vars(TestController::class);
        $appVars = get_class_vars(ControllerTestAppController::class);

        $components = is_array($appVars['components'])
                        ? array_merge($appVars['components'], $testVars['components'])
                        : $testVars['components'];
        if (!in_array('Session', $components)) {
            $components[] = 'Session';
        }
        $helpers = is_array($appVars['helpers'])
                    ? array_merge($appVars['helpers'], $testVars['helpers'])
                    : $testVars['helpers'];
        $uses = is_array($appVars['uses'])
                    ? array_merge($appVars['uses'], $testVars['uses'])
                    : $testVars['uses'];

        $this->assertEquals(0, count(array_diff_key($TestController->helpers, array_flip($helpers))));
        $this->assertEquals(0, count(array_diff($TestController->uses, $uses)));
        $this->assertEquals(count(array_diff_assoc(Hash::normalize($TestController->components), Hash::normalize($components))), 0);

        $expected = ['ControllerComment', 'ControllerAlias', 'ControllerPost'];
        $this->assertEquals($expected, $TestController->uses, '$uses was merged incorrectly, ControllerTestAppController models should be last.');

        $TestController = new AnotherTestController($request);
        $TestController->constructClasses();

        $appVars = get_class_vars(ControllerTestAppController::class);
        $testVars = get_class_vars(AnotherTestController::class);

        $this->assertTrue(in_array('ControllerPost', $appVars['uses']));
        $this->assertFalse($testVars['uses']);

        $this->assertFalse(property_exists($TestController, 'ControllerPost'));

        $TestController = new ControllerCommentsController($request);
        $TestController->constructClasses();

        $appVars = get_class_vars(ControllerTestAppController::class);
        $testVars = get_class_vars(ControllerCommentsController::class);

        $this->assertTrue(in_array('ControllerPost', $appVars['uses']));
        $this->assertEquals(['ControllerPost'], $testVars['uses']);

        $this->assertTrue(isset($TestController->ControllerPost));
        $this->assertTrue(isset($TestController->ControllerComment));
    }

    /**
     * test that the BC wrapper doesn't interfere with models and components.
     *
     * @return void
     */
    public function testPropertyCompatibilityAndModelsComponents(): void
    {
        $request = new CakeRequest('controller_posts/index');

        $Controller = new TestController($request);
        $Controller->constructClasses();
        $this->assertInstanceOf('SecurityComponent', $Controller->Security);
        $this->assertInstanceOf('ControllerComment', $Controller->ControllerComment);
    }

    /**
     * test that using Controller::paginate() falls back to PaginatorComponent
     *
     * @return void
     */
    public function testPaginateBackwardsCompatibility(): void
    {
        $request = new CakeRequest('controller_posts/index');
        $request->params['pass'] = $request->params['named'] = [];
        $response = $this->getMock('CakeResponse', ['httpCodes']);

        $Controller = new Controller($request, $response);
        $Controller->uses = ['ControllerPost', 'ControllerComment'];
        $Controller->passedArgs[] = '1';
        $Controller->params['url'] = [];
        $Controller->params['named'] = [
            'posts' => [
                'page' => 2,
                'limit' => 2,
            ],
        ];
        $Controller->constructClasses();
        $expected = ['page' => 1, 'limit' => 20, 'maxLimit' => 100, 'paramType' => 'named', 'queryScope' => null];
        $this->assertEquals($expected, $Controller->paginate);
        $results = Hash::extract($Controller->paginate('ControllerPost'), '{n}.ControllerPost.id');
        $this->assertEquals([1, 2, 3], $results);

        $Controller->passedArgs = [];
        $Controller->paginate = ['limit' => '1'];
        $this->assertEquals(['limit' => '1'], $Controller->paginate);
        $Controller->paginate('ControllerPost');
        $this->assertSame($Controller->params['paging']['ControllerPost']['page'], 1);
        $this->assertSame($Controller->params['paging']['ControllerPost']['pageCount'], 3);
        $this->assertFalse($Controller->params['paging']['ControllerPost']['prevPage']);
        $this->assertTrue($Controller->params['paging']['ControllerPost']['nextPage']);
        $this->assertNull($Controller->params['paging']['ControllerPost']['queryScope']);

        $Controller->paginate = ['queryScope' => 'posts'];
        $Controller->paginate('ControllerPost');
        $this->assertSame($Controller->params['paging']['ControllerPost']['page'], 2);
        $this->assertSame($Controller->params['paging']['ControllerPost']['pageCount'], 2);
        $this->assertSame($Controller->params['paging']['ControllerPost']['queryScope'], 'posts');
    }
}
