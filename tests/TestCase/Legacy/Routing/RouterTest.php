<?php
/**
 * RouterTest file
 *
 * CakePHP(tm) Tests <https://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.Test.Case.Routing
 * @since         CakePHP(tm) v 1.2.0.4206
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Test\TestCase\Legacy\Routing;

use Cake\Core\App;
use Cake\Core\CakePlugin;
use Cake\Core\Configure;
use Cake\Error\RouterException;
use Cake\Routing\Router;
use Cake\TestSuite\CakeTestCase;

/**
 * RouterTest class
 *
 * @package       Cake.Test.Case.Routing
 */
class RouterTest extends CakeTestCase
{
    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        Configure::write('Routing', ['admin' => null, 'prefixes' => []]);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        CakePlugin::unload();
        Router::fullBaseUrl('');
        Configure::write('App.fullBaseUrl', 'http://localhost');

        parent::tearDown();
    }

    /**
     * testMapResources with custom connectOptions
     */
    public function testMapResourcesConnectOptions(): void
    {
        App::build([
            'Plugin' => [
                CORE_TESTS . DS . 'test_legacy_app' . DS . 'Plugin' . DS,
            ],
        ]);
        CakePlugin::load('TestLegacyPlugin');
        Router::mapResources('Posts', [
            'connectOptions' => [
                'routeClass' => 'TestLegacyPlugin.TestRoute',
                'foo' => '^(bar)$',
            ],
        ]);
        $route = end(Router::$routes);
        $this->assertInstanceOf('TestRoute', $route);
        $this->assertEquals('^(bar)$', $route->options['foo']);
    }

    /**
     * test that the required default routes are connected.
     *
     * @return void
     */
    public function testConnectDefaultRoutes(): void
    {
        App::build([
            'plugins' => [
                CORE_TESTS . DS . 'test_legacy_app' . DS . 'Plugin' . DS,
            ],
        ], App::RESET);
        CakePlugin::load(['TestLegacyPlugin', 'TestJsLegacyPlugin']);
        Router::reload();
        require CORE_ROOT . DS . 'config' . DS . 'routes.php';

        $result = Router::url(['plugin' => 'test_js_legacy_plugin', 'controller' => 'js_file', 'action' => 'index']);
        $this->assertEquals('/test_js_legacy_plugin/js_file', $result);

        $result = Router::parse('/test_js_legacy_plugin/js_file');
        $expected = [
            'plugin' => 'test_js_legacy_plugin', 'controller' => 'js_file', 'action' => 'index',
            'named' => [], 'pass' => [],
        ];
        $this->assertEquals($expected, $result);

        $result = Router::url(['plugin' => 'test_legacy_plugin', 'controller' => 'test_legacy_plugin', 'action' => 'index']);
        $this->assertEquals('/test_legacy_plugin', $result);

        $result = Router::parse('/test_legacy_plugin');
        $expected = [
            'plugin' => 'test_legacy_plugin', 'controller' => 'test_legacy_plugin', 'action' => 'index',
            'named' => [], 'pass' => [],
        ];

        $this->assertEquals($expected, $result, 'Plugin shortcut route broken. %s');
    }

    /**
     * test using a custom route class for route connection
     *
     * @return void
     */
    public function testUsingCustomRouteClass(): void
    {
        $this->getMock('CakeRoute', [], [], 'MockConnectedRoute', false);
        $routes = Router::connect(
            '/:slug',
            ['controller' => 'posts', 'action' => 'view'],
            ['routeClass' => 'MockConnectedRoute', 'slug' => '[a-z_-]+'],
        );
        $this->assertInstanceOf('MockConnectedRoute', $routes[0], 'Incorrect class used. %s');
        $expected = ['controller' => 'posts', 'action' => 'view', 'slug' => 'test'];
        $routes[0]->expects($this->any())
            ->method('parse')
            ->will($this->returnValue($expected));
        $result = Router::parse('/test');
        $this->assertEquals($expected, $result);
    }

    /**
     * test using custom route class in PluginDot notation
     *
     * @return void
     */
    public function testUsingCustomRouteClassPluginDotSyntax(): void
    {
        App::build([
            'Plugin' => [
                CORE_TESTS . DS . 'test_legacy_app' . DS . 'Plugin' . DS,
            ],
        ]);
        CakePlugin::load('TestLegacyPlugin');
        $routes = Router::connect(
            '/:slug',
            ['controller' => 'posts', 'action' => 'view'],
            ['routeClass' => 'TestLegacyPlugin.TestRoute', 'slug' => '[a-z_-]+'],
        );
        $this->assertInstanceOf('TestRoute', $routes[0]);
        CakePlugin::unload('TestLegacyPlugin');
    }

    /**
     * test that route classes must extend CakeRoute
     *
     * @return void
     */
    public function testCustomRouteException(): void
    {
        $this->expectException(RouterException::class);
        Router::connect('/:controller', [], ['routeClass' => 'CakeObject']);
    }

    /**
     * Test setting the default route class
     *
     * @return void
     */
    public function testDefaultRouteClass(): void
    {
        $this->getMock('CakeRoute', [], ['/test'], 'TestDefaultRouteClass');
        Router::defaultRouteClass('TestDefaultRouteClass');

        $result = Router::connect('/', ['controller' => 'pages', 'action' => 'display', 'home']);
        $this->assertInstanceOf('TestDefaultRouteClass', $result[0]);
    }

    /**
     * Test that route classes must extend CakeRoute
     *
     * @return void
     */
    public function testSettingInvalidDefaultRouteException(): void
    {
        $this->expectException(RouterException::class);
        Router::defaultRouteClass('CakeObject');
    }

    /**
     * Test that class must exist
     *
     * @return void
     */
    public function testSettingNonExistentDefaultRouteException(): void
    {
        $this->expectException(RouterException::class);
        Router::defaultRouteClass('NonExistentClass');
    }
}
