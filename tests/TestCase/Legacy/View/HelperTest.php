<?php
/**
 * HelperTest file
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

use Cake\Core\App;
use Cake\Core\CakePlugin;
use Cake\Core\Configure;
use Cake\Network\CakeRequest;
use Cake\Routing\Router;
use Cake\TestSuite\CakeTestCase;
use Cake\Utility\ClassRegistry;
use Cake\View\Helper;
use Cake\View\View;

class TestHelper extends Helper
{
    /**
     * Settings for this helper.
     *
     * @var array
     */
    public $settings = [
        'key1' => 'val1',
        'key2' => ['key2.1' => 'val2.1', 'key2.2' => 'val2.2'],
    ];

    /**
     * Helpers for this helper.
     *
     * @var array
     */
    public $helpers = ['Html', 'TestLegacyPlugin.OtherHelper'];

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
}

/**
 * HelperTest class
 *
 * @package       Cake.Test.Case.View
 */
class HelperTest extends CakeTestCase
{
    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        ClassRegistry::flush();
        Router::reload();
        $null = null;
        $this->View = new View($null);
        $this->Helper = new Helper($this->View);
        $this->Helper->request = new CakeRequest(null, false);

        App::build([
            'Plugin' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Plugin' . DS],
        ]);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        Configure::delete('Asset');

        CakePlugin::unload();
        unset($this->Helper, $this->View);

        parent::tearDown();
    }

    /**
     * Test assetUrl with plugins.
     *
     * @return void
     */
    public function testAssetUrlPlugin(): void
    {
        $this->Helper->webroot = '';
        CakePlugin::load('TestLegacyPlugin');

        $result = $this->Helper->assetUrl('TestLegacyPlugin.style', ['ext' => '.css']);
        $this->assertEquals('test_legacy_plugin/style.css', $result);

        $result = $this->Helper->assetUrl('TestLegacyPlugin.style', ['ext' => '.css', 'plugin' => false]);
        $this->assertEquals('TestLegacyPlugin.style.css', $result);

        CakePlugin::unload('TestLegacyPlugin');
    }

    /**
     * test assetTimestamp with plugins and themes
     *
     * @return void
     */
    public function testAssetTimestampPluginsAndThemes(): void
    {
        Configure::write('Asset.timestamp', 'force');
        App::build([
            'View' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'View' . DS],
        ]);
        CakePlugin::load(['TestLegacyPlugin']);

        $result = $this->Helper->assetTimestamp('/test_legacy_plugin/css/test_plugin_asset.css');
        $this->assertMatchesRegularExpression('#/test_legacy_plugin/css/test_plugin_asset.css\?[0-9]+$#', $result, 'Missing timestamp plugin');

        $result = $this->Helper->assetTimestamp('/test_legacy_plugin/css/i_dont_exist.css');
        $this->assertMatchesRegularExpression('#/test_legacy_plugin/css/i_dont_exist.css\?$#', $result, 'No error on missing file');

        $result = $this->Helper->assetTimestamp('/theme/test_theme/js/theme.js');
        $this->assertMatchesRegularExpression('#/theme/test_theme/js/theme.js\?[0-9]+$#', $result, 'Missing timestamp theme');

        $result = $this->Helper->assetTimestamp('/theme/test_theme/js/non_existant.js');
        $this->assertMatchesRegularExpression('#/theme/test_theme/js/non_existant.js\?$#', $result, 'No error on missing file');
    }

    public function testWebrootPaths(): void
    {
        $this->Helper->request->webroot = '/';
        $result = $this->Helper->webroot('/img/cake.power.gif');
        $expected = '/img/cake.power.gif';
        $this->assertEquals($expected, $result);

        $this->Helper->theme = 'test_theme';

        App::build([
            'View' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'View' . DS],
        ]);

        $result = $this->Helper->webroot('/img/cake.power.gif');
        $expected = '/theme/test_theme/img/cake.power.gif';
        $this->assertEquals($expected, $result);

        $result = $this->Helper->webroot('/img/test.jpg');
        $expected = '/theme/test_theme/img/test.jpg';
        $this->assertEquals($expected, $result);

        $webRoot = Configure::read('App.www_root');
        Configure::write('App.www_root', CORE_TESTS . DS . 'test_legacy_app' . DS . 'webroot' . DS);

        $result = $this->Helper->webroot('/img/cake.power.gif');
        $expected = '/theme/test_theme/img/cake.power.gif';
        $this->assertEquals($expected, $result);

        $result = $this->Helper->webroot('/img/test.jpg');
        $expected = '/theme/test_theme/img/test.jpg';
        $this->assertEquals($expected, $result);

        $result = $this->Helper->webroot('/img/cake.icon.gif');
        $expected = '/img/cake.icon.gif';
        $this->assertEquals($expected, $result);

        $result = $this->Helper->webroot('/img/cake.icon.gif?some=param');
        $expected = '/img/cake.icon.gif?some=param';
        $this->assertEquals($expected, $result);

        Configure::write('App.www_root', $webRoot);
    }

    /**
     * test lazy loading helpers is seamless
     *
     * @return void
     */
    public function testLazyLoadingHelpers(): void
    {
        App::build([
            'Plugin' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Plugin' . DS],
        ]);
        CakePlugin::load(['TestLegacyPlugin']);
        $Helper = new TestHelper($this->View);
        $this->assertInstanceOf('OtherHelperHelper', $Helper->OtherHelper);
        $this->assertInstanceOf('HtmlHelper', $Helper->Html);
        App::build();
    }
}
