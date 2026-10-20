<?php
/**
 * DispatcherTest file
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
 * @package       Cake.Test.Case.Routing
 * @since         CakePHP(tm) v 1.2.0.4206
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Test\TestCase\Legacy\Routing;

use Cake\Controller\Controller;
use Cake\Core\App;
use Cake\Core\CakePlugin;
use Cake\Core\Configure;
use Cake\Error\MissingControllerException;
use Cake\Error\MissingDispatcherFilterException;
use Cake\Event\CakeEvent;
use Cake\Network\CakeRequest;
use Cake\Network\CakeResponse;
use Cake\Routing\Dispatcher;
use Cake\Routing\Router;
use Cake\TestSuite\CakeTestCase;
use Cake\Utility\Inflector;

/**
 * TestDispatcher class
 *
 * @package       Cake.Test.Case.Routing
 */
class TestDispatcher extends Dispatcher
{
    /**
     * Controller instance, made publicly available for testing
     *
     * @var Controller
     */
    public $controller;

    /**
     * invoke method
     *
     * @param Controller $controller
     * @param CakeRequest $request
     * @return CakeResponse
     */
    protected function _invoke(Controller $controller, CakeRequest $request): mixed
    {
        $this->controller = $controller;

        return parent::_invoke($controller, $request);
    }

    /**
     * Helper function to test single method attaching for dispatcher filters
     *
     * @param CakeEvent $event
     * @return void
     */
    public function filterTest($event): void
    {
        $event->data['request']->params['eventName'] = $event->name();
    }

    /**
     * Helper function to test single method attaching for dispatcher filters
     *
     * @param CakeEvent
     * @return void
     */
    public function filterTest2($event): mixed
    {
        $event->stopPropagation();

        return $event->data['response'];
    }
}

/**
 * DispatcherTest class
 *
 * @package       Cake.Test.Case.Routing
 */
class DispatcherTest extends CakeTestCase
{
    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->_get = $_GET;
        $_GET = [];
        $this->_post = $_POST;
        $this->_files = $_FILES;
        $this->_server = $_SERVER;

        $this->_app = Configure::read('App');
        Configure::write('App.base', false);
        Configure::write('App.baseUrl', false);
        Configure::write('App.dir', 'app');
        Configure::write('App.webroot', 'webroot');

        $this->_cache = Configure::read('Cache');
        Configure::write('Cache.disable', true);

        $this->_debug = Configure::read('debug');

        App::build();
        App::build([
            'Controller' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Controller' . DS],
            'Lib' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Lib' . DS],
            'Plugin' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Plugin' . DS],
        ]);
        App::objects('plugin', null, false);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        $_GET = $this->_get;
        $_POST = $this->_post;
        $_FILES = $this->_files;
        $_SERVER = $this->_server;
        App::build();
        CakePlugin::unload();
        Configure::write('App', $this->_app);
        Configure::write('Cache', $this->_cache);
        Configure::write('debug', $this->_debug);
        Configure::write('Dispatcher.filters', []);

        parent::tearDown();
    }

    /**
     * testMissingController method
     *
     * @return void
     */
    public function testMissingController(): void
    {
        $this->expectException(MissingControllerException::class);
        $this->expectExceptionMessage('Controller class SomeControllerController could not be found.');
        Router::connect('/:controller/:action/*');

        $Dispatcher = new TestDispatcher();
        Configure::write('App.baseUrl', '/index.php');
        $url = new CakeRequest('some_controller/home/param:value/param2:value2');
        $response = $this->getMock('CakeResponse');

        $Dispatcher->dispatch($url, $response, ['return' => 1]);
    }

    /**
     * Test that a controller name with a dot is not split into a plugin name.
     *
     * @return void
     */
    public function testMissingControllerWithDot(): void
    {
        $this->expectException(MissingControllerException::class);
        $this->expectExceptionMessage('Controller class TestLegacyPlugin.testsController could not be found.');
        Router::connect('/:controller/:action/*');
        App::build(['Plugin' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Plugin' . DS]], App::RESET);
        CakePlugin::load('TestLegacyPlugin');

        $Dispatcher = new TestDispatcher();
        $url = new CakeRequest('test_legacy_plugin.tests/index');
        $response = $this->getMock('CakeResponse');

        $Dispatcher->dispatch($url, $response, ['return' => 1]);
    }

    /**
     * testMissingControllerInterface method
     *
     * @return void
     */
    public function testMissingControllerInterface(): void
    {
        $this->expectException(MissingControllerException::class);
        $this->expectExceptionMessage('Controller class DispatcherTestInterfaceController could not be found.');
        Router::connect('/:controller/:action/*');

        $Dispatcher = new TestDispatcher();
        Configure::write('App.baseUrl', '/index.php');
        $url = new CakeRequest('dispatcher_test_interface/index');
        $response = $this->getMock('CakeResponse');

        $Dispatcher->dispatch($url, $response, ['return' => 1]);
    }

    /**
     * testMissingControllerInterface method
     *
     * @return void
     */
    public function testMissingControllerAbstract(): void
    {
        $this->expectException(MissingControllerException::class);
        $this->expectExceptionMessage('Controller class DispatcherTestAbstractController could not be found.');
        Router::connect('/:controller/:action/*');

        $Dispatcher = new TestDispatcher();
        Configure::write('App.baseUrl', '/index.php');
        $url = new CakeRequest('dispatcher_test_abstract/index');
        $response = $this->getMock('CakeResponse');

        $Dispatcher->dispatch($url, $response, ['return' => 1]);
    }

    /**
     * testDispatch method
     *
     * @return void
     */
    public function testDispatchBasic(): void
    {
        App::build([
            'View' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'View' . DS],
        ]);
        $Dispatcher = new TestDispatcher();
        Configure::write('App.baseUrl', '/index.php');
        $url = new CakeRequest('pages/home/param:value/param2:value2');
        $response = $this->getMock('CakeResponse');

        $Dispatcher->dispatch($url, $response, ['return' => 1]);
        $expected = 'Pages';
        $this->assertEquals($expected, $Dispatcher->controller->name);

        $expected = ['0' => 'home', 'param' => 'value', 'param2' => 'value2'];
        $this->assertSame($expected, $Dispatcher->controller->passedArgs);

        Configure::write('App.baseUrl', '/pages/index.php');

        $url = new CakeRequest('pages/home');
        $Dispatcher->dispatch($url, $response, ['return' => 1]);

        $expected = 'Pages';
        $this->assertEquals($expected, $Dispatcher->controller->name);

        $url = new CakeRequest('pages/home/');
        $Dispatcher->dispatch($url, $response, ['return' => 1]);
        $this->assertNull($Dispatcher->controller->plugin);

        $expected = 'Pages';
        $this->assertEquals($expected, $Dispatcher->controller->name);

        unset($Dispatcher);

        require CORE_ROOT . DS . 'config' . DS . 'routes.php';
        $Dispatcher = new TestDispatcher();
        Configure::write('App.baseUrl', '/timesheets/index.php');

        $url = new CakeRequest('timesheets');
        $Dispatcher->dispatch($url, $response, ['return' => 1]);

        $expected = 'Timesheets';
        $this->assertEquals($expected, $Dispatcher->controller->name);

        $url = new CakeRequest('timesheets/');
        $Dispatcher->dispatch($url, $response, ['return' => 1]);

        $this->assertEquals('Timesheets', $Dispatcher->controller->name);
        $this->assertEquals('/timesheets/index.php', $url->base);

        $url = new CakeRequest('test_dispatch_pages/camelCased');
        $Dispatcher->dispatch($url, $response, ['return' => 1]);
        $this->assertEquals('TestDispatchPages', $Dispatcher->controller->name);

        $url = new CakeRequest('test_dispatch_pages/camelCased/something. .');
        $Dispatcher->dispatch($url, $response, ['return' => 1]);
        $this->assertEquals('something. .', $Dispatcher->controller->params['pass'][0], 'Period was chopped off. %s');
    }

    /**
     * testPluginDispatch method
     *
     * @return void
     * @triggers DispatcherTest $Dispatcher, array('request' => $url)
     */
    public function testPluginDispatch(): void
    {
        $_POST = [];
        CakePlugin::load('TestDispatchLegacyPlugin');

        Router::reload();
        $Dispatcher = new TestDispatcher();
        Router::connect(
            '/test_dispatch_legacy_plugin/:controller/*',
            ['plugin' => 'test_dispatch_legacy_plugin', 'controller' => 'pages', 'action' => 'display'],
        );

        $url = new CakeRequest('test_dispatch_legacy_plugin/some_pages/home/param:value/param2:value2');
        $response = $this->getMock('CakeResponse');
        $Dispatcher->dispatch($url, $response, ['return' => 1]);

        $event = new CakeEvent('DispatcherTest', $Dispatcher, ['request' => $url]);
        $Dispatcher->parseParams($event);
        $expected = [
            'pass' => ['home'],
            'named' => ['param' => 'value', 'param2' => 'value2'], 'plugin' => 'test_dispatch_legacy_plugin',
            'controller' => 'some_pages', 'action' => 'display',
        ];
        foreach ($expected as $key => $value) {
            $this->assertEquals($value, $url[$key], 'Value mismatch ' . $key . ' %');
        }

        $this->assertSame($Dispatcher->controller->plugin, 'TestDispatchLegacyPlugin');
        $this->assertSame($Dispatcher->controller->name, 'SomePages');
        $this->assertSame($Dispatcher->controller->params['controller'], 'some_pages');
        $this->assertSame($Dispatcher->controller->passedArgs, ['0' => 'home', 'param' => 'value', 'param2' => 'value2']);
    }

    /**
     * testAutomaticPluginDispatch method
     *
     * @return void
     */
    public function testAutomaticPluginDispatch(): void
    {
        $_POST = [];
        $_SERVER['PHP_SELF'] = '/cake/repo/branches/1.2.x.x/index.php';
        CakePlugin::load('TestDispatchLegacyPlugin');

        Router::reload();
        $Dispatcher = new TestDispatcher();
        Router::connect(
            '/test_dispatch_legacy_plugin/:controller/:action/*',
            ['plugin' => 'test_dispatch_legacy_plugin', 'controller' => 'pages', 'action' => 'display'],
        );

        $Dispatcher->base = false;

        $url = new CakeRequest('test_dispatch_legacy_plugin/other_pages/index/param:value/param2:value2');
        $response = $this->getMock('CakeResponse');
        $Dispatcher->dispatch($url, $response, ['return' => 1]);

        $this->assertSame($Dispatcher->controller->plugin, 'TestDispatchLegacyPlugin');
        $this->assertSame($Dispatcher->controller->name, 'OtherPages');
        $this->assertSame($Dispatcher->controller->action, 'index');
        $this->assertSame($Dispatcher->controller->passedArgs, ['param' => 'value', 'param2' => 'value2']);

        $expected = '/cake/repo/branches/1.2.x.x/test_dispatch_legacy_plugin/other_pages/index/param:value/param2:value2';
        $this->assertSame($expected, $url->here);

        $expected = '/cake/repo/branches/1.2.x.x';
        $this->assertSame($expected, $url->base);
    }

    /**
     * testAutomaticPluginControllerDispatch method
     *
     * @return void
     */
    public function testAutomaticPluginControllerDispatch(): void
    {
        $plugins = App::objects('plugin');
        $plugins[] = 'TestDispatchLegacyPlugin';
        $plugins[] = 'TestArticlesLegacyPlugin';

        CakePlugin::load('TestDispatchLegacyPlugin');

        Router::reload();
        $Dispatcher = new TestDispatcher();
        $Dispatcher->base = false;

        $url = new CakeRequest('test_dispatch_legacy_plugin/test_dispatch_legacy_plugin/add/param:value/param2:value2');
        $response = $this->getMock('CakeResponse');

        $Dispatcher->dispatch($url, $response, ['return' => 1]);

        $this->assertSame($Dispatcher->controller->plugin, 'TestDispatchLegacyPlugin');
        $this->assertSame($Dispatcher->controller->name, 'TestDispatchLegacyPlugin');
        $this->assertSame($Dispatcher->controller->action, 'add');
        $this->assertEquals(['param' => 'value', 'param2' => 'value2'], $Dispatcher->controller->params['named']);

        Router::reload();
        require CORE_ROOT . DS . 'config' . DS . 'routes.php';
        $Dispatcher = new TestDispatcher();
        $Dispatcher->base = false;

        // Simulates the Route for a real plugin, installed in APP/plugins
        Router::connect('/test_dispatch_legacy_plugin/:controller/:action/*', ['plugin' => 'test_dispatch_legacy_plugin']);

        $plugin = 'TestDispatchLegacyPlugin';
        $pluginUrl = Inflector::underscore($plugin);

        $url = new CakeRequest($pluginUrl);
        $Dispatcher->dispatch($url, $response, ['return' => 1]);
        $this->assertSame($Dispatcher->controller->plugin, 'TestDispatchLegacyPlugin');
        $this->assertSame($Dispatcher->controller->name, 'TestDispatchLegacyPlugin');
        $this->assertSame($Dispatcher->controller->action, 'index');

        $expected = $pluginUrl;
        $this->assertEquals($expected, $Dispatcher->controller->params['controller']);

        Configure::write('Routing.prefixes', ['admin']);

        Router::reload();
        require CORE_ROOT . DS . 'config' . DS . 'routes.php';
        $Dispatcher = new TestDispatcher();

        $url = new CakeRequest('admin/test_dispatch_legacy_plugin/test_dispatch_legacy_plugin/add/5/param:value/param2:value2');
        $response = $this->getMock('CakeResponse');

        $Dispatcher->dispatch($url, $response, ['return' => 1]);

        $this->assertEquals('test_dispatch_legacy_plugin', $Dispatcher->controller->params['plugin']);
        $this->assertEquals('test_dispatch_legacy_plugin', $Dispatcher->controller->params['controller']);
        $this->assertEquals('admin_add', $Dispatcher->controller->params['action']);
        $this->assertEquals([5], $Dispatcher->controller->params['pass']);
        $this->assertEquals(['param' => 'value', 'param2' => 'value2'], $Dispatcher->controller->params['named']);
        $this->assertSame($Dispatcher->controller->plugin, 'TestDispatchLegacyPlugin');
        $this->assertSame($Dispatcher->controller->name, 'TestDispatchLegacyPlugin');
        $this->assertSame($Dispatcher->controller->action, 'admin_add');

        $expected = [0 => 5, 'param' => 'value', 'param2' => 'value2'];
        $this->assertEquals($expected, $Dispatcher->controller->passedArgs);

        Configure::write('Routing.prefixes', ['admin']);
        CakePlugin::load('TestArticlesLegacyPlugin');
        Router::reload();
        require CORE_ROOT . DS . 'config' . DS . 'routes.php';

        $Dispatcher = new TestDispatcher();

        $Dispatcher->dispatch(new CakeRequest('admin/test_articles_legacy_plugin'), $response, ['return' => 1]);
        $this->assertSame($Dispatcher->controller->plugin, 'TestArticlesLegacyPlugin');
        $this->assertSame($Dispatcher->controller->name, 'TestArticlesLegacyPlugin');
        $this->assertSame($Dispatcher->controller->action, 'admin_index');

        $expected = [
            'pass' => [],
            'named' => [],
            'controller' => 'test_articles_legacy_plugin',
            'plugin' => 'test_articles_legacy_plugin',
            'action' => 'admin_index',
            'prefix' => 'admin',
            'admin' => true,
            'return' => 1,
        ];
        foreach ($expected as $key => $value) {
            $this->assertEquals($expected[$key], $Dispatcher->controller->request[$key], 'Value mismatch ' . $key);
        }
    }

    /**
     * test Plugin dispatching without controller name and using
     * plugin short form instead.
     *
     * @return void
     */
    public function testAutomaticPluginDispatchWithShortAccess(): void
    {
        CakePlugin::load('TestDispatchLegacyPlugin');
        Router::reload();

        $Dispatcher = new TestDispatcher();
        $Dispatcher->base = false;

        $url = new CakeRequest('test_dispatch_legacy_plugin/');
        $response = $this->getMock('CakeResponse');

        $Dispatcher->dispatch($url, $response, ['return' => 1]);
        $this->assertEquals('test_dispatch_legacy_plugin', $Dispatcher->controller->params['controller']);
        $this->assertEquals('test_dispatch_legacy_plugin', $Dispatcher->controller->params['plugin']);
        $this->assertEquals('index', $Dispatcher->controller->params['action']);
        $this->assertFalse(isset($Dispatcher->controller->params['pass'][0]));
    }

    /**
     * test plugin shortcut URLs with controllers that need to be loaded,
     * the above test uses a controller that has already been included.
     *
     * @return void
     */
    public function testPluginShortCutUrlsWithControllerThatNeedsToBeLoaded(): void
    {
        $loaded = class_exists('TestLegacyPluginController', false);
        $this->skipIf($loaded, 'TestLegacyPluginController already loaded.');

        Router::reload();
        App::build([
            'Plugin' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Plugin' . DS],
        ], App::RESET);
        CakePlugin::load(['TestLegacyPlugin', 'TestTwoLegacyPlugin']);

        $Dispatcher = new TestDispatcher();
        $Dispatcher->base = false;

        $url = new CakeRequest('test_legacy_plugin/');
        $response = $this->getMock('CakeResponse');

        $Dispatcher->dispatch($url, $response, ['return' => 1]);
        $this->assertEquals('test_legacy_plugin', $Dispatcher->controller->params['controller']);
        $this->assertEquals('test_legacy_plugin', $Dispatcher->controller->params['plugin']);
        $this->assertEquals('index', $Dispatcher->controller->params['action']);
        $this->assertFalse(isset($Dispatcher->controller->params['pass'][0]));

        $url = new CakeRequest('/test_legacy_plugin/tests/index');
        $Dispatcher->dispatch($url, $response, ['return' => 1]);
        $this->assertEquals('tests', $Dispatcher->controller->params['controller']);
        $this->assertEquals('test_legacy_plugin', $Dispatcher->controller->params['plugin']);
        $this->assertEquals('index', $Dispatcher->controller->params['action']);
        $this->assertFalse(isset($Dispatcher->controller->params['pass'][0]));

        $url = new CakeRequest('/test_legacy_plugin/tests/index/some_param');
        $Dispatcher->dispatch($url, $response, ['return' => 1]);
        $this->assertEquals('tests', $Dispatcher->controller->params['controller']);
        $this->assertEquals('test_legacy_plugin', $Dispatcher->controller->params['plugin']);
        $this->assertEquals('index', $Dispatcher->controller->params['action']);
        $this->assertEquals('some_param', $Dispatcher->controller->params['pass'][0]);

        App::build();
    }

    /**
     * Test dispatching into the TestLegacyPlugin in the test_legacy_app
     *
     * @return void
     */
    public function testTestPluginDispatch(): void
    {
        $Dispatcher = new TestDispatcher();
        App::build([
            'Plugin' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Plugin' . DS],
        ], App::RESET);
        CakePlugin::load(['TestLegacyPlugin', 'TestTwoLegacyPlugin']);
        Router::reload();
        Router::parse('/');

        $url = new CakeRequest('/test_legacy_plugin/tests/index');
        $response = $this->getMock('CakeResponse');
        $Dispatcher->dispatch($url, $response, ['return' => 1]);
        $this->assertTrue(class_exists('TestsController'));
        $this->assertTrue(class_exists('TestLegacyPluginAppController'));
        $this->assertTrue(class_exists('PluginsComponent'));

        $this->assertEquals('tests', $Dispatcher->controller->params['controller']);
        $this->assertEquals('test_legacy_plugin', $Dispatcher->controller->params['plugin']);
        $this->assertEquals('index', $Dispatcher->controller->params['action']);

        App::build();
    }

    /**
     * Tests that it is possible to attach filter classes to the dispatch cycle
     *
     * @return void
     */
    public function testDispatcherFilterSubscriber(): void
    {
        App::build([
            'View' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'View' . DS],
            'Plugin' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Plugin' . DS],
        ], App::RESET);

        CakePlugin::load('TestLegacyPlugin');
        Configure::write('Dispatcher.filters', [
            ['callable' => 'TestLegacyPlugin.TestDispatcherFilter'],
        ]);
        $dispatcher = new TestDispatcher();
        $request = new CakeRequest('/');
        $request->params['altered'] = false;
        $response = $this->getMock('CakeResponse', ['send']);

        $dispatcher->dispatch($request, $response);
        $this->assertTrue($request->params['altered']);
        $this->assertEquals(304, $response->statusCode());

        Configure::write('Dispatcher.filters', [
            'TestLegacyPlugin.Test2DispatcherFilter',
            'TestLegacyPlugin.TestDispatcherFilter',
        ]);
        $dispatcher = new TestDispatcher();
        $request = new CakeRequest('/');
        $request->params['altered'] = false;
        $response = $this->getMock('CakeResponse', ['send']);

        $dispatcher->dispatch($request, $response);
        $this->assertFalse($request->params['altered']);
        $this->assertEquals(500, $response->statusCode());
        $this->assertNull($dispatcher->controller);
    }

    /**
     * Tests that it is possible to attach filter with config classes to the dispatch cycle
     *
     * @return void
     */
    public function testDispatcherFilterSettings(): void
    {
        Configure::write('Dispatcher.filters', [
            'TestFilterDispatcher' => ['service' => 'google.com'],
        ]);
        $Dispatcher = new Dispatcher();
        $url = new CakeRequest('some_pages/index');
        $response = $this->getMock('CakeResponse');
        $Dispatcher->dispatch($url, $response, ['return' => 1]);
        $settings = $url->param('settings');
        $this->assertEquals($settings, ['service' => 'google.com']);
    }

    /**
     * Tests that attaching an inexistent class as filter will throw an exception
     *
     * @return void
     */
    public function testDispatcherFilterSuscriberMissing(): void
    {
        $this->expectException(MissingDispatcherFilterException::class);
        App::build([
            'Plugin' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Plugin' . DS],
        ], App::RESET);

        CakePlugin::load('TestLegacyPlugin');
        Configure::write('Dispatcher.filters', [
            ['callable' => 'TestLegacyPlugin.NotAFilter'],
        ]);
        $dispatcher = new TestDispatcher();
        $request = new CakeRequest('/');
        $response = $this->getMock('CakeResponse', ['send']);
        $dispatcher->dispatch($request, $response);
    }

    /**
     * testStaticAssets method
     *
     * @return void
     */
    public function testAssets(): void
    {
        Router::reload();

        App::build([
            'Plugin' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Plugin' . DS],
            'Vendor' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Vendor' . DS],
            'View' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'View' . DS],
        ]);
        CakePlugin::load(['TestLegacyPlugin', 'TestTwoLegacyPlugin']);
        Configure::write('Dispatcher.filters', ['AssetDispatcher']);

        $Dispatcher = new TestDispatcher();
        $response = $this->getMock('CakeResponse', ['_sendHeader']);

        try {
            $Dispatcher->dispatch(new CakeRequest('theme/test_theme/../webroot/css/test_asset.css'), $response);
            $this->fail('No exception');
        } catch (MissingControllerException $e) {
            $this->assertEquals('Controller class ThemeController could not be found.', $e->getMessage());
        }

        try {
            $Dispatcher->dispatch(new CakeRequest('theme/test_theme/pdfs'), $response);
            $this->fail('No exception');
        } catch (MissingControllerException $e) {
            $this->assertEquals('Controller class ThemeController could not be found.', $e->getMessage());
        }
    }

    /**
     * Data provider for asset filter
     *
     * - theme assets.
     * - plugin assets.
     * - plugin assets in sub directories.
     * - unknown plugin assets.
     *
     * @return array
     */
    public static function assetProvider(): mixed
    {
        return [
            [
                'theme/test_theme/flash/theme_test.swf',
                'View/Themed/TestTheme/webroot/flash/theme_test.swf',
            ],
            [
                'theme/test_theme/pdfs/theme_test.pdf',
                'View/Themed/TestTheme/webroot/pdfs/theme_test.pdf',
            ],
            [
                'theme/test_theme/img/test.jpg',
                'View/Themed/TestTheme/webroot/img/test.jpg',
            ],
            [
                'theme/test_theme/css/test_asset.css',
                'View/Themed/TestTheme/webroot/css/test_asset.css',
            ],
            [
                'theme/test_theme/js/theme.js',
                'View/Themed/TestTheme/webroot/js/theme.js',
            ],
            [
                'theme/test_theme/js/one/theme_one.js',
                'View/Themed/TestTheme/webroot/js/one/theme_one.js',
            ],
            [
                'theme/test_theme/space%20image.text',
                'View/Themed/TestTheme/webroot/space image.text',
            ],
            [
                'test_legacy_plugin/root.js',
                'Plugin/TestLegacyPlugin/webroot/root.js',
            ],
            [
                'test_legacy_plugin/flash/plugin_test.swf',
                'Plugin/TestLegacyPlugin/webroot/flash/plugin_test.swf',
            ],
            [
                'test_legacy_plugin/pdfs/plugin_test.pdf',
                'Plugin/TestLegacyPlugin/webroot/pdfs/plugin_test.pdf',
            ],
            [
                'test_legacy_plugin/js/test_legacy_plugin/test.js',
                'Plugin/TestLegacyPlugin/webroot/js/test_legacy_plugin/test.js',
            ],
            [
                'test_legacy_plugin/css/test_plugin_asset.css',
                'Plugin/TestLegacyPlugin/webroot/css/test_plugin_asset.css',
            ],
            [
                'test_legacy_plugin/img/cake.icon.gif',
                'Plugin/TestLegacyPlugin/webroot/img/cake.icon.gif',
            ],
            [
                'test_js_legacy_plugin/js/test_js_legacy_plugin.js',
                'Plugin/TestJsLegacyPlugin/webroot/js/test_js_legacy_plugin.js',
            ],
            [
                'test_js_legacy_plugin/js/one/plugin_one.js',
                'Plugin/TestJsLegacyPlugin/webroot/js/one/plugin_one.js',
            ],
            [
                'test_legacy_plugin/css/unknown.extension',
                'Plugin/TestLegacyPlugin/webroot/css/unknown.extension',
            ],
            [
                'test_legacy_plugin/css/theme_one.htc',
                'Plugin/TestLegacyPlugin/webroot/css/theme_one.htc',
            ],
        ];
    }

    /**
     * Test assets
     *
     * @dataProvider assetProvider
     * @outputBuffering enabled
     * @return void
     */
    public function testAsset($url, $file): void
    {
        Router::reload();

        App::build([
            'Plugin' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Plugin' . DS],
            'Vendor' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Vendor' . DS],
            'View' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'View' . DS],
        ]);
        CakePlugin::load(['TestLegacyPlugin', 'TestJsLegacyPlugin']);
        Configure::write('Dispatcher.filters', ['AssetDispatcher']);

        $Dispatcher = new TestDispatcher();
        $response = $this->getMock('CakeResponse', ['_sendHeader']);

        $Dispatcher->dispatch(new CakeRequest($url), $response);
        $result = ob_get_clean();

        $path = CORE_TESTS . DS . 'test_legacy_app' . DS . str_replace('/', DS, $file);
        $file = file_get_contents($path);
        $this->assertEquals($file, $result);

        $expected = filesize($path);
        $headers = $response->header();
        $this->assertEquals($expected, $headers['Content-Length']);
    }

    /**
     * Data provider for cached actions.
     *
     * - Test simple views
     * - Test views with nocache tags
     * - Test requests with named + passed params.
     * - Test requests with query string params
     * - Test themed views.
     *
     * @return array
     */
    public static function cacheActionProvider(): mixed
    {
        return [
            ['/'],
            ['test_cached_pages/index'],
            ['TestCachedPages/index'],
            ['test_cached_pages/test_nocache_tags'],
            ['TestCachedPages/test_nocache_tags'],
            ['test_cached_pages/view/param/param'],
            ['test_cached_pages/view/foo:bar/value:goo'],
            ['test_cached_pages/view?q=cakephp'],
            ['test_cached_pages/themed'],
        ];
    }

    /**
     * testFullPageCachingDispatch method
     *
     * @dataProvider cacheActionProvider
     * @return void
     */
    public function testFullPageCachingDispatch($url): void
    {
        Configure::write('Cache.disable', false);
        Configure::write('Cache.check', true);
        Configure::write('debug', 2);

        Router::reload();
        Router::connect('/', ['controller' => 'test_cached_pages', 'action' => 'index']);
        Router::connect('/:controller/:action/*');

        App::build([
            'View' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'View' . DS],
        ], App::RESET);

        $dispatcher = new TestDispatcher();
        $request = new CakeRequest($url);
        $response = $this->getMock('CakeResponse', ['send']);

        $dispatcher->dispatch($request, $response);
        $out = $response->body();

        Configure::write('Dispatcher.filters', ['CacheDispatcher']);
        $request = new CakeRequest($url);
        $response = $this->getMock('CakeResponse', ['send']);
        $dispatcher = new TestDispatcher();
        $dispatcher->dispatch($request, $response);
        $cached = $response->body();

        $cached = preg_replace('/<!--+[^<>]+-->/', '', $cached);

        $this->assertTextEquals($out, $cached);

        $filename = $this->_cachePath($request->here());
        unlink($filename);
    }

    /**
     * cachePath method
     *
     * @param string $here
     * @return string
     */
    protected function _cachePath($here): mixed
    {
        $path = $here;
        if ($here === '/') {
            $path = 'home';
        }
        $path = strtolower(Inflector::slug($path));

        $filename = CACHE . 'views' . DS . $path . '.php';

        if (!file_exists($filename)) {
            $filename = CACHE . 'views' . DS . $path . '_index.php';
        }

        return $filename;
    }
}
