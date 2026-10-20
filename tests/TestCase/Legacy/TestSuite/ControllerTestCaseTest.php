<?php

/**
 * ControllerTestCaseTest file
 *
 * Test Case for ControllerTestCase class
 *
 * CakePHP : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP Project
 * @package       Cake.Test.Case.TestSuite
 * @since         CakePHP v 2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Test\TestCase\Legacy\TestSuite;

use Cake\Core\App;
use Cake\Core\CakePlugin;
use Cake\Error\MissingActionException;
use Cake\Routing\Router;
use Cake\TestSuite\CakeTestCase;
use Cake\Utility\ClassRegistry;

/**
 * ControllerTestCaseTest
 *
 * @package       Cake.Test.Case.TestSuite
 */
class ControllerTestCaseTest extends CakeTestCase
{
    /**
     * fixtures property
     *
     * @var array
     */
    public $fixtures = ['core.post', 'core.author', 'core.test_plugin_comment'];

    /**
     * reset environment.
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        App::build([
            'Plugin' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Plugin' . DS],
            'Controller' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Controller' . DS],
            'Model' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Model' . DS],
            'View' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'View' . DS],
        ], App::RESET);
        CakePlugin::load(['TestLegacyPlugin', 'TestTwoLegacyPlugin']);
        $this->Case = $this->getMockForAbstractClass('ControllerTestCase');
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
        $this->Case->controller = null;

        parent::tearDown();
    }

    /**
     * Test that ControllerTestCase::generate() creates mock objects correctly
     *
     * @return void
     */
    public function testGenerate(): void
    {
        $Posts = $this->Case->generate('Posts');
        $this->assertEquals('Posts', $Posts->name);
        $this->assertEquals('Post', $Posts->modelClass);
        $this->assertNull($Posts->response->send());

        $Posts = $this->Case->generate('Posts', [
            'methods' => [
                'render',
            ],
        ]);
        $this->assertNull($Posts->render('index'));

        $Posts = $this->Case->generate('Posts', [
            'models' => ['Post'],
            'components' => ['RequestHandler'],
        ]);

        $this->assertInstanceOf('Post', $Posts->Post);
        $this->assertNull($Posts->Post->save([]));
        $this->assertNull($Posts->Post->find('all'));
        $this->assertEquals('posts', $Posts->Post->useTable);
        $this->assertNull($Posts->RequestHandler->isAjax());

        $Posts = $this->Case->generate('Posts', [
            'models' => [
                'Post' => true,
            ],
        ]);
        $this->assertNull($Posts->Post->save([]));
        $this->assertNull($Posts->Post->find('all'));

        $Posts = $this->Case->generate('Posts', [
            'models' => [
                'Post' => ['save'],
            ],
        ]);
        $this->assertNull($Posts->Post->save([]));
        $this->assertIsArray($Posts->Post->find('all'));

        $Posts = $this->Case->generate('Posts', [
            'models' => ['Post'],
            'components' => [
                'RequestHandler' => ['isPut'],
                'Email' => ['send'],
                'Session',
            ],
        ]);
        $Posts->RequestHandler->expects($this->once())
            ->method('isPut')
            ->will($this->returnValue(true));
        $this->assertTrue($Posts->RequestHandler->isPut());

        $Posts->Auth->Session->expects($this->any())
            ->method('write')
            ->will($this->returnValue('written!'));
        $this->assertEquals('written!', $Posts->Auth->Session->write('something'));
    }

    /**
     * testGenerateWithComponentConfig
     *
     * @return void
     */
    public function testGenerateWithComponentConfig(): void
    {
        $Tests = $this->Case->generate('TestConfigs', [
        ]);

        $expected = ['some' => 'config'];
        $settings = array_intersect_key($Tests->RequestHandler->settings, ['some' => 'foo']);
        $this->assertSame($expected, $settings, 'A mocked component should have the same config as an unmocked component');

        $Tests = $this->Case->generate('TestConfigs', [
            'components' => [
                'RequestHandler' => ['isPut'],
            ],
        ]);

        $expected = ['some' => 'config'];
        $settings = array_intersect_key($Tests->RequestHandler->settings, ['some' => 'foo']);
        $this->assertSame($expected, $settings, 'A mocked component should have the same config as an unmocked component');
    }

    /**
     * Tests ControllerTestCase::generate() using classes from plugins
     *
     * @return void
     */
    public function testGenerateWithPlugin(): void
    {
        $Tests = $this->Case->generate('TestLegacyPlugin.Tests', [
            'models' => [
                'TestLegacyPlugin.TestPluginComment',
            ],
            'components' => [
                'TestLegacyPlugin.Plugins',
            ],
        ]);
        $this->assertEquals('Tests', $Tests->name);
        $this->assertInstanceOf('PluginsComponent', $Tests->Plugins);

        $result = ClassRegistry::init('TestLegacyPlugin.TestPluginComment');
        $this->assertInstanceOf('TestPluginComment', $result);

        $Tests = $this->Case->generate('ControllerTestCaseTest', [
            'models' => [
                'TestLegacyPlugin.TestPluginComment' => ['save'],
            ],
        ]);
        $this->assertInstanceOf('TestPluginComment', $Tests->TestPluginComment);
        $Tests->TestPluginComment->expects($this->exactly(2))
            ->method('save')
            ->willReturnOnConsecutiveCalls(true, false);
        $this->assertTrue($Tests->TestPluginComment->save([]));
        $this->assertFalse($Tests->TestPluginComment->save([]));
    }

    /**
     * Tests ControllerTestCase::generate() using aliased component
     *
     * @return void
     */
    public function testGenerateWithMockedAliasedComponent(): void
    {
        $Posts = $this->Case->generate('Posts', [
            'components' => [
                'AliasedEmail' => ['send'],
            ],
        ]);
        $Posts->AliasedEmail->expects($this->once())
            ->method('send')
            ->will($this->returnValue(true));

        $this->assertInstanceOf('EmailComponent', $Posts->AliasedEmail);
        $this->assertTrue($Posts->AliasedEmail->send());
    }

    /**
     * Tests ControllerTestCase::generate() using aliased plugin component
     *
     * @return void
     */
    public function testGenerateWithMockedAliasedPluginComponent(): void
    {
        $Posts = $this->Case->generate('Posts', [
            'components' => [
                'AliasedPluginEmail' => ['send'],
            ],
        ]);
        $Posts->AliasedPluginEmail->expects($this->once())
            ->method('send')
            ->will($this->returnValue(true));

        $this->assertInstanceOf('TestPluginEmailComponent', $Posts->AliasedPluginEmail);
        $this->assertTrue($Posts->AliasedPluginEmail->send());
    }

    /**
     * Tests testAction
     *
     * @return void
     */
    public function testTestAction(): void
    {
        $this->Case->generate('TestsApps');
        $this->Case->testAction('/tests_apps/index');
        $this->assertIsArray($this->Case->controller->viewVars);

        $this->Case->testAction('/tests_apps/set_action');
        $results = $this->Case->controller->viewVars;
        $expected = [
            'var' => 'string',
        ];
        $this->assertEquals($expected, $results);

        $result = $this->Case->controller->response->body();
        $this->assertMatchesRegularExpression('/This is the TestsAppsController index view/', $result);

        $Controller = $this->Case->generate('TestsApps');
        $this->Case->testAction('/tests_apps/redirect_to');
        $results = $this->Case->headers;
        $expected = [
            'Location' => 'https://cakephp.org',
        ];
        $this->assertEquals($expected, $results);
        $this->assertSame(302, $Controller->response->statusCode());
    }

    /**
     * Make sure testAction() can hit plugin controllers.
     *
     * @return void
     */
    public function testTestActionWithPlugin(): void
    {
        $this->Case->generate('TestLegacyPlugin.Tests');
        $this->Case->testAction('/test_legacy_plugin/tests/index');
        $this->assertEquals('It is a variable', $this->Case->controller->viewVars['test_value']);
    }

    /**
     * Tests using loaded routes during tests
     *
     * @return void
     */
    public function testUseRoutes(): void
    {
        Router::connect('/:controller/:action/*');
        include CORE_TESTS . DS . 'test_legacy_app' . DS . 'Config' . DS . 'routes.php';

        $controller = $this->Case->generate('TestsApps');
        $controller->Components->load('RequestHandler');
        $result = $this->Case->testAction('/tests_apps/index.json', ['return' => 'contents']);
        $result = json_decode($result, true);
        $expected = ['cakephp' => 'cool'];
        $this->assertEquals($expected, $result);

        include CORE_TESTS . DS . 'test_legacy_app' . DS . 'Config' . DS . 'routes.php';
        $result = $this->Case->testAction('/some_alias');
        $this->assertEquals(5, $result);
    }

    /**
     * Tests not using loaded routes during tests
     *
     * @return void
     */
    public function testSkipRoutes(): void
    {
        $this->expectException(MissingActionException::class);
        Router::connect('/:controller/:action/*');
        include CORE_TESTS . DS . 'test_legacy_app' . DS . 'Config' . DS . 'routes.php';

        $this->Case->loadRoutes = false;
        $this->Case->testAction('/tests_apps/missing_action.json', ['return' => 'view']);
    }

    /**
     * Tests backwards compatibility with setting the return type
     *
     * @return void
     */
    public function testBCSetReturn(): void
    {
        $this->Case->autoMock = true;

        $result = $this->Case->testAction('/tests_apps/some_method');
        $this->assertEquals(5, $result);

        $data = ['var' => 'set'];
        $result = $this->Case->testAction('/tests_apps_posts/post_var', [
            'data' => $data,
            'return' => 'vars',
        ]);
        $this->assertEquals($data, $result['data']);

        $result = $this->Case->testAction('/tests_apps/set_action', [
            'return' => 'view',
        ]);
        $this->assertEquals('This is the TestsAppsController index view string', $result);

        $result = $this->Case->testAction('/tests_apps/set_action', [
            'return' => 'contents',
        ]);
        $this->assertMatchesRegularExpression('/<html/', $result);
        $this->assertMatchesRegularExpression('/This is the TestsAppsController index view/', $result);
        $this->assertMatchesRegularExpression('/<\/html>/', $result);
    }
}
