<?php

/**
 * AppTest file.
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
 * @since         CakePHP(tm) v 2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Test\TestCase\Core;

use AppController;
use AppModel;
use Cake\Cache\Cache;
use Cake\Configure\PhpReader;
use Cake\Console\Shell;
use Cake\Controller\Component\AuthComponent;
use Cake\Controller\Component\RequestHandlerComponent;
use Cake\Controller\Controller;
use Cake\Core\App;
use Cake\Core\CakePlugin;
use Cake\Core\Configure;
use Cake\Log\CakeLog;
use Cake\Log\Engine\BaseLog;
use Cake\Model\Behavior\ContainableBehavior;
use Cake\Model\Datasource\DboSource;
use Cake\Model\Model;
use Cake\TestSuite\CakeTestCase;
use Cake\Utility\Folder;
use Cake\View\Helper\FormHelper;
use Closure;
use Composer\Autoload\ClassLoader;
use ConfigureTestVendorSample;
use ExampleExample;
use ReflectionClass;
use RuntimeException;
use SamplePluginClassTestName;
use TestApp\Controller\Component\NamespacedComponent;
use TestApp\Controller\PagesController;
use TestApp\Lib\Library;
use TestApp\Log\Engine\AppTestCaptureLog;
use TestApp\Model\NamespacedPost;
use TestApp\Model\PersisterOne;
use TestApp\Model\PersisterTwo;
use TestApp\Utility\TestUtilityClass;
use TestApp\View\Helper\BananaHelper;
use TestApp\View\Helper\NamespacedHelper;
use TestPlugin\Controller\Component\OtherComponent;
use TestPlugin\Controller\TestPluginAppController;
use TestPlugin\Controller\TestsController;
use TestPlugin\Lib\Custom\Package\CustomLibClass;
use TestPlugin\Lib\TestPluginLibrary;
use TestPlugin\Lib\TestPluginOtherLibrary;
use TestPlugin\Model\Datasource\TestSource;
use TestPlugin\View\Helper\OtherHelperHelper;
use TestPlugin\View\Helper\TestPluginAppHelper;

/**
 * AppTest class
 *
 * @package       Cake.Test.Case.Core
 */
class AppTest extends CakeTestCase
{
    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        Configure::write('App.namespace', 'TestApp');
    }

    /**
     * Returns the file and object maps of App and whether they changed.
     *
     * @return array
     */
    protected function _appMaps(): array
    {
        return Closure::bind(function () {
            return [static::$_map, static::$_objects, static::$_cacheChange, static::$_objectCacheChange];
        }, null, App::class)();
    }

    /**
     * Sets the file and object maps of App and whether they changed.
     *
     * @param array $maps The maps, as _appMaps() returns them.
     * @return void
     */
    protected function _setAppMaps(array $maps): void
    {
        Closure::bind(function () use ($maps) {
            [static::$_map, static::$_objects, static::$_cacheChange, static::$_objectCacheChange] = $maps;
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
        CakeLog::drop('app_test_capture');
        CakeLog::drop('app_test_throwing');
        AppTestCaptureLog::$messages = [];
        AppTestThrowingLog::$calls = 0;
        $this->_setUsesReported(true);
        // The import tests map their own names to files; drop those names again. The other
        // names stay mapped, so that a vendor file that declares a class is not included twice.
        $maps = $this->_appMaps();
        foreach (array_keys($maps[0]) as $name) {
            if (str_contains($name, 'AppTestMapped') || str_contains($name, 'AppTestShutdown')) {
                unset($maps[0][$name]);
            }
        }
        $this->_setAppMaps($maps);

        parent::tearDown();
    }

    /**
     * Sets whether the deprecation of App::uses() was already reported.
     *
     * @param bool $reported Whether it was reported.
     * @return void
     */
    protected function _setUsesReported($reported): void
    {
        Closure::bind(function () use ($reported) {
            static::$_usesDeprecationReported = $reported;
        }, null, App::class)();
    }

    /**
     * Lets App::uses() report its deprecation again, and keeps the messages that are logged.
     *
     * @return void
     */
    protected function _captureUsesReports(): void
    {
        $this->_setUsesReported(false);
        AppTestCaptureLog::$messages = [];
        CakeLog::config('app_test_capture', ['engine' => AppTestCaptureLog::class]);
    }

    /**
     * Calls a function and collects the E_USER_DEPRECATED errors it triggers.
     *
     * @param callable $callback Function to call.
     * @return array<int, string>
     */
    protected function _collectDeprecations(callable $callback): array
    {
        $errors = [];
        set_error_handler(function ($errno, $errstr) use (&$errors) {
            $errors[] = $errstr;

            return true;
        }, E_USER_DEPRECATED);
        try {
            $callback();
        } finally {
            restore_error_handler();
        }

        return $errors;
    }

    /**
     * App::uses() logs its deprecation once, with the file and line of the call.
     *
     * @return void
     */
    public function testUsesDeprecationLoggedOnce(): void
    {
        Configure::write('debug', 0);
        $this->_captureUsesReports();

        $line = __LINE__ + 2;
        $errors = $this->_collectDeprecations(function () {
            App::uses('AppTestUsesFirst', 'Model');
            App::uses('AppTestUsesSecond', 'Model');
        });

        $this->assertSame([], $errors, 'No E_USER_DEPRECATED with debug off');
        $this->assertSame([[
            'warning',
            'App::uses() is deprecated (called in ' . __FILE__ . ' on line ' . $line . '). Let Composer load the classes'
            . ' of the application and of its plugins, with a classmap or PSR-4 in composer.json.',
        ]], AppTestCaptureLog::$messages);
    }

    /**
     * With debug on, the deprecation of App::uses() is also triggered as E_USER_DEPRECATED, once.
     *
     * @return void
     */
    public function testUsesDeprecationTriggeredOnceInDebug(): void
    {
        Configure::write('debug', 2);
        $this->_captureUsesReports();

        $errors = $this->_collectDeprecations(function () {
            App::uses('AppTestUsesFirst', 'Model');
            App::uses('AppTestUsesSecond', 'Model');
        });

        $this->assertCount(1, $errors);
        $this->assertStringStartsWith('App::uses() is deprecated (called in ' . __FILE__ . ' on line ', $errors[0]);
        $this->assertSame($errors[0], AppTestCaptureLog::$messages[0][1]);
    }

    /**
     * The core finds the classes without App::uses(), so it does not report the deprecation.
     *
     * @return void
     */
    public function testCoreDoesNotUseUses(): void
    {
        Configure::write('debug', 2);
        $this->_captureUsesReports();

        $errors = $this->_collectDeprecations(function () {
            App::className('AppTestMissing', 'Model');
            App::import('Model', 'AppTestMissing');
            App::import('Console/Command', 'Shell', false);
        });

        $this->assertSame([], $errors);
        $this->assertSame([], AppTestCaptureLog::$messages);
    }

    /**
     * testBuild method
     *
     * @return void
     */
    public function testBuild(): void
    {
        $old = App::path('Model');
        $expected = [
            APP . 'Model' . DS,
        ];
        $this->assertEquals($expected, $old);

        App::build(['Model' => ['/path/to/models/']]);
        $new = App::path('Model');
        $expected = [
            '/path/to/models/',
            APP . 'Model' . DS,
        ];
        $this->assertEquals($expected, $new);

        App::build();
        App::build(['Model' => ['/path/to/models/']], App::PREPEND);
        $new = App::path('Model');
        $expected = [
            '/path/to/models/',
            APP . 'Model' . DS,
        ];
        $this->assertEquals($expected, $new);

        App::build();
        App::build(['Model' => ['/path/to/models/']], App::APPEND);
        $new = App::path('Model');
        $expected = [
            APP . 'Model' . DS,
            '/path/to/models/',
        ];
        $this->assertEquals($expected, $new);

        App::build();
        App::build([
            'Model' => ['/path/to/models/'],
            'Controller' => ['/path/to/controllers/'],
        ], App::APPEND);
        $new = App::path('Model');
        $expected = [
            APP . 'Model' . DS,
            '/path/to/models/',
        ];
        $this->assertEquals($expected, $new);
        $new = App::path('Controller');
        $expected = [
            APP . 'Controller' . DS,
            '/path/to/controllers/',
        ];
        $this->assertEquals($expected, $new);

        App::build(); //reset defaults
        $defaults = App::path('Model');
        $this->assertEquals($old, $defaults);
    }

    /**
     * tests that it is possible to set up paths using the CakePHP 1.3 notation for them (models, behaviors, controllers...)
     *
     * @return void
     */
    public function testCompatibleBuild(): void
    {
        $old = App::path('models');
        $expected = [
            APP . 'Model' . DS,
        ];
        $this->assertEquals($expected, $old);

        App::build(['models' => ['/path/to/models/']]);

        $new = App::path('models');

        $expected = [
            '/path/to/models/',
            APP . 'Model' . DS,
        ];
        $this->assertEquals($expected, $new);
        $this->assertEquals($expected, App::path('Model'));

        App::build(['datasources' => ['/path/to/datasources/']]);
        $expected = [
            '/path/to/datasources/',
            APP . 'Model' . DS . 'Datasource' . DS,
        ];
        $result = App::path('datasources');
        $this->assertEquals($expected, $result);
        $this->assertEquals($expected, App::path('Model/Datasource'));

        App::build(['behaviors' => ['/path/to/behaviors/']]);
        $expected = [
            '/path/to/behaviors/',
            APP . 'Model' . DS . 'Behavior' . DS,
        ];
        $result = App::path('behaviors');
        $this->assertEquals($expected, $result);
        $this->assertEquals($expected, App::path('Model/Behavior'));

        App::build(['controllers' => ['/path/to/controllers/']]);
        $expected = [
            '/path/to/controllers/',
            APP . 'Controller' . DS,
        ];
        $result = App::path('controllers');
        $this->assertEquals($expected, $result);
        $this->assertEquals($expected, App::path('Controller'));

        App::build(['components' => ['/path/to/components/']]);
        $expected = [
            '/path/to/components/',
            APP . 'Controller' . DS . 'Component' . DS,
        ];
        $result = App::path('components');
        $this->assertEquals($expected, $result);
        $this->assertEquals($expected, App::path('Controller/Component'));

        App::build(['views' => ['/path/to/views/']]);
        $expected = [
            '/path/to/views/',
            APP . 'View' . DS,
            ROOT . DS . 'templates' . DS,
        ];
        $result = App::path('views');
        $this->assertEquals($expected, $result);
        $this->assertEquals($expected, App::path('View'));

        App::build(['helpers' => ['/path/to/helpers/']]);
        $expected = [
            '/path/to/helpers/',
            APP . 'View' . DS . 'Helper' . DS,
        ];
        $result = App::path('helpers');
        $this->assertEquals($expected, $result);
        $this->assertEquals($expected, App::path('View/Helper'));

        App::build(['shells' => ['/path/to/shells/']]);
        $expected = [
            '/path/to/shells/',
            APP . 'Console' . DS . 'Command' . DS,
        ];
        $result = App::path('shells');
        $this->assertEquals($expected, $result);
        $this->assertEquals($expected, App::path('Console/Command'));

        App::build(); //reset defaults
        $defaults = App::path('Model');
        $this->assertEquals($old, $defaults);
    }

    /**
     * test package build() with App::REGISTER.
     *
     * @return void
     */
    public function testBuildPackage(): void
    {
        $pluginPaths = [
            '/foo/bar',
            APP . 'Plugin' . DS,
            ROOT . DS . 'plugins' . DS,
            dirname(CAKE) . DS . 'plugins' . DS,
        ];
        App::build([
            'Plugin' => [
                '/foo/bar',
            ],
        ]);
        $result = App::path('Plugin');
        $this->assertEquals($pluginPaths, $result);

        $paths = App::path('Service');
        $this->assertEquals([], $paths);

        App::build([
            'Service' => [
                '%s' . 'Service' . DS,
            ],
        ], App::REGISTER);

        $expected = [
            APP . 'Service' . DS,
        ];
        $result = App::path('Service');
        $this->assertEquals($expected, $result);

        //Ensure new paths registered for other packages are not affected
        $result = App::path('Plugin');
        $this->assertEquals($pluginPaths, $result);

        App::build();
        $paths = App::path('Service');
        $this->assertEquals([], $paths);
    }

    /**
     * test path() with a plugin.
     *
     * @return void
     */
    public function testPathWithPlugins(): void
    {
        $basepath = CORE_TESTS . DS . 'test_app' . DS . 'plugins' . DS;
        App::build([
            'Plugin' => [$basepath],
        ]);
        CakePlugin::load('TestPlugin');

        $result = App::path('Vendor', 'TestPlugin');
        $this->assertEquals($basepath . 'TestPlugin' . DS . 'vendor' . DS, $result[0]);
    }

    /**
     * testBuildWithReset method
     *
     * @return void
     */
    public function testBuildWithReset(): void
    {
        $old = App::path('Model');
        $expected = [
            APP . 'Model' . DS,
        ];
        $this->assertEquals($expected, $old);

        App::build(['Model' => ['/path/to/models/']], App::RESET);

        $new = App::path('Model');

        $expected = [
            '/path/to/models/',
        ];
        $this->assertEquals($expected, $new);

        App::build(); //reset defaults
        $defaults = App::path('Model');
        $this->assertEquals($old, $defaults);
    }

    /**
     * testCore method
     *
     * @return void
     */
    public function testCore(): void
    {
        $model = App::core('Model');
        $this->assertEquals([CAKE . 'Model' . DS], $model);

        $view = App::core('View');
        $this->assertEquals([CAKE . 'View' . DS], $view);

        $controller = App::core('Controller');
        $this->assertEquals([CAKE . 'Controller' . DS], $controller);

        $component = App::core('Controller/Component');
        $this->assertEquals([CAKE . 'Controller' . DS . 'Component' . DS], str_replace('/', DS, $component));

        $auth = App::core('Controller/Component/Auth');
        $this->assertEquals([CAKE . 'Controller' . DS . 'Component' . DS . 'Auth' . DS], str_replace('/', DS, $auth));

        $datasource = App::core('Model/Datasource');
        $this->assertEquals([CAKE . 'Model' . DS . 'Datasource' . DS], str_replace('/', DS, $datasource));
    }

    /**
     * testListObjects method
     *
     * @return void
     */
    public function testListObjects(): void
    {
        $result = App::objects('class', CAKE . 'Routing', false);
        $this->assertTrue(in_array('Dispatcher', $result));
        $this->assertTrue(in_array('Router', $result));

        App::build([
            'Model/Behavior' => App::core('Model/Behavior'),
            'Controller' => App::core('Controller'),
            'Controller/Component' => App::core('Controller/Component'),
            'View' => App::core('View'),
            'Model' => App::core('Model'),
            'View/Helper' => App::core('View/Helper'),
        ], App::RESET);
        $result = App::objects('behavior', null, false);
        $this->assertTrue(in_array('TreeBehavior', $result));
        $result = App::objects('Model/Behavior', null, false);
        $this->assertTrue(in_array('TreeBehavior', $result));

        $result = App::objects('component', null, false);
        $this->assertTrue(in_array('AuthComponent', $result));
        $result = App::objects('Controller/Component', null, false);
        $this->assertTrue(in_array('AuthComponent', $result));

        $result = App::objects('view', null, false);
        $this->assertTrue(in_array('MediaView', $result));
        $result = App::objects('View', null, false);
        $this->assertTrue(in_array('MediaView', $result));

        $result = App::objects('helper', null, false);
        $this->assertTrue(in_array('HtmlHelper', $result));
        $result = App::objects('View/Helper', null, false);
        $this->assertTrue(in_array('HtmlHelper', $result));

        $result = App::objects('model', null, false);
        $this->assertTrue(in_array('AcoAction', $result));
        $result = App::objects('Model', null, false);
        $this->assertTrue(in_array('AcoAction', $result));

        $result = App::objects('file');
        $this->assertFalse($result);

        $result = App::objects('file', 'non_existing_configure');
        $expected = [];
        $this->assertEquals($expected, $result);

        $result = App::objects('NonExistingType');
        $this->assertSame([], $result);

        App::build([
            'plugins' => [
                CORE_TESTS . DS . 'test_app' . DS . 'src' . DS,
            ],
        ]);
        $result = App::objects('plugin', null, false);
        $this->assertTrue(in_array('Cache', $result));
        $this->assertTrue(in_array('Log', $result));

        App::build();
    }

    /**
     * Make sure that .svn and friends are excluded from App::objects('plugin')
     *
     * @return void
     */
    public function testListObjectsIgnoreDotDirectories(): void
    {
        $path = CORE_TESTS . DS . 'test_app' . DS . 'plugins' . DS;

        $this->skipIf(!is_writable($path), $path . ' is not writable.');

        App::build([
            'plugins' => [$path],
        ], App::RESET);
        mkdir($path . '.svn');
        $result = App::objects('plugin', null, false);
        rmdir($path . '.svn');

        $this->assertNotContains('.svn', $result);
    }

    /**
     * Tests listing objects within a plugin
     *
     * @return void
     */
    public function testListObjectsInPlugin(): void
    {
        App::build([
            'Model' => [CORE_TESTS . DS . 'test_app' . DS . 'src' . DS . 'Model' . DS],
            'plugins' => [CORE_TESTS . DS . 'test_app' . DS . 'plugins' . DS],
        ], App::RESET);
        CakePlugin::load(['TestPlugin', 'TestTwoPlugin']);

        $result = App::objects('TestPlugin.model');
        $this->assertTrue(in_array('TestPluginPost', $result));
        $result = App::objects('TestPlugin.Model');
        $this->assertTrue(in_array('TestPluginPost', $result));

        $result = App::objects('TestPlugin.behavior');
        $this->assertTrue(in_array('TestPluginPersisterOneBehavior', $result));
        $result = App::objects('TestPlugin.Model/Behavior');
        $this->assertTrue(in_array('TestPluginPersisterOneBehavior', $result));

        $result = App::objects('TestPlugin.helper');
        $expected = ['OtherHelperHelper', 'PluggedHelperHelper', 'TestPluginAppHelper'];
        $this->assertEquals($expected, $result);
        $result = App::objects('TestPlugin.View/Helper');
        $expected = ['OtherHelperHelper', 'PluggedHelperHelper', 'TestPluginAppHelper'];
        $this->assertEquals($expected, $result);

        $result = App::objects('TestPlugin.component');
        $this->assertTrue(in_array('OtherComponent', $result));
        $result = App::objects('TestPlugin.Controller/Component');
        $this->assertTrue(in_array('OtherComponent', $result));

        $result = App::objects('TestTwoPlugin.behavior');
        $this->assertSame([], $result);
        $result = App::objects('TestTwoPlugin.Model/Behavior');
        $this->assertSame([], $result);

        $result = App::objects('model', null, false);
        $this->assertTrue(in_array('Comment', $result));
        $this->assertTrue(in_array('Post', $result));

        $result = App::objects('Model', null, false);
        $this->assertTrue(in_array('Comment', $result));
        $this->assertTrue(in_array('Post', $result));

        App::build();
    }

    /**
     * test that themePath can find paths for themes.
     *
     * @return void
     */
    public function testThemePath(): void
    {
        App::build([
            'View' => [
                CORE_TESTS . DS . 'test_app' . DS . 'src' . DS . 'View' . DS,
                CORE_TESTS . DS . 'test_app' . DS . 'templates' . DS,
            ],
        ]);
        $path = App::themePath('test_theme');
        $expected = CORE_TESTS . DS . 'test_app' . DS . 'templates' . DS . 'Themed' . DS . 'TestTheme' . DS;
        $this->assertEquals($expected, $path);

        $path = App::themePath('TestTheme');
        $expected = CORE_TESTS . DS . 'test_app' . DS . 'templates' . DS . 'Themed' . DS . 'TestTheme' . DS;
        $this->assertEquals($expected, $path);

        App::build();
    }

    /**
     * testClassLoading method
     *
     * @return void
     */
    public function testClassLoading(): void
    {
        $file = App::import('Model', 'Model', false);
        $this->assertTrue($file);
        $this->assertTrue(class_exists(Model::class));

        $file = App::import('Controller', 'Controller', false);
        $this->assertTrue($file);
        $this->assertTrue(class_exists(Controller::class));

        $file = App::import('Component', 'Auth', false);
        $this->assertTrue($file);
        $this->assertTrue(class_exists(AuthComponent::class));

        $file = App::import('Shell', 'Shell', false);
        $this->assertTrue($file);
        $this->assertTrue(class_exists(Shell::class));

        $file = App::import('Configure', 'PhpReader');
        $this->assertTrue($file);
        $this->assertTrue(class_exists(PhpReader::class));

        $file = App::import('Model', 'SomeRandomModelThatDoesNotExist', false);
        $this->assertFalse($file);

        $file = App::import('Model', 'AppModel', false);
        $this->assertTrue($file);
        $this->assertTrue(class_exists(AppModel::class));

        $file = App::import('WrongType', null, true, [], '');
        $this->assertFalse($file);

        $file = App::import('Model', 'NonExistingPlugin.NonExistingModel', false);
        $this->assertFalse($file);

        $file = App::import('Model', ['NonExistingPlugin.NonExistingModel'], false);
        $this->assertFalse($file);

        if (!class_exists(AppController::class, false)) {
            $classes = array_flip(get_declared_classes());

            $this->assertFalse(isset($classes['PagesController']));
            $this->assertFalse(isset($classes['AppController']));

            $file = App::import('Controller', 'Pages');
            $this->assertTrue($file);
            $this->assertTrue(class_exists(PagesController::class));

            $classes = array_flip(get_declared_classes());

            $this->assertTrue(isset($classes['PagesController']));
            $this->assertTrue(isset($classes['AppController']));

            $file = App::import('Behavior', 'Containable');
            $this->assertTrue($file);
            $this->assertTrue(class_exists(ContainableBehavior::class));

            $file = App::import('Component', 'RequestHandler');
            $this->assertTrue($file);
            $this->assertTrue(class_exists(RequestHandlerComponent::class));

            $file = App::import('Helper', 'Form');
            $this->assertTrue($file);
            $this->assertTrue(class_exists(FormHelper::class));

            $file = App::import('Model', 'NonExistingModel');
            $this->assertFalse($file);

            $file = App::import('Datasource', 'DboSource');
            $this->assertTrue($file);
            $this->assertTrue(class_exists(DboSource::class));
        }
        App::build();
    }

    /**
     * test import() with plugins
     *
     * @return void
     */
    public function testPluginImporting(): void
    {
        App::build([
            'Lib' => [CORE_TESTS . DS . 'test_app' . DS . 'src' . DS . 'Lib' . DS],
            'Plugin' => [CORE_TESTS . DS . 'test_app' . DS . 'plugins' . DS],
        ]);
        CakePlugin::load(['TestPlugin', 'TestTwoPlugin']);

        $result = App::import('Controller', 'TestPlugin.Tests');
        $this->assertTrue($result);
        $this->assertTrue(class_exists(TestPluginAppController::class));
        $this->assertTrue(class_exists(TestsController::class));

        $result = App::import('Lib', 'TestPlugin.TestPluginLibrary');
        $this->assertTrue($result);
        $this->assertTrue(class_exists(TestPluginLibrary::class));

        $result = App::import('Lib', 'Library');
        $this->assertTrue($result);
        $this->assertTrue(class_exists(Library::class));

        $result = App::import('Helper', 'TestPlugin.OtherHelper');
        $this->assertTrue($result);
        $this->assertTrue(class_exists(OtherHelperHelper::class));

        $result = App::import('Helper', 'TestPlugin.TestPluginApp');
        $this->assertTrue($result);
        $this->assertTrue(class_exists(TestPluginAppHelper::class));

        $result = App::import('Datasource', 'TestPlugin.TestSource');
        $this->assertTrue($result);
        $this->assertTrue(class_exists(TestSource::class));

        App::uses('ExampleExample', 'TestPlugin.vendor/Example');
        $this->assertTrue(class_exists(ExampleExample::class));

        App::build();
    }

    /**
     * test that building helper paths actually works.
     *
     * @return void
     */
    public function testImportingHelpersFromAlternatePaths(): void
    {
        $this->assertFalse(class_exists(BananaHelper::class, false), 'BananaHelper exists, cannot test importing it.');
        App::build([
            'View/Helper' => [
                CORE_TESTS . DS . 'test_app' . DS . 'src' . DS . 'View' . DS . 'Helper' . DS,
            ],
        ]);
        $this->assertFalse(class_exists(BananaHelper::class, false), 'BananaHelper exists, cannot test importing it.');
        App::import('Helper', 'Banana');
        $this->assertTrue(class_exists(BananaHelper::class, false), 'BananaHelper was not loaded.');

        App::build();
    }

    /**
     * testFileLoading method
     *
     * @return void
     */
    public function testFileLoading(): void
    {
        $file = App::import('File', 'RealFile', false, [], CORE_ROOT . DS . 'config' . DS . 'config.php');
        $this->assertTrue($file);

        $file = App::import('File', 'NoFile', false, [], CORE_ROOT . DS . 'config' . DS . 'cake' . DS . 'config.php');
        $this->assertFalse($file);
    }

    /**
     * testFileLoadingWithArray method
     *
     * @return void
     */
    public function testFileLoadingWithArray(): void
    {
        $type = [
            'type' => 'File',
            'name' => 'SomeName',
            'parent' => false,
            'file' => CORE_ROOT . DS . 'config' . DS . 'config.php',
        ];
        $file = App::import($type);
        $this->assertTrue($file);

        $type = [
            'type' => 'File',
            'name' => 'NoFile',
            'parent' => false,
            'file' => CORE_ROOT . DS . 'config' . DS . 'cake' . DS . 'config.php',
        ];
        $file = App::import($type);
        $this->assertFalse($file);
    }

    /**
     * testFileLoadingReturnValue method
     *
     * @return void
     */
    public function testFileLoadingReturnValue(): void
    {
        $file = App::import('File', 'Name', false, [], CORE_ROOT . DS . 'config' . DS . 'config.php', true);
        $this->assertTrue(!empty($file));

        $this->assertTrue(isset($file['Cake.version']));

        $type = [
            'type' => 'File',
            'name' => 'OtherName',
            'parent' => false,
            'file' => CORE_ROOT . DS . 'config' . DS . 'config.php', 'return' => true,
        ];
        $file = App::import($type);
        $this->assertTrue(!empty($file));

        $this->assertTrue(isset($file['Cake.version']));
    }

    /**
     * testLoadingWithSearch method
     *
     * @return void
     */
    public function testLoadingWithSearch(): void
    {
        $file = App::import('File', 'NewName', false, [CORE_ROOT . DS . 'config' . DS], 'config.php');
        $this->assertTrue($file);

        $file = App::import('File', 'AnotherNewName', false, [CAKE], 'config.php');
        $this->assertFalse($file);

        $file = App::import('File', 'SecondPathName', false, [CAKE, CORE_ROOT . DS . 'config' . DS], 'config.php');
        $this->assertTrue($file, 'The file should be found in a search path other than the first one.');
    }

    /**
     * testLoadingWithSearchArray method
     *
     * @return void
     */
    public function testLoadingWithSearchArray(): void
    {
        $type = [
            'type' => 'File',
            'name' => 'RandomName',
            'parent' => false,
            'file' => 'config.php',
            'search' => [CORE_ROOT . DS . 'config' . DS],
        ];
        $file = App::import($type);
        $this->assertTrue($file);

        $type = [
            'type' => 'File',
            'name' => 'AnotherRandomName',
            'parent' => false,
            'file' => 'config.php',
            'search' => [CAKE],
        ];
        $file = App::import($type);
        $this->assertFalse($file);
    }

    /**
     * testMultipleLoading method
     *
     * @return void
     */
    public function testMultipleLoading(): void
    {
        if (class_exists(PersisterOne::class, false) || class_exists(PersisterTwo::class, false)) {
            $this->markTestSkipped('Cannot test loading of classes that exist.');
        }
        App::build([
            'Model' => [CORE_TESTS . DS . 'test_app' . DS . 'src' . DS . 'Model' . DS],
        ]);
        $toLoad = ['PersisterOne', 'PersisterTwo'];
        $load = App::import('Model', $toLoad);
        $this->assertTrue($load);

        $classes = array_flip(get_declared_classes());

        $this->assertTrue(isset($classes[PersisterOne::class]));
        $this->assertTrue(isset($classes[PersisterTwo::class]));

        $load = App::import('Model', ['PersisterOne', 'SomeNotFoundClass', 'PersisterTwo']);
        $this->assertFalse($load);
    }

    public function testLoadingVendor(): void
    {
        App::build([
            'plugins' => [CORE_TESTS . DS . 'test_app' . DS . 'plugins' . DS],
            'vendors' => [CORE_TESTS . DS . 'test_app' . DS . 'vendor' . DS],
        ], App::RESET);
        CakePlugin::load(['TestPlugin', 'TestTwoPlugin']);

        ob_start();
        $result = App::import('Vendor', 'css/TestAsset', ['ext' => 'css']);
        $text = ob_get_clean();
        $this->assertTrue($result);
        $this->assertEquals('/* this is the test asset css file */', trim($text));

        $result = App::import('Vendor', 'TestPlugin.sample/SamplePlugin');
        $this->assertTrue($result);
        $this->assertTrue(class_exists(SamplePluginClassTestName::class, false));

        $result = App::import('Vendor', 'sample/ConfigureTestVendorSample');
        $this->assertTrue($result);
        $this->assertTrue(class_exists(ConfigureTestVendorSample::class, false));

        ob_start();
        $result = App::import('Vendor', 'SomeNameInSubfolder', ['file' => 'somename/some.name.php']);
        $text = ob_get_clean();
        $this->assertTrue($result);
        $this->assertEquals('This is a file with dot in file name', $text);

        ob_start();
        $result = App::import('Vendor', 'TestHello', ['file' => 'Test' . DS . 'hello.php']);
        $text = ob_get_clean();
        $this->assertTrue($result);
        $this->assertEquals('This is the hello.php file in Test directory', $text);

        ob_start();
        $result = App::import('Vendor', 'MyTest', ['file' => 'Test' . DS . 'MyTest.php']);
        $text = ob_get_clean();
        $this->assertTrue($result);
        $this->assertEquals('This is the MyTest.php file', $text);

        ob_start();
        $result = App::import('Vendor', 'Welcome');
        $text = ob_get_clean();
        $this->assertTrue($result);
        $this->assertEquals('This is the welcome.php file in vendors directory', $text);

        ob_start();
        $result = App::import('Vendor', 'TestPlugin.Welcome');
        $text = ob_get_clean();
        $this->assertTrue($result);
        $this->assertEquals('This is the welcome.php file in test_plugin/vendors directory', $text);
    }

    /**
     * Tests that the automatic class loader will also find in "libs" folder for both
     * app and plugins if it does not find the class in other configured paths
     *
     * @return void
     */
    public function testLoadClassInLibs(): void
    {
        App::build([
            'libs' => [CORE_TESTS . DS . 'test_app' . DS . 'src' . DS . 'Lib' . DS],
            'plugins' => [CORE_TESTS . DS . 'test_app' . DS . 'plugins' . DS],
        ], App::RESET);
        CakePlugin::load(['TestPlugin', 'TestTwoPlugin']);

        $this->assertFalse(class_exists(CustomLibClass::class, false));
        $this->assertTrue(class_exists(CustomLibClass::class));

        $this->assertFalse(class_exists(TestUtilityClass::class, false));
        $this->assertTrue(class_exists(TestUtilityClass::class));
    }

    /**
     * Tests that App::location() returns the defined path for a class
     *
     * @return void
     */
    public function testClassLocation(): void
    {
        App::uses('MyCustomClass', 'MyPackage/Name');
        $this->assertEquals('MyPackage/Name', App::location('MyCustomClass'));
    }

    /**
     * Test that paths() works.
     *
     * @return void
     */
    public function testPaths(): void
    {
        $result = App::paths();
        $this->assertArrayHasKey('Plugin', $result);
        $this->assertArrayHasKey('Controller', $result);
        $this->assertArrayHasKey('Controller/Component', $result);
    }

    /**
     * Proves that it is possible to load plugin libraries in top
     * level Lib dir for plugins
     *
     * @return void
     */
    public function testPluginLibClasses(): void
    {
        App::build([
            'plugins' => [CORE_TESTS . DS . 'test_app' . DS . 'plugins' . DS],
        ], App::RESET);
        CakePlugin::load(['TestPlugin', 'TestTwoPlugin']);
        $this->assertFalse(class_exists(TestPluginOtherLibrary::class, false));
        $this->assertTrue(class_exists(TestPluginOtherLibrary::class));
    }

    /**
     * Test that increaseMemoryLimit increases the maximum amount of memory actually
     *
     * @dataProvider memoryVariationProvider
     * @return void
     */
    public function testIncreaseMemoryLimit($memoryLimit, $additionalKb, $expected): void
    {
        $this->skipIf(!function_exists('ini_set'));

        $originalMemoryLimit = ini_get('memory_limit');

        ini_set('memory_limit', $memoryLimit);
        App::increaseMemoryLimit($additionalKb);
        $this->assertEquals($expected, ini_get('memory_limit'));

        ini_set('memory_limit', $originalMemoryLimit);
    }

    /**
     * Data provider function for testIncreaseMemoryLimit
     *
     * @return void
     */
    public function memoryVariationProvider(): mixed
    {
        return [
            ['331072K', 100000, '431072K'],
            ['256M', 1, '262145K'],
            ['1G', 1, '1048577K'],
            ['-1', 100000, '-1'],
        ];
    }

    /**
     * Test that App::className() returns the global class declared with App::uses().
     *
     * @return void
     */
    public function testClassName(): void
    {
        $this->assertSame('AuthComponent', App::className('Auth', 'Controller/Component', 'Component'));
        $this->assertSame('FileEngine', App::className('File', 'Cache/Engine', 'Engine'));
        $this->assertNull(App::className('DoesNotExist', 'Controller/Component', 'Component'));

        App::build(['Plugin' => [CORE_TESTS . DS . 'test_app' . DS . 'plugins' . DS]], App::RESET);
        CakePlugin::load('TestPlugin');
        $this->assertSame(OtherComponent::class, App::className('TestPlugin.Other', 'Controller/Component', 'Component'));
    }

    /**
     * Test that App::className() accepts fully qualified class names with or without the suffix.
     *
     * @return void
     */
    public function testClassNameFullyQualified(): void
    {
        $expected = NamespacedComponent::class;
        $this->assertSame($expected, App::className($expected, 'Controller/Component', 'Component'));
        $this->assertSame($expected, App::className('TestApp\Controller\Component\Namespaced', 'Controller/Component', 'Component'));
        $this->assertSame($expected, App::className('\\' . $expected, 'Controller/Component', 'Component'));
        $this->assertSame(NamespacedPost::class, App::className(NamespacedPost::class, 'Model'));
        $this->assertNull(App::className('TestApp\Controller\Component\DoesNotExist', 'Controller/Component', 'Component'));
        $this->assertNull(App::className(NamespacedPost::class, 'Controller/Component', 'Component'));
    }

    /**
     * Test that App::className() looks in the App.namespace namespace first.
     *
     * @return void
     */
    public function testClassNameAppNamespace(): void
    {
        $result = App::className('Namespaced', 'Controller/Component', 'Component');
        $this->assertSame(NamespacedComponent::class, $result);
        $this->assertSame(NamespacedPost::class, App::className('NamespacedPost', 'Model'));
        $this->assertSame('AuthComponent', App::className('Auth', 'Controller/Component', 'Component'));
    }

    /**
     * Test that App::import() finds namespaced classes in App.namespace and in the namespace of a plugin.
     *
     * @return void
     */
    public function testImportNamespacedClass(): void
    {
        App::build(['Plugin' => [CORE_TESTS . DS . 'test_app' . DS . 'plugins' . DS]], App::RESET);
        CakePlugin::load('TestTwoPlugin');

        $this->assertTrue(App::import('Controller', 'Namespaced'));
        $this->assertTrue(App::import('Model', 'NamespacedPost'));
        $this->assertTrue(App::import('Controller', 'TestTwoPlugin.NamespacedPlugin'));
        $this->assertFalse(App::import('Controller', 'DoesNotExist'));
    }

    /**
     * Test that loading a global name from the file of a namespaced class that is already loaded
     * does not declare the class again.
     *
     * @return void
     */
    public function testLoadFileOfLoadedNamespacedClass(): void
    {
        $this->assertTrue(class_exists(NamespacedPost::class));
        App::build(['Model' => [CORE_TESTS . DS . 'test_app' . DS . 'src' . DS . 'Model' . DS]], App::RESET);
        App::uses('NamespacedPost', 'Model');
        $this->assertFalse(class_exists('NamespacedPost'));
    }

    /**
     * @return array
     */
    public static function appClassFromAppNamespaceProvider(): array
    {
        return [[false], [true]];
    }

    /**
     * Test that the global name of an application base class is declared for the class in App.namespace
     * when the application has no global one, so that the core classes that extend it work.
     *
     * @dataProvider appClassFromAppNamespaceProvider
     * @param bool $cached Whether the file of the class is in the file map that App caches
     * @return void
     */
    public function testLoadAppClassFromAppNamespace($cached): void
    {
        $loader = new ReflectionClass(ClassLoader::class);
        $script = '<?php' . "\n" .
            'define("DS", DIRECTORY_SEPARATOR);' . "\n" .
            'define("ROOT", ' . var_export(ROOT, true) . ');' . "\n" .
            'define("APP_DIR", ' . var_export(APP_DIR, true) . ');' . "\n" .
            'define("WWW_ROOT", ' . var_export(WWW_ROOT, true) . ');' . "\n" .
            'require ' . var_export(dirname($loader->getFileName(), 2) . DS . 'autoload.php', true) . ';' . "\n" .
            'require ' . var_export(CORE_ROOT . DS . 'config' . DS . 'init.php', true) . ';' . "\n" .
            'require CAKE . "bootstrap.php";' . "\n" .
            'Configure::write("App.namespace", "TestApp");' . "\n" .
            'App::build(["Controller" => [' . var_export(CORE_TESTS . DS . 'test_app' . DS . 'src' . DS . 'Controller' . DS, true) . ']], App::RESET);' . "\n" .
            // The test app has a global AppController in the classmap; load it with App::load() like an application without one
            'spl_autoload_register(function ($class) { if ($class === "AppController") { App::load($class); } }, true, true);' . "\n" .
            // The file map that App caches is neither read nor written
            'Closure::bind(function () { static::$_map = []; }, null, App::class)();' . "\n" .
            ($cached ? 'Closure::bind(function () { static::$_map["AppController"] = ' . var_export(CORE_TESTS . DS . 'test_app' . DS . 'src' . DS . 'Controller' . DS . 'AppController.php', true) . '; }, null, App::class)();' . "\n" : '') .
            'echo (new ReflectionClass("AppController"))->getName(), "\n";' . "\n" .
            'echo get_parent_class("CakeErrorController"), "\n";' . "\n" .
            'Closure::bind(function () { static::$_cacheChange = false; static::$_objectCacheChange = false; }, null, App::class)();' . "\n";
        $file = TMP . 'app_class_from_app_namespace.php';
        file_put_contents($file, $script);
        try {
            $process = proc_open([PHP_BINARY, '-d', 'display_errors=stderr', $file], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
            $output = stream_get_contents($pipes[1]);
            $errors = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            proc_close($process);
        } finally {
            unlink($file);
        }

        // CakeErrorController of the core extends it
        $this->assertSame("TestApp\\Controller\\AppController\nTestApp\\Controller\\AppController\n", $output, $errors);
    }

    /**
     * Test that App::className() looks in the namespace of a plugin.
     *
     * @return void
     */
    public function testClassNamePluginNamespace(): void
    {
        Configure::write('App.namespace', 'Other');

        $result = App::className('TestApp.Namespaced', 'View/Helper', 'Helper');
        $this->assertSame(NamespacedHelper::class, $result);
    }

    /**
     * Test that App::path() finds the src/, templates/, resources/locales/ and vendor/
     * directories of a plugin before the legacy directories.
     *
     * @return void
     */
    public function testPathWithModernPluginLayout(): void
    {
        $basepath = CORE_TESTS . DS . 'test_app' . DS . 'plugins' . DS;
        App::build([
            'Plugin' => [$basepath],
        ]);
        CakePlugin::load('TestModernPlugin');
        $pluginPath = $basepath . 'TestModernPlugin' . DS;

        $result = App::path('Controller', 'TestModernPlugin');
        $expected = [
            $pluginPath . 'src' . DS . 'Controller' . DS,
            $pluginPath . 'Controller' . DS,
        ];
        $this->assertSame($expected, $result);

        $result = App::path('View', 'TestModernPlugin');
        $expected = [
            $pluginPath . 'templates' . DS,
            $pluginPath . 'View' . DS,
        ];
        $this->assertSame($expected, $result);

        $result = App::path('Locale', 'TestModernPlugin');
        $expected = [
            $pluginPath . 'resources' . DS . 'locales' . DS,
            $pluginPath . 'Locale' . DS,
        ];
        $this->assertSame($expected, $result);

        $result = App::path('Vendor', 'TestModernPlugin');
        $this->assertSame($pluginPath . 'vendor' . DS, $result[0]);
        $this->assertNotContains($pluginPath . 'src' . DS . 'Vendor' . DS, $result);

        $result = App::path('View/Helper', 'TestModernPlugin');
        $expected = [
            $pluginPath . 'src' . DS . 'View' . DS . 'Helper' . DS,
            $pluginPath . 'View' . DS . 'Helper' . DS,
        ];
        $this->assertSame($expected, $result);
    }

    /**
     * Test that objects and vendor files are found in a plugin with the modern layout.
     *
     * @return void
     */
    public function testModernPluginLayoutObjects(): void
    {
        App::build([
            'plugins' => [CORE_TESTS . DS . 'test_app' . DS . 'plugins' . DS],
        ], App::RESET);
        CakePlugin::load('TestModernPlugin');

        $result = App::objects('TestModernPlugin.Controller/Component');
        $this->assertSame(['ModernComponent'], $result);

        $this->assertTrue(App::import('Vendor', 'TestModernPlugin.sample/modern_vendor'));
        $this->assertTrue(defined('TEST_PLUGIN_MODERN_VENDOR_LOADED'));
    }

    /**
     * App::uses() still registers the package of the class when the logger of its deprecation throws,
     * and still triggers the deprecation in debug.
     *
     * @return void
     */
    public function testUsesWhenDeprecationLoggerThrows(): void
    {
        Configure::write('debug', 2);
        $this->_setUsesReported(false);
        AppTestThrowingLog::$calls = 0;
        CakeLog::config('app_test_throwing', ['engine' => AppTestThrowingLog::class]);

        $errors = $this->_collectDeprecations(function () {
            App::uses('AppTestUsesThrowingLogger', 'MyPackage/Logged');
        });

        $this->assertSame(1, AppTestThrowingLog::$calls, 'The deprecation is logged once.');
        $this->assertSame('MyPackage/Logged', App::location('AppTestUsesThrowingLogger'));
        $this->assertCount(1, $errors);
        $this->assertStringStartsWith('App::uses() is deprecated (called in ' . __FILE__ . ' on line ', $errors[0]);
    }

    /**
     * App::location() returns null for a class whose package was never declared.
     *
     * @return void
     */
    public function testClassLocationUndeclared(): void
    {
        $this->assertNull(App::location('AppTestNeverDeclaredClass'));
    }

    /**
     * App::pluginPath() returns the same path as CakePlugin::path().
     *
     * @return void
     */
    public function testPluginPath(): void
    {
        App::build([
            'Plugin' => [CORE_TESTS . DS . 'test_app' . DS . 'plugins' . DS],
        ], App::RESET);
        CakePlugin::load('TestPlugin');

        $expected = CORE_TESTS . DS . 'test_app' . DS . 'plugins' . DS . 'TestPlugin' . DS;
        $this->assertSame($expected, App::pluginPath('TestPlugin'));
        $this->assertSame(CakePlugin::path('TestPlugin'), App::pluginPath('TestPlugin'));
        App::build();
    }

    /**
     * App::themePath() returns the theme directory under the first View path
     * when no View path contains the theme.
     *
     * @return void
     */
    public function testThemePathOfMissingTheme(): void
    {
        $first = CORE_TESTS . DS . 'test_app' . DS . 'src' . DS . 'View' . DS;
        App::build([
            'View' => [$first, CORE_TESTS . DS . 'test_app' . DS . 'templates' . DS],
        ], App::RESET);

        $this->assertSame(
            $first . 'Themed' . DS . 'NoSuchTheme' . DS,
            App::themePath('no_such_theme'),
        );
        App::build();
    }

    /**
     * App::load() returns true for a class that already exists, and false for a class
     * that is not declared in the class map.
     *
     * @return void
     */
    public function testLoadExistingAndUnknownClass(): void
    {
        $this->assertTrue(App::load(App::class));
        $this->assertFalse(App::load('AppTestNeverDeclaredClass'));
    }

    /**
     * App::load() refuses a class name with "..", so that a class name cannot include
     * a file outside of the package paths.
     *
     * @return void
     */
    public function testLoadRefusesPathTraversal(): void
    {
        App::build([
            'Lib' => [CORE_TESTS . DS . 'test_app' . DS . 'src' . DS . 'Lib' . DS],
        ], App::RESET);
        // test_app/src/Lib/../../vendor/welcome.php exists and prints a text when it is included.
        $className = '..' . DS . '..' . DS . 'vendor' . DS . 'welcome';
        $this->assertFileExists(CORE_TESTS . DS . 'test_app' . DS . 'src' . DS . 'Lib' . DS . $className . '.php');
        $this->_setUsesReported(true);
        App::uses($className, 'Lib');

        ob_start();
        $result = App::load($className);
        $output = ob_get_clean();

        $this->assertFalse($result);
        $this->assertSame('', $output);
        App::build();
    }

    /**
     * App::import('File') needs a file name: a name alone does not load anything.
     *
     * @return void
     */
    public function testImportFileWithoutFileName(): void
    {
        $this->assertFalse(App::import('File', 'AppTestFileWithoutName'));
    }

    /**
     * App::import('File') loads the file that was mapped to a name the first time,
     * even when the same name is imported later with another file.
     *
     * @return void
     */
    public function testImportFileUsesTheMappedFile(): void
    {
        $config = CORE_ROOT . DS . 'config' . DS . 'config.php';
        $first = App::import('File', 'AppTestMappedConfig', false, [], $config, true);
        $this->assertArrayHasKey('Cake.version', $first);

        $second = App::import('File', 'AppTestMappedConfig', false, [], TMP . 'app_test_no_such_file.php', true);
        $this->assertSame($first, $second);
    }

    /**
     * App::import('Vendor') includes a mapped vendor file only once, and returns false
     * for a vendor file that does not exist.
     *
     * @return void
     */
    public function testImportVendorMappedAndMissing(): void
    {
        App::build([
            'vendors' => [CORE_TESTS . DS . 'test_app' . DS . 'vendor' . DS],
        ], App::RESET);

        ob_start();
        $first = App::import('Vendor', 'AppTestMappedHello', ['file' => 'Test' . DS . 'hello.php']);
        $firstOutput = ob_get_clean();
        $this->assertTrue($first);
        $this->assertSame('This is the hello.php file in Test directory', $firstOutput);

        ob_start();
        $second = App::import('Vendor', 'AppTestMappedHello', ['file' => 'Test' . DS . 'hello.php']);
        $secondOutput = ob_get_clean();
        $this->assertTrue($second);
        $this->assertSame('', $secondOutput, 'The mapped file is not run again');

        $this->assertFalse(App::import('Vendor', 'AppTestNoSuchVendor'));
        $this->assertFalse(App::import('Vendor', 'AppTestNoSuchVendorFile', ['file' => 'no' . DS . 'such.php']));
        App::build();
    }

    /**
     * shutdown() saves the file map and the object map in the core cache, so that the next
     * request finds the mapped files and the objects without searching the paths again.
     *
     * The next request is simulated by emptying the maps of App and calling App::init(),
     * after the listed file is deleted, so that only the cache can give the old results.
     *
     * @return void
     */
    public function testShutdownWritesMapsToCache(): void
    {
        $base = TMP . 'app_test_shutdown_' . getmypid() . DS;
        $cacheDir = $base . 'cache' . DS;
        $libDir = $base . 'Lib' . DS;
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0777, true);
        }
        if (!is_dir($libDir)) {
            mkdir($libDir, 0777, true);
        }
        file_put_contents($libDir . 'Listed.php', '<?php' . PHP_EOL);
        $configFile = $base . 'config.php';
        file_put_contents($configFile, "<?php\nreturn ['AppTest' => 'shutdown'];\n");

        $maps = $this->_appMaps();
        $old = Cache::settings('_cake_core_');
        Cache::drop('_cake_core_');
        Cache::config('_cake_core_', ['engine' => 'File', 'path' => $cacheDir, 'prefix' => 'app_test_', 'serialize' => true]);
        Configure::write('Error.extraFatalErrorMemory', false);
        try {
            $this->assertSame(['AppTest' => 'shutdown'], App::import('File', 'AppTestShutdownConfig', false, [], $configFile, true));
            $this->assertSame(['Listed'], App::objects('file', $libDir));

            error_clear_last();
            App::shutdown();

            unlink($libDir . 'Listed.php');
            $this->_setAppMaps([[], [], false, false]);
            App::init();

            $this->assertSame(['Listed'], App::objects('file', $libDir), 'The object list comes from the cache.');
            $this->assertSame(
                ['AppTest' => 'shutdown'],
                App::import('File', 'AppTestShutdownConfig', false, [], $base . 'no_such_file.php', true),
                'The mapped file comes from the cache.',
            );
        } finally {
            $this->_setAppMaps($maps);
            Cache::clear(false, '_cake_core_');
            Cache::drop('_cake_core_');
            (new Folder($base))->delete();
            if ($old) {
                Cache::config('_cake_core_', $old);
            }
        }
    }
}

/**
 * A log engine that always fails.
 *
 * @package       Cake.Test.Case.Core
 */
class AppTestThrowingLog extends BaseLog
{
    /**
     * The number of calls to write().
     *
     * @var int
     */
    public static int $calls = 0;

    /**
     * Throws.
     *
     * @param string $type The type of log.
     * @param string $message The message.
     * @return void
     * @throws \RuntimeException
     */
    public function write($type, $message): void
    {
        static::$calls++;

        throw new RuntimeException('The log is not available');
    }
}
