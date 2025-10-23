<?php
/**
 * NumberHelperTest file
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

namespace Cake\Test\TestCase\View\Helper;

use Cake\Core\App;
use Cake\Core\CakePlugin;
use Cake\Core\Configure;
use Cake\TestSuite\CakeTestCase;
use Cake\View\Helper\NumberHelper;
use Cake\View\View;
use TestApp\Utility\TestAppEngine;
use TestPlugin\Utility\TestPluginEngine;

/**
 * NumberHelperTestObject class
 */
class NumberHelperTestObject extends NumberHelper
{
    public function attach(CakeNumberMock $cakeNumber)
    {
        $this->_engine = $cakeNumber;
    }

    public function engine()
    {
        return $this->_engine;
    }
}

/**
 * CakeNumberMock class
 */
class CakeNumberMock
{
}
class_alias(CakeNumberMock::class, 'TestApp\\Utility\\CakeNumberMock');

/**
 * NumberHelperTest class
 *
 * @package       Cake.Test.Case.View.Helper
 */
class NumberHelperTest extends CakeTestCase
{
    protected $_appNamespace = null;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->_appNamespace = Configure::read('App.namespace');
        Configure::write('App.namespace', 'TestApp');

        $this->View = new View(null);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->View);

        Configure::write('App.namespace', $this->_appNamespace);

        parent::tearDown();
    }

    /**
     * test CakeNumber class methods are called correctly
     *
     * @return void
     */
    public function testNumberHelperProxyMethodCalls()
    {
        $methods = [
            'precision', 'toReadableSize', 'toPercentage', 'format',
            'currency', 'addFormat',
        ];

        $CakeNumber = $this->getMock(CakeNumberMock::class, $methods);
        $Number = new NumberHelperTestObject($this->View, ['engine' => 'CakeNumberMock']);
        $Number->attach($CakeNumber);

        $calledMethods = [];
        foreach ($methods as $method) {
            $CakeNumber->expects($this->once())
                ->method($method)
                ->willReturnCallback(function () use ($method, &$calledMethods) {
                    $calledMethods[] = $method;

                    return null;
                });
        }

        foreach ($methods as $method) {
            $Number->{$method}('who', 'what', 'when', 'where', 'how');
        }

        $this->assertEquals($methods, $calledMethods);
    }

    /**
     * test engine override
     *
     * @return void
     */
    public function testEngineOverride()
    {
        App::build([
            'Utility' => [CORE_TESTS . DS . 'test_app' . DS . 'src' . DS . 'Utility' . DS],
        ], App::REGISTER);
        $Number = new NumberHelperTestObject($this->View, ['engine' => 'TestAppEngine']);
        $this->assertInstanceOf(TestAppEngine::class, $Number->engine());

        App::build([
            'Plugin' => [CORE_TESTS . DS . 'test_app' . DS . 'plugins' . DS],
        ]);
        CakePlugin::load('TestPlugin');
        $Number = new NumberHelperTestObject($this->View, ['engine' => 'TestPlugin.TestPluginEngine']);
        $this->assertInstanceOf(TestPluginEngine::class, $Number->engine());
        CakePlugin::unload('TestPlugin');
    }
}
