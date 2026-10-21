<?php
/**
 * LegacyPluginTest file.
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @package       Cake.Test.Case.Core
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Test\TestCase\Core;

use Cake\Console\ShellDispatcher;
use Cake\Console\TaskCollection;
use Cake\Core\App;
use Cake\Core\CakeObject;
use Cake\Core\CakePlugin;
use Cake\Core\Configure;
use Cake\Event\CakeEvent;
use Cake\I18n\I18n;
use Cake\Network\CakeRequest;
use Cake\Network\CakeResponse;
use Cake\Routing\Dispatcher;
use Cake\Routing\Filter\AssetDispatcher;
use Cake\TestSuite\CakeTestCase;
use Cake\Utility\ClassRegistry;
use Cake\View\View;
use Closure;
use ExampleShell;
use OtherComponent;
use OtherTaskTask;
use PluggedHelperHelper;
use PluginsComponent;
use TestApp\Controller\AppController;
use TestApp\Model\AppModel;
use TestLegacyPluginAppController;
use TestLegacyPluginAppModel;
use TestPluginBlogPost;
use TestPluginPersisterOneBehavior;
use TestPluginPost;
use TestsController;

/**
 * Shell dispatcher that returns the shells without dispatching them
 *
 * @package       Cake.Test.Case.Core
 */
class LegacyPluginShellDispatcher extends ShellDispatcher
{
    /**
     * getShell
     *
     * @param string $shell
     * @return mixed
     */
    public function getShell($shell): mixed
    {
        return $this->_getShell($shell);
    }
}

/**
 * Tests a plugin with the CakePHP 2 layout and global classes in an application with the
 * CakePHP 5 layout and App.namespace
 *
 * @package       Cake.Test.Case.Core
 */
class LegacyPluginTest extends CakeTestCase
{
    /**
     * fixtures property
     *
     * @var array
     */
    public $fixtures = ['core.article', 'core.post'];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        Configure::write('App.namespace', 'TestApp');
        App::build([
            'Plugin' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Plugin' . DS],
            'Controller' => [CORE_TESTS . DS . 'test_app' . DS . 'src' . DS . 'Controller' . DS],
            'Model' => [CORE_TESTS . DS . 'test_app' . DS . 'src' . DS . 'Model' . DS],
            'View' => [CORE_TESTS . DS . 'test_app' . DS . 'templates' . DS],
        ], App::RESET);
        CakePlugin::load('TestLegacyPlugin', ['bootstrap' => true]);
        App::uses('TestLegacyPluginAppController', 'TestLegacyPlugin.Controller');
        App::uses('TestsController', 'TestLegacyPlugin.Controller');

        // App maps the files of plugin classes by class name only, and TestPlugin has a TestsController too
        Closure::bind(function () {
            static::$_map = [];
        }, null, App::class)();
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        CakePlugin::unload();
        I18n::clear();
        parent::tearDown();
    }

    /**
     * Test that the AppController of the plugin works on top of the AppController of the application
     *
     * It runs in its own process, as other tests declare the AppController of the skeleton application.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @return void
     */
    public function testAppControllerExtendsApplicationAppController(): void
    {
        $this->assertSame(AppController::class, get_parent_class(TestLegacyPluginAppController::class));

        $request = new CakeRequest('/test_legacy_plugin/tests/index', false);
        $request->addParams(['plugin' => 'test_legacy_plugin', 'controller' => 'tests', 'action' => 'index']);
        $Controller = new TestsController($request, new CakeResponse());
        $Controller->constructClasses();
        $this->assertInstanceOf(AppController::class, $Controller);
        $this->assertArrayHasKey('Namespaced', $Controller->helpers);
    }

    /**
     * Test that a model of the plugin that extends AppModel works on top of the AppModel of the application
     *
     * It runs in its own process, as other tests declare the AppModel of the skeleton application.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @return void
     */
    public function testModelExtendsApplicationAppModel(): void
    {
        $Post = ClassRegistry::init('TestLegacyPlugin.TestPluginBlogPost');
        $this->assertInstanceOf(TestPluginBlogPost::class, $Post);
        $this->assertInstanceOf(AppModel::class, $Post);
        $this->assertTrue($Post->Behaviors->loaded('Containable'));

        $this->assertTrue($Post->findMethods['published']);
    }

    /**
     * Test that ClassRegistry::init() returns the global models of the plugin
     *
     * @return void
     */
    public function testClassRegistryInit(): void
    {
        $Post = ClassRegistry::init('TestLegacyPlugin.TestPluginPost');
        $this->assertInstanceOf(TestPluginPost::class, $Post);
        $this->assertSame('TestPluginPost', $Post->alias);
        $this->assertSame('TestLegacyPlugin', $Post->plugin);
        $this->assertSame(3, $Post->find('count'));

        $Model = ClassRegistry::init('TestLegacyPlugin.TestPluginMissing');
        $this->assertInstanceOf(TestLegacyPluginAppModel::class, $Model);
        $this->assertSame('TestPluginMissing', $Model->name);
    }

    /**
     * Test that the global behaviors of the plugin are loaded by name
     *
     * @return void
     */
    public function testBehaviorsLoad(): void
    {
        $Post = ClassRegistry::init('TestLegacyPlugin.TestPluginPost');
        $Post->Behaviors->load('TestLegacyPlugin.TestPluginPersisterOne');
        $this->assertInstanceOf(TestPluginPersisterOneBehavior::class, $Post->Behaviors->TestPluginPersisterOne);
    }

    /**
     * Test that the global components of a plugin controller are found by name
     *
     * @return void
     */
    public function testControllerClasses(): void
    {
        $request = new CakeRequest('/test_legacy_plugin/tests/index', false);
        $request->addParams(['plugin' => 'test_legacy_plugin', 'controller' => 'tests', 'action' => 'index']);
        $Controller = new TestsController($request, new CakeResponse());
        $Controller->constructClasses();
        $this->assertSame('TestLegacyPlugin', $Controller->plugin);
        $this->assertInstanceOf(PluginsComponent::class, $Controller->Plugins);
        $this->assertInstanceOf(OtherComponent::class, $Controller->Plugins->Other);
    }

    /**
     * Test dispatching to a global controller of the plugin
     *
     * @return void
     */
    public function testDispatch(): void
    {
        $Dispatcher = new Dispatcher();
        $result = $Dispatcher->dispatch(new CakeRequest('/test_legacy_plugin/tests/some_method'), new CakeResponse(), ['return' => 1]);
        $this->assertSame(25, $result);
    }

    /**
     * Test requestAction() to a global controller of the plugin
     *
     * @return void
     */
    public function testRequestAction(): void
    {
        $Object = new CakeObject();

        $result = $Object->requestAction('/test_legacy_plugin/tests/some_method');
        $this->assertSame(25, $result);

        $result = $Object->requestAction(['plugin' => 'test_legacy_plugin', 'controller' => 'tests', 'action' => 'some_method']);
        $this->assertSame(25, $result);
    }

    /**
     * Test that a View renders the views, layouts and elements in the View directory of the plugin
     * and loads its global helpers by name
     *
     * @return void
     */
    public function testView(): void
    {
        $View = new View(null);
        $View->plugin = 'TestLegacyPlugin';
        $View->viewPath = 'Tests';
        $this->assertSame('test plugin index', trim($View->render('index', false)));

        $View = new View(null);
        $View->plugin = 'TestLegacyPlugin';
        $View->viewPath = 'Tests';
        $this->assertSame('test plugin default layout', trim($View->render('index')));

        $View = new View(null);
        $result = $View->element('TestLegacyPlugin.test_plugin_element');
        $this->assertSame('this is the test set using View::$plugin plugin element', $result);

        $this->assertInstanceOf(PluggedHelperHelper::class, $View->loadHelper('TestLegacyPlugin.PluggedHelper'));
    }

    /**
     * Test that the global shells and tasks of the plugin are found by name
     *
     * @return void
     */
    public function testShellAndTask(): void
    {
        $Dispatcher = new LegacyPluginShellDispatcher([], false);

        $Shell = $Dispatcher->getShell('test_legacy_plugin.example');
        $this->assertInstanceOf(ExampleShell::class, $Shell);
        $this->assertSame('TestLegacyPlugin', $Shell->plugin);
        $this->assertSame('Example', $Shell->name);

        $Tasks = new TaskCollection($Shell);
        $this->assertInstanceOf(OtherTaskTask::class, $Tasks->load('TestLegacyPlugin.OtherTask'));
    }

    /**
     * Test that __d() reads the translations in the Locale directory of the plugin
     *
     * @return void
     */
    public function testTranslation(): void
    {
        Configure::write('Config.language', 'po');
        $result = __d('test_legacy_plugin', 'Plural Rule 1');
        $this->assertSame('Plural Rule 1 (from plugin)', $result);
    }

    /**
     * Test that the files in the Config directory of the plugin are read
     *
     * @return void
     */
    public function testConfig(): void
    {
        $this->assertSame('loaded plugin bootstrap', Configure::read('CakePluginTest.test_legacy_plugin.bootstrap'));

        Configure::load('TestLegacyPlugin.load');
        $this->assertSame('/test_app/plugins/test_legacy_plugin/config/load.php', Configure::read('plugin_load'));
    }

    /**
     * Test that AssetDispatcher serves the webroot assets of the plugin
     *
     * @return void
     * @triggers DispatcherTest $this, compact('request', 'response')
     */
    public function testAssetDispatcher(): void
    {
        $filter = new AssetDispatcher();
        $response = $this->getMock(CakeResponse::class, ['_sendHeader', 'send']);
        $request = new CakeRequest('test_legacy_plugin/css/test_plugin_asset.css');
        $event = new CakeEvent('DispatcherTest', $this, compact('request', 'response'));

        $this->assertSame($response, $filter->beforeDispatch($event));
        $result = ob_get_clean();
        $this->assertTrue($event->isStopped());

        $file = CORE_TESTS . DS . 'test_legacy_app' . DS . 'Plugin' . DS . 'TestLegacyPlugin' . DS . 'webroot' . DS . 'css' . DS . 'test_plugin_asset.css';
        $this->assertSame(file_get_contents($file), $result);
    }
}
