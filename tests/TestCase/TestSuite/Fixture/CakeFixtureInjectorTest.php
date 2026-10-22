<?php

/**
 * CakeFixtureInjectorTest file
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP Project
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Test\TestCase\TestSuite\Fixture;

use Cake\TestSuite\CakeTestCase;
use Cake\TestSuite\Fixture\CakeFixtureInjector;
use Cake\TestSuite\Fixture\CakeFixtureManager;
use Exception;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\TestSuite;
use PHPUnit\Framework\Warning;
use ReflectionClass;

/**
 * A test case that uses fixtures, given to the listener
 */
class CakeFixtureInjectorTestCase extends CakeTestCase
{
    public $fixtures = ['core.article'];

    /**
     * testNothing method
     *
     * @return void
     */
    public function testNothing(): void
    {
    }
}

/**
 * A test case of PHPUnit, which knows nothing of fixtures
 */
class CakeFixtureInjectorPlainTestCase extends TestCase
{
    /**
     * testNothing method
     *
     * @return void
     */
    public function testNothing(): void
    {
    }
}

/**
 * Tests the listener that loads the fixtures of the tests.
 */
class CakeFixtureInjectorTest extends CakeTestCase
{
    /**
     * Creates a mock of the fixture manager.
     *
     * @return CakeFixtureManager&MockObject
     */
    protected function _manager(): CakeFixtureManager
    {
        return $this->getMockBuilder(CakeFixtureManager::class)
            ->onlyMethods(['fixturize', 'load', 'unload', 'shutDown', 'loadSingle'])
            ->getMock();
    }

    /**
     * Test that the listener shuts down the fixture manager when it is created and after the
     * first test suite only, not after the nested ones.
     *
     * @return void
     */
    public function testShutDownAfterTheFirstSuite(): void
    {
        $manager = $this->_manager();
        $manager->expects($this->exactly(2))->method('shutDown');
        $manager->expects($this->never())->method('fixturize');

        $injector = new CakeFixtureInjector($manager);
        $all = new TestSuite('AllTests');
        $nested = new TestSuite('AllCoreTest');

        $injector->startTestSuite($all);
        $injector->startTestSuite($nested);
        $injector->endTestSuite($nested);
        $injector->endTestSuite($all);
    }

    /**
     * Test that the listener gives a CakeTestCase the fixture manager, loads its fixtures before
     * the test and unloads them after it.
     *
     * @return void
     */
    public function testLoadsFixturesOfCakeTestCase(): void
    {
        $test = new CakeFixtureInjectorTestCase('testNothing');
        $calls = [];
        $manager = $this->_manager();
        foreach (['fixturize', 'load', 'unload'] as $method) {
            $manager->expects($this->once())->method($method)->with($this->identicalTo($test))
                ->willReturnCallback(function () use (&$calls, $method): void {
                    $calls[] = $method;
                });
        }

        $injector = new CakeFixtureInjector($manager);
        $injector->startTest($test);
        $this->assertSame($manager, $test->fixtureManager);
        $this->assertSame(['fixturize', 'load'], $calls);

        $injector->endTest($test, 0.1);
        $this->assertSame(['fixturize', 'load', 'unload'], $calls);
    }

    /**
     * Test that the listener leaves the tests of PHPUnit alone, and that the results of the tests
     * do not change the fixtures.
     *
     * @return void
     */
    public function testIgnoresOtherTestsAndResults(): void
    {
        $manager = $this->_manager();
        $manager->expects($this->once())->method('shutDown');
        foreach (['fixturize', 'load', 'unload', 'loadSingle'] as $method) {
            $manager->expects($this->never())->method($method);
        }

        $injector = new CakeFixtureInjector($manager);
        $test = new CakeFixtureInjectorPlainTestCase('testNothing');
        $injector->startTest($test);
        $injector->endTest($test, 0.1);

        $test = new CakeFixtureInjectorTestCase('testNothing');
        $injector->addError($test, new Exception('error'), 0.1);
        $injector->addFailure($test, new AssertionFailedError('failure'), 0.1);
        $injector->addIncompleteTest($test, new Exception('incomplete'), 0.1);
        $injector->addSkippedTest($test, new Exception('skipped'), 0.1);
        $injector->addRiskyTest($test, new Exception('risky'), 0.1);
        $injector->addWarning($test, new Warning('warning'), 0.1);
        $this->assertNull($test->fixtureManager);
    }

    /**
     * Test that the former path of the listener in src/Cake/, which the phpunit.xml of an
     * application may name, loads the class without declaring it again.
     *
     * @return void
     */
    public function testFormerPath(): void
    {
        $this->assertTrue(class_exists(CakeFixtureInjector::class, false));

        $former = CAKE . 'Cake' . DS . 'TestSuite' . DS . 'Fixture' . DS . 'CakeFixtureInjector.php';
        $this->assertNotFalse(require_once $former);

        $this->assertContains(realpath($former), get_included_files());
        $class = new ReflectionClass('CakeFixtureInjector');
        $this->assertSame(CakeFixtureInjector::class, $class->getName());
        $this->assertSame(realpath(CAKE . 'TestSuite' . DS . 'Fixture' . DS . 'CakeFixtureInjector.php'), $class->getFileName());
    }
}
