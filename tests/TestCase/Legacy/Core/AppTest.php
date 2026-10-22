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

namespace Cake\Test\TestCase\Legacy\Core;

use AppController;
use AppModel;
use BananaHelper;
use Cake\Configure\PhpReader;
use Cake\Console\Shell;
use Cake\Controller\Component\AuthComponent;
use Cake\Controller\Component\RequestHandlerComponent;
use Cake\Controller\Controller;
use Cake\Core\App;
use Cake\Core\CakePlugin;
use Cake\Model\Behavior\ContainableBehavior;
use Cake\Model\Datasource\DboSource;
use Cake\Model\Model;
use Cake\TestSuite\CakeTestCase;
use Cake\View\Helper\FormHelper;
use ConfigureTestVendorSample;
use CustomLibClass;
use ExampleExample;
use Library;
use OtherHelperHelper;
use PagesController;
use PersisterOne;
use PersisterTwo;
use SamplePluginClassTestName;
use TestLegacyPluginAppController;
use TestLegacyPluginAppHelper;
use TestPluginLibrary;
use TestsController;
use TestSource;
use TestUtilityClass;

/**
 * AppTest class
 *
 * @package       Cake.Test.Case.Core
 */
class AppTest extends CakeTestCase
{
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
        ]);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        CakePlugin::unload();

        parent::tearDown();
    }

    /**
     * test path() with a plugin.
     *
     * @return void
     */
    public function testPathWithPlugins(): void
    {
        $basepath = CORE_TESTS . DS . 'test_legacy_app' . DS . 'Plugin' . DS;
        App::build([
            'Plugin' => [$basepath],
        ]);
        CakePlugin::load('TestLegacyPlugin');

        $result = App::path('Vendor', 'TestLegacyPlugin');
        $this->assertContains($basepath . 'TestLegacyPlugin' . DS . 'Vendor' . DS, $result);
    }

    /**
     * Test that App::path() finds the src/, templates/, resources/locales/ and vendor/
     * directories of a plugin before the legacy directories.
     *
     * @return void
     */
    public function testPathWithModernPluginLayout(): void
    {
        $basepath = CORE_TESTS . DS . 'test_legacy_app' . DS . 'plugins' . DS;
        App::build([
            'Plugin' => [$basepath],
        ]);
        CakePlugin::load('TestNamespacedPlugin');
        $pluginPath = $basepath . 'TestNamespacedPlugin' . DS;

        $result = App::path('Controller', 'TestNamespacedPlugin');
        $expected = [
            $pluginPath . 'src' . DS . 'Controller' . DS,
            $pluginPath . 'Controller' . DS,
        ];
        $this->assertSame($expected, $result);

        $result = App::path('View', 'TestNamespacedPlugin');
        $expected = [
            $pluginPath . 'templates' . DS,
            $pluginPath . 'View' . DS,
        ];
        $this->assertSame($expected, $result);

        $result = App::path('Locale', 'TestNamespacedPlugin');
        $expected = [
            $pluginPath . 'resources' . DS . 'locales' . DS,
            $pluginPath . 'Locale' . DS,
        ];
        $this->assertSame($expected, $result);

        $result = App::path('Vendor', 'TestNamespacedPlugin');
        $this->assertSame($pluginPath . 'vendor' . DS, $result[0]);
        $this->assertNotContains($pluginPath . 'src' . DS . 'Vendor' . DS, $result);

        $result = App::path('View/Helper', 'TestNamespacedPlugin');
        $expected = [
            $pluginPath . 'src' . DS . 'View' . DS . 'Helper' . DS,
            $pluginPath . 'View' . DS . 'Helper' . DS,
        ];
        $this->assertSame($expected, $result);
    }

    /**
     * Test that App::path() does not add the src/ or templates/ directory of a plugin
     * that does not have it.
     *
     * @return void
     */
    public function testPathWithLegacyPluginLayout(): void
    {
        $basepath = CORE_TESTS . DS . 'test_legacy_app' . DS . 'Plugin' . DS;
        App::build([
            'Plugin' => [$basepath],
        ]);
        CakePlugin::load('TestLegacyPlugin');

        $result = App::path('View', 'TestLegacyPlugin');
        $this->assertSame([$basepath . 'TestLegacyPlugin' . DS . 'View' . DS], $result);
    }

    /**
     * Tests listing objects within a plugin
     *
     * @return void
     */
    public function testListObjectsInPlugin(): void
    {
        App::build([
            'Model' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Model' . DS],
            'plugins' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Plugin' . DS],
        ], App::RESET);
        CakePlugin::load(['TestLegacyPlugin', 'TestTwoLegacyPlugin']);

        $result = App::objects('TestLegacyPlugin.model');
        $this->assertTrue(in_array('TestPluginPost', $result));
        $result = App::objects('TestLegacyPlugin.Model');
        $this->assertTrue(in_array('TestPluginPost', $result));

        $result = App::objects('TestLegacyPlugin.behavior');
        $this->assertTrue(in_array('TestPluginPersisterOneBehavior', $result));
        $result = App::objects('TestLegacyPlugin.Model/Behavior');
        $this->assertTrue(in_array('TestPluginPersisterOneBehavior', $result));

        $result = App::objects('TestLegacyPlugin.helper');
        $expected = ['OtherHelperHelper', 'PluggedHelperHelper', 'TestLegacyPluginAppHelper'];
        $this->assertEquals($expected, $result);
        $result = App::objects('TestLegacyPlugin.View/Helper');
        $expected = ['OtherHelperHelper', 'PluggedHelperHelper', 'TestLegacyPluginAppHelper'];
        $this->assertEquals($expected, $result);

        $result = App::objects('TestLegacyPlugin.component');
        $this->assertTrue(in_array('OtherComponent', $result));
        $result = App::objects('TestLegacyPlugin.Controller/Component');
        $this->assertTrue(in_array('OtherComponent', $result));

        $result = App::objects('TestTwoLegacyPlugin.behavior');
        $this->assertSame([], $result);
        $result = App::objects('TestTwoLegacyPlugin.Model/Behavior');
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
            'View' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'View' . DS],
        ]);
        $path = App::themePath('test_theme');
        $expected = CORE_TESTS . DS . 'test_legacy_app' . DS . 'View' . DS . 'Themed' . DS . 'TestTheme' . DS;
        $this->assertEquals($expected, $path);

        $path = App::themePath('TestTheme');
        $expected = CORE_TESTS . DS . 'test_legacy_app' . DS . 'View' . DS . 'Themed' . DS . 'TestTheme' . DS;
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
            'Lib' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Lib' . DS],
            'Plugin' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Plugin' . DS],
        ]);
        CakePlugin::load(['TestLegacyPlugin', 'TestTwoLegacyPlugin']);

        $result = App::import('Controller', 'TestLegacyPlugin.Tests');
        $this->assertTrue($result);
        $this->assertTrue(class_exists(TestLegacyPluginAppController::class));
        $this->assertTrue(class_exists(TestsController::class));

        $result = App::import('Lib', 'TestLegacyPlugin.TestPluginLibrary');
        $this->assertTrue($result);
        $this->assertTrue(class_exists(TestPluginLibrary::class));

        $result = App::import('Lib', 'Library');
        $this->assertTrue($result);
        $this->assertTrue(class_exists(Library::class));

        $result = App::import('Helper', 'TestLegacyPlugin.OtherHelper');
        $this->assertTrue($result);
        $this->assertTrue(class_exists(OtherHelperHelper::class));

        $result = App::import('Helper', 'TestLegacyPlugin.TestLegacyPluginApp');
        $this->assertTrue($result);
        $this->assertTrue(class_exists(TestLegacyPluginAppHelper::class));

        $result = App::import('Datasource', 'TestLegacyPlugin.TestSource');
        $this->assertTrue($result);
        $this->assertTrue(class_exists(TestSource::class));

        App::uses('ExampleExample', 'TestLegacyPlugin.Vendor/Example');
        $this->assertTrue(class_exists(ExampleExample::class));

        App::build();
    }

    /**
     * test that building helper paths actually works.
     *
     * It runs in its own process, as PHP cannot unload the classes and files it loaded, e.g. with --repeat.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @return void
     */
    public function testImportingHelpersFromAlternatePaths(): void
    {
        $this->assertFalse(class_exists(BananaHelper::class, false), 'BananaHelper exists, cannot test importing it.');
        App::build([
            'View/Helper' => [
                CORE_TESTS . DS . 'test_legacy_app' . DS . 'View' . DS . 'Helper' . DS,
            ],
        ]);
        $this->assertFalse(class_exists(BananaHelper::class, false), 'BananaHelper exists, cannot test importing it.');
        App::import('Helper', 'Banana');
        $this->assertTrue(class_exists(BananaHelper::class, false), 'BananaHelper was not loaded.');

        App::build();
    }

    /**
     * testMultipleLoading method
     *
     * It runs in its own process, as PHP cannot unload the classes and files it loaded, e.g. with --repeat.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @return void
     */
    public function testMultipleLoading(): void
    {
        if (class_exists(PersisterOne::class, false) || class_exists(PersisterTwo::class, false)) {
            $this->markTestSkipped('Cannot test loading of classes that exist.');
        }
        App::build([
            'Model' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Model' . DS],
        ]);
        $toLoad = ['PersisterOne', 'PersisterTwo'];
        $load = App::import('Model', $toLoad);
        $this->assertTrue($load);

        $classes = array_flip(get_declared_classes());

        $this->assertTrue(isset($classes['PersisterOne']));
        $this->assertTrue(isset($classes['PersisterTwo']));

        $load = App::import('Model', ['PersisterOne', 'SomeNotFoundClass', 'PersisterTwo']);
        $this->assertFalse($load);
    }

    /**
     * Test loading vendor files with App::import()
     *
     * It runs in its own process, as PHP cannot unload the classes and files it loaded, e.g. with --repeat.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @return void
     */
    public function testLoadingVendor(): void
    {
        App::build([
            'plugins' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Plugin' . DS],
            'vendors' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Vendor' . DS],
        ], App::RESET);
        CakePlugin::load(['TestLegacyPlugin', 'TestTwoLegacyPlugin']);

        ob_start();
        $result = App::import('Vendor', 'css/TestAsset', ['ext' => 'css']);
        $text = ob_get_clean();
        $this->assertTrue($result);
        $this->assertEquals('/* this is the test asset css file */', trim($text));

        $result = App::import('Vendor', 'TestLegacyPlugin.sample/SamplePlugin');
        $this->assertTrue($result);
        $this->assertTrue(class_exists(SamplePluginClassTestName::class));

        $result = App::import('Vendor', 'sample/ConfigureTestVendorSample');
        $this->assertTrue($result);
        $this->assertTrue(class_exists(ConfigureTestVendorSample::class));

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
        $result = App::import('Vendor', 'TestLegacyPlugin.Welcome');
        $text = ob_get_clean();
        $this->assertTrue($result);
        $this->assertEquals('This is the welcome.php file in test_legacy_plugin/vendors directory', $text);
    }

    /**
     * Tests that the automatic class loader will also find in "libs" folder for both
     * app and plugins if it does not find the class in other configured paths
     *
     * It runs in its own process, as PHP cannot unload the classes and files it loaded, e.g. with --repeat.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @return void
     */
    public function testLoadClassInLibs(): void
    {
        App::build([
            'libs' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Lib' . DS],
            'plugins' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Plugin' . DS],
        ], App::RESET);
        CakePlugin::load(['TestLegacyPlugin', 'TestTwoLegacyPlugin']);

        $this->assertFalse(class_exists(CustomLibClass::class, false));
        App::uses('CustomLibClass', 'TestLegacyPlugin.Custom/Package');
        $this->assertTrue(class_exists(CustomLibClass::class));

        $this->assertFalse(class_exists(TestUtilityClass::class, false));
        App::uses('TestUtilityClass', 'Utility');
        $this->assertTrue(class_exists(TestUtilityClass::class));
    }

    /**
     * Proves that it is possible to load plugin libraries in top
     * level Lib dir for plugins
     *
     * It runs in its own process, as PHP cannot unload the classes and files it loaded, e.g. with --repeat.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @return void
     */
    public function testPluginLibClasses(): void
    {
        App::build([
            'plugins' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Plugin' . DS],
        ], App::RESET);
        CakePlugin::load(['TestLegacyPlugin', 'TestTwoLegacyPlugin']);
        $this->assertFalse(class_exists('TestPluginOtherLibrary', false));
        App::uses('TestPluginOtherLibrary', 'TestLegacyPlugin.Lib');
        $this->assertTrue(class_exists('TestPluginOtherLibrary'));
    }

    /**
     * Test that objects and vendor files are found in a plugin with the modern layout.
     *
     * @return void
     */
    public function testModernPluginLayoutObjects(): void
    {
        App::build([
            'plugins' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'plugins' . DS],
        ], App::RESET);
        CakePlugin::load('TestNamespacedPlugin');

        $result = App::objects('TestNamespacedPlugin.Controller/Component');
        $this->assertSame(['ModernComponent'], $result);

        $this->assertTrue(App::import('Vendor', 'TestNamespacedPlugin.sample/modern_vendor'));
        $this->assertTrue(defined('TEST_PLUGIN_MODERN_VENDOR_LOADED'));
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

        App::build(['Plugin' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Plugin' . DS]], App::RESET);
        CakePlugin::load('TestLegacyPlugin');
        $this->assertSame('OtherComponent', App::className('TestLegacyPlugin.Other', 'Controller/Component', 'Component'));
    }
}
