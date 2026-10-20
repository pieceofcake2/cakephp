<?php
/**
 * NamespacedPluginTest file.
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

namespace Cake\Test\TestCase\Legacy\Core;

use AppController;
use AppModel;
use Cake\Console\ShellDispatcher;
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
use Cake\Routing\Router;
use Cake\TestSuite\CakeTestCase;
use Cake\Utility\ClassRegistry;
use Cake\View\View;
use ReflectionClass;
use TestNamespacedPlugin\Console\Command\ModernShell;
use TestNamespacedPlugin\Console\Command\Task\ModernTask;
use TestNamespacedPlugin\Controller\Component\ModernComponent;
use TestNamespacedPlugin\Controller\ModernController;
use TestNamespacedPlugin\Controller\TestNamespacedPluginAppController;
use TestNamespacedPlugin\Model\Behavior\ModernBehavior;
use TestNamespacedPlugin\Model\ModernArticle;
use TestNamespacedPlugin\Model\TestNamespacedPluginAppModel;
use TestNamespacedPlugin\View\Helper\ModernHelper;
use TestNamespacedPlugin\View\ModernView;
use User;

App::uses('AppController', 'Controller');
App::uses('AppModel', 'Model');
App::uses('User', 'Model');

/**
 * Shell dispatcher that returns the shells without dispatching them
 *
 * @package       Cake.Test.Case.Core
 */
class NamespacedPluginShellDispatcher extends ShellDispatcher
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
 * Tests a plugin with the CakePHP 5 layout, in the namespace of the plugin, in an application
 * with the CakePHP 2 layout, without App.namespace
 *
 * @package       Cake.Test.Case.Core
 */
class NamespacedPluginTest extends CakeTestCase
{
    /**
     * fixtures property
     *
     * @var array
     */
    public $fixtures = ['core.article', 'core.user'];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        App::build([
            'Plugin' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'plugins' . DS],
            'Controller' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Controller' . DS],
            'Model' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Model' . DS],
            'View' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'View' . DS],
        ], App::RESET);
        CakePlugin::load('TestNamespacedPlugin', ['bootstrap' => true, 'routes' => true]);
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
     * Test that the base classes of the plugin extend the global classes of the application
     *
     * @return void
     */
    public function testBaseClassesExtendApplicationClasses(): void
    {
        $this->assertNull(Configure::read('App.namespace'));

        $this->assertSame('AppController', get_parent_class(TestNamespacedPluginAppController::class));
        $this->assertSame(
            CORE_TESTS . DS . 'test_legacy_app' . DS . 'Controller' . DS . 'AppController.php',
            (new ReflectionClass(AppController::class))->getFileName(),
        );

        $this->assertSame('AppModel', get_parent_class(TestNamespacedPluginAppModel::class));
        $this->assertSame(
            CORE_TESTS . DS . 'test_legacy_app' . DS . 'Model' . DS . 'AppModel.php',
            (new ReflectionClass(AppModel::class))->getFileName(),
        );
    }

    /**
     * Test that ClassRegistry::init() returns the models of the plugin
     *
     * @return void
     */
    public function testClassRegistryInit(): void
    {
        $Article = ClassRegistry::init('TestNamespacedPlugin.ModernArticle');
        $this->assertInstanceOf(ModernArticle::class, $Article);
        $this->assertInstanceOf(AppModel::class, $Article);
        $this->assertSame('ModernArticle', $Article->alias);
        $this->assertSame('TestNamespacedPlugin', $Article->plugin);
        $this->assertInstanceOf(User::class, $Article->User);

        $Article->id = 2;
        $Article->saveField('published', 'N');
        $result = $Article->find('published', ['fields' => ['id'], 'recursive' => -1, 'order' => 'id']);
        $this->assertSame([1, 3], array_map('intval', array_column(array_column($result, 'ModernArticle'), 'id')));

        $result = $Article->find('first', ['conditions' => ['ModernArticle.id' => 1]]);
        $this->assertSame('mariano', $result['User']['user']);

        $Model = ClassRegistry::init('TestNamespacedPlugin.ModernComment');
        $this->assertInstanceOf(TestNamespacedPluginAppModel::class, $Model);
        $this->assertSame('ModernComment', $Model->name);
    }

    /**
     * Test that the behaviors of the plugin are loaded by name
     *
     * @return void
     */
    public function testBehaviorsLoad(): void
    {
        $Article = ClassRegistry::init('TestNamespacedPlugin.ModernArticle');
        $this->assertTrue($Article->Behaviors->loaded('Modern'));
        $this->assertInstanceOf(ModernBehavior::class, $Article->Behaviors->Modern);
        $this->assertSame('modern behavior of ModernArticle', $Article->modernName());

        $User = ClassRegistry::init('User');
        $User->Behaviors->load('TestNamespacedPlugin.Modern');
        $this->assertInstanceOf(ModernBehavior::class, $User->Behaviors->Modern);
        $this->assertSame('modern behavior of User', $User->modernName());
    }

    /**
     * Test that the models, components and helpers of a plugin controller are found by name
     *
     * @return void
     */
    public function testControllerClasses(): void
    {
        $request = new CakeRequest('/test_namespaced_plugin/modern/index', false);
        $request->addParams(['plugin' => 'test_namespaced_plugin', 'controller' => 'modern', 'action' => 'index']);
        $Controller = new ModernController($request, new CakeResponse());
        $Controller->constructClasses();
        $this->assertInstanceOf(AppController::class, $Controller);
        $this->assertSame('TestNamespacedPlugin', $Controller->plugin);
        $this->assertInstanceOf(ModernArticle::class, $Controller->ModernArticle);
        $this->assertInstanceOf(ModernComponent::class, $Controller->Modern);
        $this->assertContains('TestNamespacedPlugin.Modern', $Controller->helpers);

        $Controller->viewClass = 'TestNamespacedPlugin.Modern';
        $Controller->index();
        $Controller->render('index');
        $this->assertInstanceOf(ModernView::class, $Controller->View);
        $this->assertInstanceOf(ModernHelper::class, $Controller->View->Modern);
    }

    /**
     * Test dispatching to a controller of the plugin, which renders the templates of the plugin
     *
     * @return void
     */
    public function testDispatch(): void
    {
        $Dispatcher = new Dispatcher();
        $expected = 'modern layout: modern index: It is a variable, modern helper this is the element in the templates directory of a plugin';

        ob_start();
        $Dispatcher->dispatch(new CakeRequest('/test_namespaced_plugin/modern/index'), $this->getMock(CakeResponse::class, ['_sendHeader']));
        $result = ob_get_clean();
        $this->assertSame($expected, $result);

        CakePlugin::routes('TestNamespacedPlugin');
        ob_start();
        $Dispatcher->dispatch(new CakeRequest('/modern'), $this->getMock(CakeResponse::class, ['_sendHeader']));
        $result = ob_get_clean();
        $this->assertSame($expected, $result);
    }

    /**
     * Test requestAction() to a controller of the plugin
     *
     * @return void
     */
    public function testRequestAction(): void
    {
        Router::reload();
        $Object = new CakeObject();

        $result = $Object->requestAction('/test_namespaced_plugin/modern/index', ['return']);
        $this->assertSame('modern index: It is a variable, modern helper', $result);

        $result = $Object->requestAction(['plugin' => 'test_namespaced_plugin', 'controller' => 'modern', 'action' => 'some_method']);
        $this->assertSame(25, $result);
    }

    /**
     * Test that a View renders the elements of the plugin
     *
     * @return void
     */
    public function testViewElement(): void
    {
        $View = new View(null);
        $result = $View->element('TestNamespacedPlugin.modern_element');
        $this->assertSame('this is the element in the templates directory of a plugin', $result);
    }

    /**
     * Test that the shells and tasks of the plugin are found by name
     *
     * @return void
     */
    public function testShellAndTask(): void
    {
        $Dispatcher = new NamespacedPluginShellDispatcher([], false);

        $Shell = $Dispatcher->getShell('test_namespaced_plugin.modern');
        $this->assertInstanceOf(ModernShell::class, $Shell);
        $this->assertSame('TestNamespacedPlugin', $Shell->plugin);
        $this->assertSame('Modern', $Shell->name);

        $Shell = $Dispatcher->getShell('TestNamespacedPlugin.Modern');
        $this->assertInstanceOf(ModernShell::class, $Shell);

        $Shell->loadTasks();
        $this->assertInstanceOf(ModernTask::class, $Shell->Modern);
    }

    /**
     * Test that __d() reads the translations of the plugin
     *
     * @return void
     */
    public function testTranslation(): void
    {
        Configure::write('Config.language', 'po');
        $result = __d('test_namespaced_plugin', 'Plural Rule 1');
        $this->assertSame('Plural Rule 1 (from resources/locales)', $result);
    }

    /**
     * Test that the config files of the plugin are read
     *
     * @return void
     */
    public function testConfig(): void
    {
        $this->assertSame('loaded from config/bootstrap.php', Configure::read('TestNamespacedPlugin.bootstrap'));

        Configure::load('TestNamespacedPlugin.modern_config');
        $this->assertSame('read from config/modern_config.php', Configure::read('ModernConfig'));
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
        $request = new CakeRequest('test_namespaced_plugin/css/modern.css');
        $event = new CakeEvent('DispatcherTest', $this, compact('request', 'response'));

        $this->assertSame($response, $filter->beforeDispatch($event));
        $result = ob_get_clean();
        $this->assertTrue($event->isStopped());

        $file = CORE_TESTS . DS . 'test_legacy_app' . DS . 'plugins' . DS . 'TestNamespacedPlugin' . DS . 'webroot' . DS . 'css' . DS . 'modern.css';
        $this->assertSame(file_get_contents($file), $result);
    }
}
