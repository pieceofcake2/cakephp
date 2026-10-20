<?php
/**
 * ViewTest file
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
 * @package       Cake.Test.Case.View
 * @since         CakePHP(tm) v 1.2.0.4206
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Test\TestCase\Legacy\View;

use Cake\Controller\Controller;
use Cake\Core\App;
use Cake\Core\CakePlugin;
use Cake\Core\Configure;
use Cake\Error\MissingLayoutException;
use Cake\Error\MissingViewException;
use Cake\Network\CakeRequest;
use Cake\TestSuite\CakeTestCase;
use Cake\View\View;

/**
 * ViewPostsController class
 *
 * @package       Cake.Test.Case.View
 */
class ViewPostsController extends Controller
{
    /**
     * name property
     *
     * @var string
     */
    public $name = 'Posts';

    /**
     * uses property
     *
     * @var mixed
     */
    public $uses = null;

    /**
     * index method
     *
     * @return void
     */
    public function index(): void
    {
        $this->set([
            'testData' => 'Some test data',
            'test2' => 'more data',
            'test3' => 'even more data',
        ]);
    }

    /**
     * nocache_tags_with_element method
     *
     * @return void
     */
    public function nocache_multiple_element(): void
    {
        $this->set('foo', 'this is foo var');
        $this->set('bar', 'this is bar var');
    }
}

/**
 * ThemePostsController class
 *
 * @package       Cake.Test.Case.View
 */
class ThemePostsController extends Controller
{
    public $theme = null;

    /**
     * index method
     *
     * @return void
     */
    public function index(): void
    {
        $this->set('testData', 'Some test data');
        $test2 = 'more data';
        $test3 = 'even more data';
        $this->set(compact('test2', 'test3'));
    }
}

/**
 * TestThemeView class
 *
 * @package       Cake.Test.Case.View
 */
class TestThemeView extends View
{
    /**
     * renderElement method
     *
     * @param string $name
     * @param array $params
     * @return string The given name
     */
    public function renderElement($name, $params = []): mixed
    {
        return $name;
    }

    /**
     * getViewFileName method
     *
     * @param string $name Controller action to find template filename for
     * @return string Template filename
     */
    public function getViewFileName($name = null): mixed
    {
        return $this->_getViewFileName($name);
    }

    /**
     * getLayoutFileName method
     *
     * @param string $name The name of the layout to find.
     * @return string Filename for layout file (.ctp).
     */
    public function getLayoutFileName($name = null): mixed
    {
        return $this->_getLayoutFileName($name);
    }
}

/**
 * TestView class
 *
 * @package       Cake.Test.Case.View
 */
class TestView extends View
{
    /**
     * getViewFileName method
     *
     * @param string $name Controller action to find template filename for
     * @return string Template filename
     */
    public function getViewFileName($name = null): mixed
    {
        return $this->_getViewFileName($name);
    }

    /**
     * getLayoutFileName method
     *
     * @param string $name The name of the layout to find.
     * @return string Filename for layout file (.ctp).
     */
    public function getLayoutFileName($name = null): mixed
    {
        return $this->_getLayoutFileName($name);
    }

    /**
     * paths method
     *
     * @param string $plugin Optional plugin name to scan for view files.
     * @param bool $cached Set to true to force a refresh of view paths.
     * @return array paths
     */
    public function paths($plugin = null, $cached = true): mixed
    {
        return $this->_paths($plugin, $cached);
    }

    /**
     * Test only function to return instance scripts.
     *
     * @return array Scripts
     */
    public function scripts(): mixed
    {
        return $this->_scripts;
    }
}

/**
 * ViewTest class
 *
 * @package       Cake.Test.Case.View
 */
class ViewTest extends CakeTestCase
{
    /**
     * Fixtures used in this test.
     *
     * @var array
     */
    public $fixtures = ['core.user', 'core.post'];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $request = $this->getMock('CakeRequest');
        $this->Controller = new Controller($request);
        $this->PostsController = new ViewPostsController($request);
        $this->PostsController->viewPath = 'Posts';
        $this->PostsController->index();
        $this->View = new View($this->PostsController);

        $themeRequest = new CakeRequest('posts/index');
        $this->ThemeController = new Controller($themeRequest);
        $this->ThemePostsController = new ThemePostsController($themeRequest);
        $this->ThemePostsController->viewPath = 'posts';
        $this->ThemePostsController->index();
        $this->ThemeView = new View($this->ThemePostsController);

        App::build([
            'Plugin' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Plugin' . DS],
            'View' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'View' . DS],
            'View/Helper' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'View' . DS . 'Helper' . DS],
        ], App::RESET);
        App::objects('plugins', null, false);

        CakePlugin::load(['TestLegacyPlugin', 'TestLegacyPlugin', 'TestJsLegacyPlugin']);
        Configure::write('debug', 2);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        CakePlugin::unload();
        unset($this->View);
        unset($this->PostsController);
        unset($this->Controller);
        unset($this->ThemeView);
        unset($this->ThemePostsController);
        unset($this->ThemeController);

        parent::tearDown();
    }

    /**
     * Test getViewFileName method
     *
     * @return void
     */
    public function testGetTemplate(): void
    {
        $this->Controller->plugin = null;
        $this->Controller->name = 'Pages';
        $this->Controller->viewPath = 'Pages';
        $this->Controller->action = 'display';
        $this->Controller->params['pass'] = ['home'];

        $ThemeView = new TestThemeView($this->Controller);
        $ThemeView->theme = 'test_theme';
        $expected = CORE_TESTS . DS . 'test_legacy_app' . DS . 'View' . DS . 'Pages' . DS . 'home.ctp';
        $result = $ThemeView->getViewFileName('home');
        $this->assertEquals($expected, $result);

        $expected = CORE_TESTS . DS . 'test_legacy_app' . DS . 'View' . DS . 'Themed' . DS . 'TestTheme' . DS . 'Posts' . DS . 'index.ctp';
        $result = $ThemeView->getViewFileName('/Posts/index');
        $this->assertEquals($expected, $result);

        $ThemeView->theme = 'TestTheme';
        $expected = CORE_TESTS . DS . 'test_legacy_app' . DS . 'View' . DS . 'Themed' . DS . 'TestTheme' . DS . 'Layouts' . DS . 'default.ctp';
        $result = $ThemeView->getLayoutFileName();
        $this->assertEquals($expected, $result);

        $ThemeView->layoutPath = 'rss';
        $expected = CORE_TESTS . DS . 'test_legacy_app' . DS . 'View' . DS . 'Layouts' . DS . 'rss' . DS . 'default.ctp';
        $result = $ThemeView->getLayoutFileName();
        $this->assertEquals($expected, $result);

        $ThemeView->layoutPath = 'Emails' . DS . 'html';
        $expected = CORE_TESTS . DS . 'test_legacy_app' . DS . 'View' . DS . 'Layouts' . DS . 'Emails' . DS . 'html' . DS . 'default.ctp';
        $result = $ThemeView->getLayoutFileName();
        $this->assertEquals($expected, $result);
    }

    /**
     * Test that plugin files with absolute file paths are scoped
     * to the plugin and do now allow any file path.
     *
     * @return void
     */
    public function testPluginGetTemplateAbsoluteFail(): void
    {
        $this->expectException(MissingViewException::class);
        $this->Controller->viewPath = 'Pages';
        $this->Controller->action = 'display';
        $this->Controller->params['pass'] = ['home'];

        $view = new TestThemeView($this->Controller);
        $expected = CORE_TESTS . DS . 'test_legacy_app' . DS . 'Plugin' . DS . 'TestLegacyPlugin' . DS . 'View' . DS . 'Tests' . DS . 'index.ctp';
        $result = $view->getViewFileName('TestLegacyPlugin./Tests/index');
        $this->assertEquals($expected, $result);

        $view->getViewFileName('TestLegacyPlugin./etc/passwd');
    }

    /**
     * Test getLayoutFileName method on plugin
     *
     * @return void
     */
    public function testPluginGetTemplate(): void
    {
        $this->Controller->plugin = 'TestLegacyPlugin';
        $this->Controller->name = 'TestLegacyPlugin';
        $this->Controller->viewPath = 'Tests';
        $this->Controller->action = 'index';

        $View = new TestView($this->Controller);

        $expected = CakePlugin::path('TestLegacyPlugin') . 'View' . DS . 'Tests' . DS . 'index.ctp';
        $result = $View->getViewFileName('index');
        $this->assertEquals($expected, $result);

        $expected = CakePlugin::path('TestLegacyPlugin') . 'View' . DS . 'Layouts' . DS . 'default.ctp';
        $result = $View->getLayoutFileName();
        $this->assertEquals($expected, $result);
    }

    /**
     * Test getViewFileName method on plugin
     *
     * @return void
     */
    public function testPluginThemedGetTemplate(): void
    {
        $this->Controller->plugin = 'TestLegacyPlugin';
        $this->Controller->name = 'TestLegacyPlugin';
        $this->Controller->viewPath = 'Tests';
        $this->Controller->action = 'index';
        $this->Controller->theme = 'TestTheme';

        $ThemeView = new TestThemeView($this->Controller);
        $expected = CORE_TESTS . DS . 'test_legacy_app' . DS . 'View' . DS . 'Themed' . DS . 'TestTheme' . DS . 'Plugin' . DS . 'TestLegacyPlugin' . DS . 'Tests' . DS . 'index.ctp';
        $result = $ThemeView->getViewFileName('index');
        $this->assertEquals($expected, $result);

        $expected = CORE_TESTS . DS . 'test_legacy_app' . DS . 'View' . DS . 'Themed' . DS . 'TestTheme' . DS . 'Plugin' . DS . 'TestLegacyPlugin' . DS . 'Layouts' . DS . 'plugin_default.ctp';
        $result = $ThemeView->getLayoutFileName('plugin_default');
        $this->assertEquals($expected, $result);

        $expected = CORE_TESTS . DS . 'test_legacy_app' . DS . 'View' . DS . 'Themed' . DS . 'TestTheme' . DS . 'Layouts' . DS . 'default.ctp';
        $result = $ThemeView->getLayoutFileName('default');
        $this->assertEquals($expected, $result);
    }

    /**
     * Test that plugin/$plugin_name is only appended to the paths it should be.
     *
     * @return void
     */
    public function testPluginPathGeneration(): void
    {
        $this->Controller->plugin = 'TestLegacyPlugin';
        $this->Controller->name = 'TestLegacyPlugin';
        $this->Controller->viewPath = 'Tests';
        $this->Controller->action = 'index';

        $View = new TestView($this->Controller);
        $paths = $View->paths();
        $expected = array_merge(App::path('View'), [CORE_ROOT . DS . 'templates' . DS]);
        $this->assertEquals($expected, $paths);

        $paths = $View->paths('TestLegacyPlugin');
        $pluginPath = CakePlugin::path('TestLegacyPlugin');
        $expected = [
            CORE_TESTS . DS . 'test_legacy_app' . DS . 'View' . DS . 'Plugin' . DS . 'TestLegacyPlugin' . DS,
            $pluginPath . 'View' . DS,
            CORE_TESTS . DS . 'test_legacy_app' . DS . 'View' . DS,
            CORE_ROOT . DS . 'templates' . DS,
        ];
        $this->assertEquals($expected, $paths);
    }

    /**
     * Test getViewFileName method
     *
     * @return void
     */
    public function testGetViewFileNames(): void
    {
        $this->Controller->plugin = null;
        $this->Controller->name = 'Pages';
        $this->Controller->viewPath = 'Pages';
        $this->Controller->action = 'display';
        $this->Controller->params['pass'] = ['home'];

        $View = new TestView($this->Controller);

        $expected = CORE_TESTS . DS . 'test_legacy_app' . DS . 'View' . DS . 'Pages' . DS . 'home.ctp';
        $result = $View->getViewFileName('home');
        $this->assertEquals($expected, $result);

        $expected = CORE_TESTS . DS . 'test_legacy_app' . DS . 'View' . DS . 'Posts' . DS . 'index.ctp';
        $result = $View->getViewFileName('/Posts/index');
        $this->assertEquals($expected, $result);

        $expected = CORE_TESTS . DS . 'test_legacy_app' . DS . 'View' . DS . 'Posts' . DS . 'index.ctp';
        $result = $View->getViewFileName('../Posts/index');
        $this->assertEquals($expected, $result);

        $expected = CORE_TESTS . DS . 'test_legacy_app' . DS . 'View' . DS . 'Pages' . DS . 'page.home.ctp';
        $result = $View->getViewFileName('page.home');
        $this->assertEquals($expected, $result, 'Should not ruin files with dots.');

        CakePlugin::load('TestLegacyPlugin');
        $expected = CORE_TESTS . DS . 'test_legacy_app' . DS . 'View' . DS . 'Pages' . DS . 'home.ctp';
        $result = $View->getViewFileName('TestLegacyPlugin.home');
        $this->assertEquals($expected, $result, 'Plugin is missing the view, cascade to app.');

        $View->viewPath = 'Tests';
        $expected = CORE_TESTS . DS . 'test_legacy_app' . DS . 'Plugin' . DS . 'TestLegacyPlugin' . DS . 'View' . DS . 'Tests' . DS . 'index.ctp';
        $result = $View->getViewFileName('TestLegacyPlugin.index');
        $this->assertEquals($expected, $result);
    }

    /**
     * Test getting layout filenames
     *
     * @return void
     */
    public function testGetLayoutFileName(): void
    {
        $this->Controller->plugin = null;
        $this->Controller->name = 'Pages';
        $this->Controller->viewPath = 'Pages';
        $this->Controller->action = 'display';

        $View = new TestView($this->Controller);

        $expected = CORE_TESTS . DS . 'test_legacy_app' . DS . 'View' . DS . 'Layouts' . DS . 'default.ctp';
        $result = $View->getLayoutFileName();
        $this->assertEquals($expected, $result);

        $View->layoutPath = 'rss';
        $expected = CORE_TESTS . DS . 'test_legacy_app' . DS . 'View' . DS . 'Layouts' . DS . 'rss' . DS . 'default.ctp';
        $result = $View->getLayoutFileName();
        $this->assertEquals($expected, $result);

        $View->layoutPath = 'Emails' . DS . 'html';
        $expected = CORE_TESTS . DS . 'test_legacy_app' . DS . 'View' . DS . 'Layouts' . DS . 'Emails' . DS . 'html' . DS . 'default.ctp';
        $result = $View->getLayoutFileName();
        $this->assertEquals($expected, $result);
    }

    /**
     * Test getting layout filenames for plugins.
     *
     * @return void
     */
    public function testGetLayoutFileNamePlugin(): void
    {
        $this->Controller->plugin = null;
        $this->Controller->name = 'Pages';
        $this->Controller->viewPath = 'Pages';
        $this->Controller->action = 'display';

        $View = new TestView($this->Controller);
        CakePlugin::load('TestLegacyPlugin');

        $expected = CORE_TESTS . DS . 'test_legacy_app' . DS . 'Plugin' . DS . 'TestLegacyPlugin' . DS . 'View' . DS . 'Layouts' . DS . 'default.ctp';
        $result = $View->getLayoutFileName('TestLegacyPlugin.default');
        $this->assertEquals($expected, $result);

        $View->plugin = 'TestLegacyPlugin';
        $expected = CORE_TESTS . DS . 'test_legacy_app' . DS . 'Plugin' . DS . 'TestLegacyPlugin' . DS . 'View' . DS . 'Layouts' . DS . 'default.ctp';
        $result = $View->getLayoutFileName('default');
        $this->assertEquals($expected, $result);
    }

    /**
     * Test for missing theme views
     *
     * @return void
     */
    public function testMissingThemeView(): void
    {
        $this->expectException(MissingViewException::class);
        $this->ThemeController->plugin = null;
        $this->ThemeController->name = 'Pages';
        $this->ThemeController->viewPath = 'Pages';
        $this->ThemeController->action = 'display';
        $this->ThemeController->theme = 'my_theme';

        $this->ThemeController->params['pass'] = ['home'];

        $View = new TestThemeView($this->ThemeController);
        $View->getViewFileName('does_not_exist');
    }

    /**
     * Test for missing theme layouts
     *
     * @return void
     */
    public function testMissingThemeLayout(): void
    {
        $this->expectException(MissingLayoutException::class);
        $this->ThemeController->plugin = null;
        $this->ThemeController->name = 'Posts';
        $this->ThemeController->viewPath = 'posts';
        $this->ThemeController->layout = 'whatever';
        $this->ThemeController->theme = 'my_theme';

        $View = new TestThemeView($this->ThemeController);
        $View->getLayoutFileName();
    }

    /**
     * Test elementExists method
     *
     * @return void
     */
    public function testElementExists(): void
    {
        $result = $this->View->elementExists('test_element');
        $this->assertTrue($result);

        $result = $this->View->elementExists('TestLegacyPlugin.plugin_element');
        $this->assertTrue($result);

        $result = $this->View->elementExists('non_existent_element');
        $this->assertFalse($result);

        $result = $this->View->elementExists('TestLegacyPlugin.element');
        $this->assertFalse($result);

        $this->View->plugin = 'TestLegacyPlugin';
        $result = $this->View->elementExists('test_plugin_element');
        $this->assertTrue($result);
    }

    /**
     * Test that element names can not resolve to files outside of the view paths.
     *
     * @return void
     */
    public function testElementOutsideViewPaths(): void
    {
        $outside = CORE_TESTS . DS . 'test_legacy_app' . DS . 'Console' . DS . 'Templates' . DS . 'test' . DS . 'views' . DS . 'admin_edit.ctp';
        $this->assertFileExists($outside);

        $this->assertFalse($this->View->elementExists('../../Console/Templates/test/views/admin_edit'));
        $this->assertFalse($this->View->elementExists('..' . DS . '..' . DS . 'Console' . DS . 'Templates' . DS . 'test' . DS . 'views' . DS . 'admin_edit'));
        $this->assertFalse($this->View->elementExists('TestLegacyPlugin.../../../../Console/Templates/test/views/admin_edit'));

        $this->assertTrue($this->View->elementExists('../Elements/test_element'), 'Paths inside the view paths are allowed.');
        $this->assertEquals('this is the test element', $this->View->element('../Elements/test_element'));
    }

    /**
     * Test that view and layout names can not resolve to files outside of the view paths.
     *
     * @return void
     */
    public function testViewAndLayoutOutsideViewPaths(): void
    {
        $this->Controller->plugin = null;
        $this->Controller->name = 'Pages';
        $this->Controller->viewPath = 'Pages';
        $this->Controller->action = 'display';

        $View = new TestView($this->Controller);

        $expected = CORE_TESTS . DS . 'test_legacy_app' . DS . 'View' . DS . 'Pages' . DS . '..' . DS . 'Posts' . DS . 'index.ctp';
        $this->assertEquals($expected, $View->getViewFileName('/Pages/../Posts/index'), 'Paths inside the view paths are allowed.');

        foreach (['../../Console/Templates/test/views/admin_edit', '/../Console/Templates/test/views/admin_edit'] as $name) {
            try {
                $View->getViewFileName($name);
                $this->fail('Expected MissingViewException for ' . $name);
            } catch (MissingViewException $e) {
                $this->assertStringContainsString('admin_edit', $e->getMessage());
            }
        }

        try {
            $View->getLayoutFileName('../../Console/Templates/test/views/admin_edit');
            $this->fail('Expected MissingLayoutException');
        } catch (MissingLayoutException $e) {
            $this->assertStringContainsString('admin_edit', $e->getMessage());
        }
    }

    /**
     * Test element method
     *
     * @return void
     */
    public function testElement(): void
    {
        $result = $this->View->element('test_element');
        $this->assertEquals('this is the test element', $result);

        $result = $this->View->element('plugin_element', [], ['plugin' => 'TestLegacyPlugin']);
        $this->assertEquals('this is the plugin element using params[plugin]', $result);

        $result = $this->View->element('plugin_element', [], ['plugin' => 'test_legacy_plugin']);
        $this->assertEquals('this is the plugin element using params[plugin]', $result);

        $result = $this->View->element('TestLegacyPlugin.plugin_element');
        $this->assertEquals('this is the plugin element using params[plugin]', $result);

        $this->View->plugin = 'TestLegacyPlugin';
        $result = $this->View->element('test_plugin_element');
        $this->assertEquals('this is the test set using View::$plugin plugin element', $result);
    }

    /**
     * Test elementInexistent2 method
     *
     * @return void
     */
    public function testElementInexistent2(): void
    {
        $noticeTriggered = false;
        $noticeMessage = '';
        set_error_handler(function ($errno, $errstr) use (&$noticeTriggered, &$noticeMessage) {
            if ($errno === E_NOTICE || $errno === E_USER_NOTICE) {
                $noticeTriggered = true;
                $noticeMessage = $errstr;

                return true;
            }

            return false;
        }, E_NOTICE | E_USER_NOTICE);

        try {
            $this->View->element('TestLegacyPlugin.plugin_element', [], ['plugin' => 'test_legacy_plugin']);
        } finally {
            restore_error_handler();
        }

        $this->assertTrue($noticeTriggered, 'Expected notice was not triggered');
        $this->assertSame('Element Not Found: TestLegacyPlugin.Elements/TestLegacyPlugin.plugin_element.ctp', $noticeMessage);
    }

    /**
     * Test elementInexistent3 method
     *
     * @return void
     */
    public function testElementInexistent3(): void
    {
        $noticeTriggered = false;
        $noticeMessage = '';
        set_error_handler(function ($errno, $errstr) use (&$noticeTriggered, &$noticeMessage) {
            if ($errno === E_NOTICE || $errno === E_USER_NOTICE) {
                $noticeTriggered = true;
                $noticeMessage = $errstr;

                return true;
            }

            return false;
        }, E_NOTICE | E_USER_NOTICE);

        try {
            $this->View->element('test_legacy_plugin.plugin_element');
        } finally {
            restore_error_handler();
        }

        $this->assertTrue($noticeTriggered, 'Expected notice was not triggered');
        $this->assertSame('Element Not Found: test_legacy_plugin.Elements/plugin_element.ctp', $noticeMessage);
    }

    /**
     * Test that ctp is used as a fallback file extension for elements
     *
     * @return void
     */
    public function testElementCtpFallback(): void
    {
        $View = new TestView($this->PostsController);
        $View->ext = '.missing';
        $element = 'test_element';
        $expected = 'this is the test element';
        $result = $View->element($element);

        $this->assertEquals($expected, $result);
    }

    /**
     * Test loadHelpers method
     *
     * @return void
     */
    public function testLoadHelpers(): void
    {
        $View = new View($this->PostsController);

        $View->helpers = ['Html', 'Form'];
        $View->loadHelpers();

        $this->assertInstanceOf('HtmlHelper', $View->Html, 'Object type is wrong.');
        $this->assertInstanceOf('FormHelper', $View->Form, 'Object type is wrong.');
    }

    /**
     * Test beforeLayout method
     *
     * @return void
     */
    public function testBeforeLayout(): void
    {
        $this->PostsController->helpers = ['Session', 'TestBeforeAfter', 'Html'];
        $View = new View($this->PostsController);
        $View->render('index');
        $this->assertEquals('Valuation', $View->Helpers->TestBeforeAfter->property);
    }

    /**
     * Test renderLoadHelper method
     *
     * @return void
     */
    public function testRenderLoadHelper(): void
    {
        $this->PostsController->helpers = ['Session', 'Html', 'Form', 'Number'];
        $View = new TestView($this->PostsController);

        $result = $View->render('index', false);
        $this->assertEquals('posts index', $result);

        $attached = $View->Helpers->loaded();
        $this->assertEquals(['Session', 'Html', 'Form', 'Number'], $attached);

        $this->PostsController->helpers = ['Html', 'Form', 'Number', 'TestLegacyPlugin.PluggedHelper'];
        $View = new TestView($this->PostsController);

        $result = $View->render('index', false);
        $this->assertEquals('posts index', $result);

        $attached = $View->Helpers->loaded();
        $expected = ['Html', 'Form', 'Number', 'PluggedHelper'];
        $this->assertEquals($expected, $attached, 'Attached helpers are wrong.');
    }

    /**
     * Test render method
     *
     * @return void
     */
    public function testRender(): void
    {
        $View = new TestView($this->PostsController);
        $result = $View->render('index');

        $this->assertMatchesRegularExpression("/<meta http-equiv=\"Content-Type\" content=\"text\/html; charset=utf-8\" \/>\s*<title>/", $result);
        $this->assertMatchesRegularExpression("/<div id=\"content\">\s*posts index\s*<\/div>/", $result);
        $this->assertMatchesRegularExpression("/<div id=\"content\">\s*posts index\s*<\/div>/", $result);

        $this->assertTrue(isset($View->viewVars['content_for_layout']), 'content_for_layout should be a view var');
        $this->assertTrue(isset($View->viewVars['scripts_for_layout']), 'scripts_for_layout should be a view var');

        $this->PostsController->set('url', 'flash');
        $this->PostsController->set('message', 'yo what up');
        $this->PostsController->set('pause', 3);
        $this->PostsController->set('pageTitle', 'yo what up');

        $View = new TestView($this->PostsController);
        $result = $View->render(false, 'flash');

        $this->assertMatchesRegularExpression("/<title>yo what up<\/title>/", $result);
        $this->assertMatchesRegularExpression("/<p><a href=\"flash\">yo what up<\/a><\/p>/", $result);

        $this->assertNull($View->render(false, 'flash'));

        $this->PostsController->helpers = ['Session', 'Cache', 'Html'];
        $this->PostsController->constructClasses();
        $this->PostsController->cacheAction = ['index' => 3600];
        $this->PostsController->request->params['action'] = 'index';
        Configure::write('Cache.check', true);

        $View = new TestView($this->PostsController);
        $result = $View->render('index');

        $this->assertMatchesRegularExpression("/<meta http-equiv=\"Content-Type\" content=\"text\/html; charset=utf-8\" \/>\s*<title>/", $result);
        $this->assertMatchesRegularExpression("/<div id=\"content\">\s*posts index\s*<\/div>/", $result);
        $this->assertMatchesRegularExpression("/<div id=\"content\">\s*posts index\s*<\/div>/", $result);
    }

    /**
     * Test render()ing a file in a subdir from a custom viewPath
     * in a plugin.
     *
     * @return void
     */
    public function testGetViewFileNameSubdirWithPluginAndViewPath(): void
    {
        $this->PostsController->plugin = 'TestLegacyPlugin';
        $this->PostsController->viewPath = 'Elements';
        $this->PostsController->name = 'Posts';
        $View = new TestView($this->PostsController);

        $expected = CORE_TESTS . DS . 'test_legacy_app' . DS . 'Plugin' . DS . 'TestLegacyPlugin' .
            DS . 'View' . DS . 'Elements' . DS . 'sub_dir' . DS . 'sub_element.ctp';
        $this->assertEquals($expected, $View->getViewFileName('sub_dir/sub_element'));
    }

    /**
     * Test getViewFileName method
     *
     * @return void
     */
    public function testViewFileName(): void
    {
        $View = new TestView($this->PostsController);

        $result = $View->getViewFileName('index');
        $this->assertMatchesRegularExpression('/Posts(\/|\\\)index.ctp/', $result);

        $result = $View->getViewFileName('TestLegacyPlugin.index');
        $this->assertMatchesRegularExpression('/Posts(\/|\\\)index.ctp/', $result);

        $result = $View->getViewFileName('/Pages/home');
        $this->assertMatchesRegularExpression('/Pages(\/|\\\)home.ctp/', $result);

        $result = $View->getViewFileName('../Elements/test_element');
        $this->assertMatchesRegularExpression('/Elements(\/|\\\)test_element.ctp/', $result);

        $result = $View->getViewFileName('../Themed/TestTheme/Posts/index');
        $this->assertMatchesRegularExpression('/Themed(\/|\\\)TestTheme(\/|\\\)Posts(\/|\\\)index.ctp/', $result);

        $expected = CORE_TESTS . DS . 'test_legacy_app' . DS . 'View' . DS . 'Posts' . DS . 'index.ctp';
        $result = $View->getViewFileName('../Posts/index');
        $this->assertEquals($expected, $result);
    }
}
