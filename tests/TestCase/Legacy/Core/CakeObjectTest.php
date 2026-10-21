<?php
/**
 * ObjectTest file
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
 * @package       Cake.Test.Case.Core
 * @since         CakePHP(tm) v 1.2.0.5432
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Test\TestCase\Legacy\Core;

use Cake\Core\App;
use Cake\Core\CakeObject;
use Cake\Core\CakePlugin;
use Cake\Core\Configure;
use Cake\Routing\Router;
use Cake\TestSuite\CakeTestCase;

/**
 * TestCakeObject class
 *
 * @package       Cake.Test.Case.Core
 */
class TestCakeObject extends CakeObject
{
    /**
     * firstName property
     *
     * @var string
     */
    public $firstName = 'Joel';

    /**
     * lastName property
     *
     * @var string
     */
    public $lastName = 'Moss';

    /**
     * methodCalls property
     *
     * @var array
     */
    public $methodCalls = [];

    /**
     * emptyMethod method
     *
     * @return void
     */
    public function emptyMethod(): void
    {
        $this->methodCalls[] = 'emptyMethod';
    }

    /**
     * oneParamMethod method
     *
     * @param mixed $param
     * @return void
     */
    public function oneParamMethod($param): void
    {
        $this->methodCalls[] = ['oneParamMethod' => [$param]];
    }

    /**
     * twoParamMethod method
     *
     * @param mixed $param
     * @param mixed $paramTwo
     * @return void
     */
    public function twoParamMethod($param, $paramTwo): void
    {
        $this->methodCalls[] = ['twoParamMethod' => [$param, $paramTwo]];
    }

    /**
     * threeParamMethod method
     *
     * @param mixed $param
     * @param mixed $paramTwo
     * @param mixed $paramThree
     * @return void
     */
    public function threeParamMethod($param, $paramTwo, $paramThree): void
    {
        $this->methodCalls[] = ['threeParamMethod' => [$param, $paramTwo, $paramThree]];
    }

    /**
     * fourParamMethod method
     *
     * @param mixed $param
     * @param mixed $paramTwo
     * @param mixed $paramThree
     * @param mixed $paramFour
     * @return void
     */
    public function fourParamMethod($param, $paramTwo, $paramThree, $paramFour): void
    {
        $this->methodCalls[] = ['fourParamMethod' => [$param, $paramTwo, $paramThree, $paramFour]];
    }

    /**
     * fiveParamMethod method
     *
     * @param mixed $param
     * @param mixed $paramTwo
     * @param mixed $paramThree
     * @param mixed $paramFour
     * @param mixed $paramFive
     * @return void
     */
    public function fiveParamMethod($param, $paramTwo, $paramThree, $paramFour, $paramFive): void
    {
        $this->methodCalls[] = ['fiveParamMethod' => [$param, $paramTwo, $paramThree, $paramFour, $paramFive]];
    }

    /**
     * crazyMethod method
     *
     * @param mixed $param
     * @param mixed $paramTwo
     * @param mixed $paramThree
     * @param mixed $paramFour
     * @param mixed $paramFive
     * @param mixed $paramSix
     * @param mixed $paramSeven
     * @return void
     */
    public function crazyMethod($param, $paramTwo, $paramThree, $paramFour, $paramFive, $paramSix, $paramSeven = null): void
    {
        $this->methodCalls[] = ['crazyMethod' => [$param, $paramTwo, $paramThree, $paramFour, $paramFive, $paramSix, $paramSeven]];
    }

    /**
     * methodWithOptionalParam method
     *
     * @param mixed $param
     * @return void
     */
    public function methodWithOptionalParam($param = null): void
    {
        $this->methodCalls[] = ['methodWithOptionalParam' => [$param]];
    }

    /**
     * Set properties.
     *
     * @param array $properties The $properties.
     * @return void
     */
    public function set($properties = []): mixed
    {
        return parent::_set($properties);
    }
}

/**
 * CakeObject Test class
 *
 * @package       Cake.Test.Case.Core
 */
class CakeObjectTest extends CakeTestCase
{
    /**
     * fixtures
     *
     * @var string
     */
    public $fixtures = ['core.post', 'core.test_plugin_comment', 'core.comment'];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->object = new TestCakeObject();
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        CakePlugin::unload();
        unset($this->object);

        parent::tearDown();
    }

    /**
     * testRequestAction method
     *
     * @return void
     */
    public function testRequestAction(): void
    {
        App::build([
            'Model' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Model' . DS],
            'View' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'View' . DS],
            'Controller' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Controller' . DS],
        ], App::RESET);
        $this->assertNull(Router::getRequest(), 'request stack should be empty.');

        $result = $this->object->requestAction('');
        $this->assertFalse($result);

        $result = $this->object->requestAction('/request_action/test_request_action');
        $expected = 'This is a test';
        $this->assertEquals($expected, $result);

        $result = $this->object->requestAction(
            Configure::read('App.fullBaseUrl') . '/request_action/test_request_action',
        );
        $expected = 'This is a test';
        $this->assertEquals($expected, $result);

        $result = $this->object->requestAction('/request_action/another_ra_test/2/5');
        $expected = 7;
        $this->assertEquals($expected, $result);

        $result = $this->object->requestAction('/tests_apps/index', ['return']);
        $expected = 'This is the TestsAppsController index view ';
        $this->assertEquals($expected, $result);

        $result = $this->object->requestAction('/tests_apps/some_method');
        $expected = 5;
        $this->assertEquals($expected, $result);

        $result = $this->object->requestAction('/request_action/paginate_request_action');
        $this->assertTrue($result);

        $result = $this->object->requestAction('/request_action/normal_request_action');
        $expected = 'Hello World';
        $this->assertEquals($expected, $result);

        $this->assertNull(Router::getRequest(), 'requests were not popped off the stack, this will break url generation');
    }

    /**
     * test requestAction() and plugins.
     *
     * @return void
     */
    public function testRequestActionPlugins(): void
    {
        App::build([
            'Plugin' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Plugin' . DS],
        ], App::RESET);
        CakePlugin::load('TestLegacyPlugin');
        Router::reload();

        $result = $this->object->requestAction('/test_legacy_plugin/tests/index', ['return']);
        $expected = 'test plugin index';
        $this->assertEquals($expected, $result);

        $result = $this->object->requestAction('/test_legacy_plugin/tests/index/some_param', ['return']);
        $expected = 'test plugin index';
        $this->assertEquals($expected, $result);

        $result = $this->object->requestAction(
            ['controller' => 'tests', 'action' => 'index', 'plugin' => 'test_legacy_plugin'],
            ['return'],
        );
        $expected = 'test plugin index';
        $this->assertEquals($expected, $result);

        $result = $this->object->requestAction('/test_legacy_plugin/tests/some_method');
        $expected = 25;
        $this->assertEquals($expected, $result);

        $result = $this->object->requestAction(
            ['controller' => 'tests', 'action' => 'some_method', 'plugin' => 'test_legacy_plugin'],
        );
        $expected = 25;
        $this->assertEquals($expected, $result);
    }

    /**
     * test requestAction() with arrays.
     *
     * @return void
     */
    public function testRequestActionArray(): void
    {
        App::build([
            'Model' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Model' . DS],
            'View' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'View' . DS],
            'Controller' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Controller' . DS],
            'Plugin' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Plugin' . DS],
        ], App::RESET);
        CakePlugin::load(['TestLegacyPlugin']);

        $result = $this->object->requestAction(
            ['controller' => 'request_action', 'action' => 'test_request_action'],
        );
        $expected = 'This is a test';
        $this->assertEquals($expected, $result);

        $result = $this->object->requestAction(
            ['controller' => 'request_action', 'action' => 'another_ra_test'],
            ['pass' => ['5', '7']],
        );
        $expected = 12;
        $this->assertEquals($expected, $result);

        $result = $this->object->requestAction(
            ['controller' => 'tests_apps', 'action' => 'index'],
            ['return'],
        );
        $expected = 'This is the TestsAppsController index view ';
        $this->assertEquals($expected, $result);

        $result = $this->object->requestAction(['controller' => 'tests_apps', 'action' => 'some_method']);
        $expected = 5;
        $this->assertEquals($expected, $result);

        $result = $this->object->requestAction(
            ['controller' => 'request_action', 'action' => 'normal_request_action'],
        );
        $expected = 'Hello World';
        $this->assertEquals($expected, $result);

        $result = $this->object->requestAction(
            ['controller' => 'request_action', 'action' => 'paginate_request_action'],
        );
        $this->assertTrue($result);

        $result = $this->object->requestAction(
            ['controller' => 'request_action', 'action' => 'paginate_request_action'],
            ['pass' => [5], 'named' => ['param' => 'value']],
        );
        $this->assertTrue($result);
    }
}
