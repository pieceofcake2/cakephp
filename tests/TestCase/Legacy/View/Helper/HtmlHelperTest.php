<?php
/**
 * HtmlHelperTest file
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
 * @package       Cake.Test.Case.View.Helper
 * @since         CakePHP(tm) v 1.2.0.4206
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Test\TestCase\Legacy\View\Helper;

use Cake\Controller\Controller;
use Cake\Core\App;
use Cake\Core\CakePlugin;
use Cake\Core\Configure;
use Cake\Error\ConfigureException;
use Cake\Network\CakeRequest;
use Cake\TestSuite\CakeTestCase;
use Cake\Utility\File;
use Cake\Utility\Folder;
use Cake\View\Helper\HtmlHelper;

/**
 * TheHtmlTestController class
 *
 * @package       Cake.Test.Case.View.Helper
 */
class TheHtmlTestController extends Controller
{
    /**
     * name property
     *
     * @var string
     */
    public $name = 'TheTest';

    /**
     * uses property
     *
     * @var mixed
     */
    public $uses = null;
}

class TestHtmlHelper extends HtmlHelper
{
    /**
     * expose a method as public
     *
     * @param string $options
     * @param string $exclude
     * @param string $insertBefore
     * @param string $insertAfter
     * @return void
     */
    public function parseAttributes($options, $exclude = null, $insertBefore = ' ', $insertAfter = null): mixed
    {
        return $this->_parseAttributes($options, $exclude, $insertBefore, $insertAfter);
    }

    /**
     * Get a protected attribute value
     *
     * @param string $attribute
     * @return mixed
     */
    public function getAttribute($attribute): mixed
    {
        if (!isset($this->{$attribute})) {
            return null;
        }

        return $this->{$attribute};
    }
}

/**
 * HtmlHelperTest class
 *
 * @package       Cake.Test.Case.View.Helper
 */
class HtmlHelperTest extends CakeTestCase
{
    /**
     * Regexp for CDATA start block
     *
     * @var string
     */
    public $cDataStart = 'preg:/^\/\/<!\[CDATA\[[\n\r]*/';

    /**
     * Regexp for CDATA end block
     *
     * @var string
     */
    public $cDataEnd = 'preg:/[^\]]*\]\]\>[\s\r\n]*/';

    /**
     * html property
     *
     * @var object
     */
    public $Html = null;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->View = $this->getMock('View', ['append'], [new TheHtmlTestController()]);
        $this->Html = new TestHtmlHelper($this->View);
        $this->Html->request = new CakeRequest(null, false);
        $this->Html->request->webroot = '';

        App::build([
            'Plugin' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Plugin' . DS],
        ]);

        Configure::write('Asset.timestamp', false);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->Html, $this->View);

        parent::tearDown();
    }

    /**
     * Tests creation of an image tag using a theme and asset timestamping
     *
     * @return void
     */
    public function testImageTagWithTheme(): void
    {
        $this->skipIf(!is_writable(WWW_ROOT), 'Cannot write to webroot.');
        $themeExists = is_dir(WWW_ROOT . 'theme');
        $testfile = WWW_ROOT . 'theme' . DS . 'test_theme' . DS . 'img' . DS . '__cake_test_image.gif';
        new File($testfile, true);

        App::build([
            'View' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'View' . DS],
        ]);
        Configure::write('Asset.timestamp', true);
        Configure::write('debug', 1);

        $this->Html->request->webroot = '/';
        $this->Html->theme = 'test_theme';
        $result = $this->Html->image('__cake_test_image.gif');
        $this->assertTags($result, [
            'img' => [
                'src' => 'preg:/\/theme\/test_theme\/img\/__cake_test_image\.gif\?\d+/',
                'alt' => '',
            ]]);

        $this->Html->request->webroot = '/testing/';
        $result = $this->Html->image('__cake_test_image.gif');

        $this->assertTags($result, [
            'img' => [
                'src' => 'preg:/\/testing\/theme\/test_theme\/img\/__cake_test_image\.gif\?\d+/',
                'alt' => '',
            ]]);

        $dir = new Folder(WWW_ROOT . 'theme' . DS . 'test_theme');
        $dir->delete();
        if (!$themeExists) {
            $dir = new Folder(WWW_ROOT . 'theme');
            $dir->delete();
        }
    }

    /**
     * test theme assets in main webroot path
     *
     * @return void
     */
    public function testThemeAssetsInMainWebrootPath(): void
    {
        App::build([
            'View' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'View' . DS],
        ]);
        $webRoot = Configure::read('App.www_root');
        Configure::write('App.www_root', CORE_TESTS . DS . 'test_legacy_app' . DS . 'webroot' . DS);

        $this->Html->theme = 'test_theme';
        $result = $this->Html->css('webroot_test');
        $expected = [
            'link' => ['rel' => 'stylesheet', 'type' => 'text/css', 'href' => 'preg:/.*theme\/test_theme\/css\/webroot_test\.css/'],
        ];
        $this->assertTags($result, $expected);

        $this->Html->theme = 'test_theme';
        $result = $this->Html->css('theme_webroot');
        $expected = [
            'link' => ['rel' => 'stylesheet', 'type' => 'text/css', 'href' => 'preg:/.*theme\/test_theme\/css\/theme_webroot\.css/'],
        ];
        $this->assertTags($result, $expected);

        Configure::write('App.www_root', $webRoot);
    }

    /**
     * testPluginCssLink method
     *
     * @return void
     */
    public function testPluginCssLink(): void
    {
        Configure::write('Asset.filter.css', false);
        CakePlugin::load('TestLegacyPlugin');

        $result = $this->Html->css('TestLegacyPlugin.test_plugin_asset');
        $expected = [
            'link' => ['rel' => 'stylesheet', 'type' => 'text/css', 'href' => 'preg:/.*test_legacy_plugin\/css\/test_plugin_asset\.css/'],
        ];
        $this->assertTags($result, $expected);

        $result = $this->Html->css('TestLegacyPlugin.test_plugin_asset.css');
        $this->assertTags($result, $expected);

        $result = $this->Html->css('TestLegacyPlugin.my.css.library');
        $expected['link']['href'] = 'preg:/.*test_legacy_plugin\/css\/my\.css\.library\.css/';
        $this->assertTags($result, $expected);

        $result = $this->Html->css('TestLegacyPlugin.test_plugin_asset.css?1234');
        $expected['link']['href'] = 'preg:/.*test_legacy_plugin\/css\/test_plugin_asset\.css\?1234/';
        $this->assertTags($result, $expected);

        Configure::write('Asset.filter.css', 'css.php');
        $result = $this->Html->css('TestLegacyPlugin.test_plugin_asset');
        $expected['link']['href'] = 'preg:/.*test_legacy_plugin\/ccss\/test_plugin_asset\.css/';
        $this->assertTags($result, $expected);

        Configure::write('Asset.filter.css', false);

        $result = explode("\n", trim($this->Html->css(['TestLegacyPlugin.test_plugin_asset', 'TestLegacyPlugin.vendor.generic'])));
        $expected['link']['href'] = 'preg:/.*test_legacy_plugin\/css\/test_plugin_asset\.css/';
        $this->assertTags($result[0], $expected);
        $expected['link']['href'] = 'preg:/.*test_legacy_plugin\/css\/vendor\.generic\.css/';
        $this->assertTags($result[1], $expected);
        $this->assertEquals(2, count($result));

        CakePlugin::unload('TestLegacyPlugin');
    }

    /**
     * test use of css() and timestamping with plugin syntax
     *
     * @return void
     */
    public function testPluginCssTimestamping(): void
    {
        CakePlugin::load('TestLegacyPlugin');

        Configure::write('debug', 2);
        Configure::write('Asset.timestamp', true);

        $expected = [
            'link' => ['rel' => 'stylesheet', 'type' => 'text/css', 'href' => ''],
        ];

        $result = $this->Html->css('TestLegacyPlugin.test_plugin_asset');
        $expected['link']['href'] = 'preg:/.*test_legacy_plugin\/css\/test_plugin_asset\.css\?[0-9]+/';
        $this->assertTags($result, $expected);

        Configure::write('debug', 0);

        $result = $this->Html->css('TestLegacyPlugin.test_plugin_asset');
        $expected['link']['href'] = 'preg:/.*test_legacy_plugin\/css\/test_plugin_asset\.css/';
        $this->assertTags($result, $expected);

        Configure::write('Asset.timestamp', 'force');

        $result = $this->Html->css('TestLegacyPlugin.test_plugin_asset');
        $expected['link']['href'] = 'preg:/.*test_legacy_plugin\/css\/test_plugin_asset\.css\?[0-9]+/';
        $this->assertTags($result, $expected);

        $this->Html->request->webroot = '/testing/';
        $result = $this->Html->css('TestLegacyPlugin.test_plugin_asset');
        $expected['link']['href'] = 'preg:/\/testing\/test_legacy_plugin\/css\/test_plugin_asset\.css\?[0-9]+/';
        $this->assertTags($result, $expected);

        $this->Html->request->webroot = '/testing/longer/';
        $result = $this->Html->css('TestLegacyPlugin.test_plugin_asset');
        $expected['link']['href'] = 'preg:/\/testing\/longer\/test_legacy_plugin\/css\/test_plugin_asset\.css\?[0-9]+/';
        $this->assertTags($result, $expected);

        CakePlugin::unload('TestLegacyPlugin');
    }

    /**
     * test that plugin scripts added with uses() are only ever included once.
     * test script tag generation with plugin syntax
     *
     * @return void
     */
    public function testPluginScript(): void
    {
        CakePlugin::load('TestLegacyPlugin');

        $result = $this->Html->script('TestLegacyPlugin.foo');
        $expected = [
            'script' => ['type' => 'text/javascript', 'src' => 'test_legacy_plugin/js/foo.js'],
        ];
        $this->assertTags($result, $expected);

        $result = $this->Html->script(['TestLegacyPlugin.foobar', 'TestLegacyPlugin.bar']);
        $expected = [
            ['script' => ['type' => 'text/javascript', 'src' => 'test_legacy_plugin/js/foobar.js']],
            '/script',
            ['script' => ['type' => 'text/javascript', 'src' => 'test_legacy_plugin/js/bar.js']],
            '/script',
        ];
        $this->assertTags($result, $expected);

        $result = $this->Html->script('TestLegacyPlugin.jquery-1.3');
        $expected = [
            'script' => ['type' => 'text/javascript', 'src' => 'test_legacy_plugin/js/jquery-1.3.js'],
        ];
        $this->assertTags($result, $expected);

        $result = $this->Html->script('TestLegacyPlugin.test.json');
        $expected = [
            'script' => ['type' => 'text/javascript', 'src' => 'test_legacy_plugin/js/test.json.js'],
        ];
        $this->assertTags($result, $expected);

        $result = $this->Html->script('TestLegacyPlugin./jquery-1.3.2.js?someparam=foo');
        $expected = [
            'script' => ['type' => 'text/javascript', 'src' => 'test_legacy_plugin/jquery-1.3.2.js?someparam=foo'],
        ];
        $this->assertTags($result, $expected);

        $result = $this->Html->script('TestLegacyPlugin.test.json.js?foo=bar');
        $expected = [
            'script' => ['type' => 'text/javascript', 'src' => 'test_legacy_plugin/js/test.json.js?foo=bar'],
        ];
        $this->assertTags($result, $expected);

        $result = $this->Html->script('TestLegacyPlugin.foo');
        $this->assertNull($result, 'Script returned upon duplicate inclusion %s');

        $result = $this->Html->script(['TestLegacyPlugin.foo', 'TestLegacyPlugin.bar', 'TestLegacyPlugin.baz']);
        $this->assertDoesNotMatchRegularExpression('/test_legacy_plugin\/js\/foo.js/', $result);

        $result = $this->Html->script('TestLegacyPlugin.foo', ['inline' => true, 'once' => false]);
        $this->assertNotNull($result);

        $result = $this->Html->script('TestLegacyPlugin.jquery-1.3.2', ['defer' => true, 'encoding' => 'utf-8']);
        $expected = [
            'script' => ['type' => 'text/javascript', 'src' => 'test_legacy_plugin/js/jquery-1.3.2.js', 'defer' => 'defer', 'encoding' => 'utf-8'],
        ];
        $this->assertTags($result, $expected);

        CakePlugin::unload('TestLegacyPlugin');
    }

    /**
     * test timestamp enforcement for script tags with plugin syntax.
     *
     * @return void
     */
    public function testPluginScriptTimestamping(): void
    {
        CakePlugin::load('TestLegacyPlugin');

        $pluginPath = CakePlugin::path('TestLegacyPlugin');
        $pluginJsPath = $pluginPath . 'webroot/js';
        $this->skipIf(!is_writable($pluginJsPath), $pluginJsPath . ' is not Writable, timestamp testing has been skipped.');

        Configure::write('debug', 2);
        Configure::write('Asset.timestamp', true);

        touch($pluginJsPath . DS . '__cake_js_test.js');
        $timestamp = substr(strtotime('now'), 0, 8);

        $result = $this->Html->script('TestLegacyPlugin.__cake_js_test', ['inline' => true, 'once' => false]);
        $this->assertMatchesRegularExpression('/test_legacy_plugin\/js\/__cake_js_test.js\?' . $timestamp . '[0-9]{2}"/', $result, 'Timestamp value not found %s');

        Configure::write('debug', 0);
        Configure::write('Asset.timestamp', 'force');
        $result = $this->Html->script('TestLegacyPlugin.__cake_js_test', ['inline' => true, 'once' => false]);
        $this->assertMatchesRegularExpression('/test_legacy_plugin\/js\/__cake_js_test.js\?' . $timestamp . '[0-9]{2}"/', $result, 'Timestamp value not found %s');
        unlink($pluginJsPath . DS . '__cake_js_test.js');
        Configure::write('Asset.timestamp', false);

        CakePlugin::unload('TestLegacyPlugin');
    }

    public function testLoadConfig(): void
    {
        $path = CORE_TESTS . DS . 'test_legacy_app' . DS . 'Config' . DS;

        $result = $this->Html->loadConfig('htmlhelper_tags', $path);
        $expected = [
            'tags' => [
                'form' => 'start form',
                'formend' => 'finish form',
                'hiddenblock' => '<div class="hidden">%s</div>',
            ],
        ];
        $this->assertEquals($expected, $result);
        $tags = $this->Html->getAttribute('_tags');
        $this->assertEquals('start form', $tags['form']);
        $this->assertEquals('finish form', $tags['formend']);
        $this->assertEquals('</select>', $tags['selectend']);

        $result = $this->Html->loadConfig(['htmlhelper_minimized.ini', 'ini'], $path);
        $expected = [
            'minimizedAttributeFormat' => 'format',
        ];
        $this->assertEquals($expected, $result);
        $this->assertEquals('format', $this->Html->getAttribute('_minimizedAttributeFormat'));
    }

    /**
     * testLoadConfigWrongReader method
     *
     * @return void
     */
    public function testLoadConfigWrongReader(): void
    {
        $this->expectException(ConfigureException::class);
        $path = CORE_TESTS . DS . 'test_legacy_app' . DS . 'Config' . DS;
        $this->Html->loadConfig(['htmlhelper_tags', 'wrong_reader'], $path);
    }
}
