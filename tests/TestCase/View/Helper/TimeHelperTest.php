<?php
/**
 * TimeHelperTest file
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
use Cake\View\Helper\TimeHelper;
use Cake\View\View;
use TestApp\Utility\TestAppEngine;
use TestPlugin\Utility\TestPluginEngine;

/**
 * TimeHelperTestObject class
 *
 * @package       Cake.Test.Case.View.Helper
 */
class TimeHelperTestObject extends TimeHelper
{
    public function attach(CakeTimeMock $cakeTime)
    {
        $this->_engine = $cakeTime;
    }

    public function engine()
    {
        return $this->_engine;
    }
}

/**
 * CakeTimeMock class
 *
 * @package       Cake.Test.Case.View.Helper
 */
class CakeTimeMock
{
}
class_alias(CakeTimeMock::class, 'TestApp\\Utility\\CakeTimeMock');

/**
 * TimeHelperTest class
 *
 * @package       Cake.Test.Case.View.Helper
 */
class TimeHelperTest extends CakeTestCase
{
    protected ?string $_appNamespace = null;

    public $Time = null;

    public $CakeTime = null;

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
     * test CakeTime class methods are called correctly
     *
     * @return void
     */
    public function testTimeHelperProxyMethodCalls()
    {
        $methods = [
            'convertSpecifiers' => ['string'],
            'convert' => [0, 0],
            'serverOffset' => [],
            'fromString' => [null],
            'nice' => [null],
            'niceShort' => [null],
            'daysAsSql' => [null, null, 'string', null],
            'dayAsSql' => [null, 'string', null],
            'isToday' => [null, null],
            'isFuture' => [null, null],
            'isPast' => [null, null],
            'isThisWeek' => [null, null],
            'isThisMonth' => [null, null],
            'isThisYear' => [null, null],
            'wasYesterday' => [null, null],
            'isTomorrow' => [null, null],
            'toQuarter' => [null, false],
            'toUnix' => [null, null],
            'toServer' => [null, null],
            'toAtom' => [null, null],
            'toRSS' => [null, null],
            'timeAgoInWords' => [null],
            'wasWithinLast' => [0, null],
            'gmt' => [null],
            'format' => [0],
            'i18nFormat' => [null],
        ];

        $cakeTime = $this->getMock(CakeTimeMock::class, array_keys($methods));
        $time = new TimeHelperTestObject($this->View, ['engine' => 'CakeTimeMock']);
        $time->attach($cakeTime);

        $calledMethods = [];
        foreach (array_keys($methods) as $method) {
            $cakeTime
                ->expects($this->once())
                ->method($method)
                ->willReturnCallback(function () use ($method, &$calledMethods) {
                    $calledMethods[] = $method;

                    if (in_array($method, ['convertSpecifiers', 'i18nFormat'], true)) {
                        return 'string';
                    }
                    if (in_array($method, ['convert', 'serverOffset', 'fromString', 'gmt'], true)) {
                        return 0;
                    }
                    if (in_array($method, ['wasWithinLast'], true)) {
                        return true;
                    }
                    if (in_array($method, ['header'], true)) {
                        return [];
                    }

                    return null;
                });
        }

        foreach ($methods as $method => $args) {
            $time->{$method}(...$args);
        }

        $this->assertEquals(array_keys($methods), $calledMethods);

        $cakeTime = $this->getMock(CakeTimeMock::class, ['timeAgoInWords']);
        $time = new TimeHelperTestObject($this->View, ['engine' => 'CakeTimeMock']);
        $time->attach($cakeTime);

        $timeAgoInWordsCalled = false;
        $cakeTime->expects($this->once())
            ->method('timeAgoInWords')
            ->willReturnCallback(function () use (&$timeAgoInWordsCalled) {
                $timeAgoInWordsCalled = true;

                return null;
            });

        $time->timeAgoInWords('who', ['what']);

        $this->assertTrue($timeAgoInWordsCalled);
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
        $Time = new TimeHelperTestObject($this->View, ['engine' => 'TestAppEngine']);
        $this->assertInstanceOf(TestAppEngine::class, $Time->engine());

        App::build([
            'Plugin' => [CORE_TESTS . DS . 'test_app' . DS . 'plugins' . DS],
        ]);
        CakePlugin::load('TestPlugin');
        $Time = new TimeHelperTestObject($this->View, ['engine' => 'TestPlugin.TestPluginEngine']);
        $this->assertInstanceOf(TestPluginEngine::class, $Time->engine());
        CakePlugin::unload('TestPlugin');
    }

    /**
     * Test element wrapping in timeAgoInWords
     *
     * @return void
     */
    public function testTimeAgoInWords()
    {
        $Time = new TimeHelper($this->View);
        $timestamp = strtotime('+8 years, +4 months +2 weeks +3 days');
        $result = $Time->timeAgoInWords($timestamp, [
            'end' => '1 years',
            'element' => 'span',
        ]);
        $expected = [
            'span' => [
                'title' => $timestamp,
                'class' => 'time-ago-in-words',
            ],
            'on ' . date('j/n/y', $timestamp),
            '/span',
        ];
        $this->assertTags($result, $expected);

        $result = $Time->timeAgoInWords($timestamp, [
            'end' => '1 years',
            'element' => [
                'title' => 'testing',
                'rel' => 'test',
            ],
        ]);
        $expected = [
            'span' => [
                'title' => 'testing',
                'class' => 'time-ago-in-words',
                'rel' => 'test',
            ],
            'on ' . date('j/n/y', $timestamp),
            '/span',
        ];
        $this->assertTags($result, $expected);

        $timestamp = strtotime('+2 weeks');
        $result = $Time->timeAgoInWords(
            $timestamp,
            ['end' => '1 years', 'element' => 'div'],
        );
        $expected = [
            'div' => [
                'title' => $timestamp,
                'class' => 'time-ago-in-words',
            ],
            'in 2 weeks',
            '/div',
        ];
        $this->assertTags($result, $expected);
    }
}
