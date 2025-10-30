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
use Cake\Utility\CakeNumber;
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
class CakeNumberMock extends CakeNumber
{
    /**
     * Track called methods
     *
     * @var array
     */
    public static array $calledMethods = [];

    /**
     * Reset called methods
     *
     * @return void
     */
    public static function reset(): void
    {
        self::$calledMethods = [];
    }

    /**
     * Mock precision method
     *
     * @param float $value
     * @param int $precision
     * @return string
     */
    public static function precision(float $value, int $precision = 3): string
    {
        self::$calledMethods[] = 'precision';

        return '1.000';
    }

    /**
     * Mock toReadableSize method
     *
     * @param int $size
     * @return string
     */
    public static function toReadableSize(int $size): string
    {
        self::$calledMethods[] = 'toReadableSize';

        return '1 KB';
    }

    /**
     * Mock toPercentage method
     *
     * @param float $value
     * @param int $precision
     * @param array $options
     * @return string
     */
    public static function toPercentage(float $value, int $precision = 2, array $options = []): string
    {
        self::$calledMethods[] = 'toPercentage';

        return '50%';
    }

    /**
     * Mock format method
     *
     * @param string|float $value
     * @param array|string|int|false $options
     * @return string
     */
    public static function format(string|float $value, array|string|int|false $options = false): string
    {
        self::$calledMethods[] = 'format';

        return '1,000';
    }

    /**
     * Mock currency method
     *
     * @param float $value
     * @param string|null $currency
     * @param array $options
     * @return string
     */
    public static function currency(float $value, ?string $currency = null, array $options = []): string
    {
        self::$calledMethods[] = 'currency';

        return '$100';
    }

    /**
     * Mock addFormat method
     *
     * @param string $formatName
     * @param array $options
     * @return void
     */
    public static function addFormat(string $formatName, array $options): void
    {
        self::$calledMethods[] = 'addFormat';
        // Mock implementation - does nothing
    }
}
class_alias(CakeNumberMock::class, 'TestApp\\Utility\\CakeNumberMock');

/**
 * NumberHelperTest class
 *
 * @package       Cake.Test.Case.View.Helper
 */
class NumberHelperTest extends CakeTestCase
{
    protected ?string $_appNamespace = null;
    public ?View $View = null;

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
        $this->View = null;

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
            'precision' => [1],
            'toReadableSize' => [1024],
            'toPercentage' => [0.5],
            'format' => [1234.56],
            'currency' => [100],
            'addFormat' => ['test', []],
        ];

        CakeNumberMock::reset();
        $number = new NumberHelper($this->View, ['engine' => 'CakeNumberMock']);

        foreach ($methods as $method => $args) {
            $number->{$method}(...$args);
        }

        $this->assertEquals(array_keys($methods), CakeNumberMock::$calledMethods);
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
        $number = new NumberHelperTestObject($this->View, ['engine' => 'TestAppEngine']);
        $this->assertInstanceOf(TestAppEngine::class, $number->engine());

        App::build([
            'Plugin' => [CORE_TESTS . DS . 'test_app' . DS . 'plugins' . DS],
        ]);
        CakePlugin::load('TestPlugin');
        $number = new NumberHelperTestObject($this->View, ['engine' => 'TestPlugin.TestPluginEngine']);
        $this->assertInstanceOf(TestPluginEngine::class, $number->engine());
        CakePlugin::unload('TestPlugin');
    }
}
