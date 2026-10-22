<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace TestApp\TestSuite;

use PHPUnit\Framework\TestBuilder;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\TestSuite;
use ReflectionClass;
use RuntimeException;

/**
 * A test case with one test method per outcome, run in-process by the reporter tests.
 *
 * It is not a test of its own: the reporter tests build a suite from its methods
 * and paint the results.
 */
class ReporterSampleCase extends TestCase
{
    /**
     * The line of the failing statement of each test method, recorded when it runs.
     *
     * @var array<string, int>
     */
    public static array $lines = [];

    /**
     * Builds a suite named `Reporter sample` from the given test methods, in that order.
     *
     * @param string ...$methods The test methods.
     * @return TestSuite
     */
    public static function buildSuite(string ...$methods): TestSuite
    {
        $suite = new TestSuite('Reporter sample');
        $builder = new TestBuilder();
        foreach ($methods as $method) {
            $suite->addTest($builder->build(new ReflectionClass(static::class), $method));
        }

        return $suite;
    }

    /**
     * Passes with two assertions.
     *
     * @return void
     */
    public function testPasses(): void
    {
        $this->assertTrue(true);
        $this->assertSame(1, 1);
    }

    /**
     * Fails a comparison of two strings that contain HTML.
     *
     * @return void
     */
    public function testFails(): void
    {
        self::$lines[__FUNCTION__] = __LINE__ + 1;
        $this->assertSame('expected <b>bold</b>', 'actual <b>bold</b>', 'Values differ & <mismatch>');
    }

    /**
     * Throws an exception with HTML in its message.
     *
     * @return void
     */
    public function testThrowsException(): void
    {
        self::$lines[__FUNCTION__] = __LINE__ + 1;
        throw new RuntimeException('Broken <i>thing</i>');
    }

    /**
     * Is skipped with HTML in its message.
     *
     * @return void
     */
    public function testSkipped(): void
    {
        $this->markTestSkipped('Needs <redis>');
    }

    /**
     * Is incomplete with HTML in its message.
     *
     * @return void
     */
    public function testIncomplete(): void
    {
        $this->markTestIncomplete('Todo & more');
    }

    /**
     * Passes an assertion but adds a warning.
     *
     * @return void
     */
    public function testWarns(): void
    {
        $this->assertTrue(true);
        $this->addWarning('Deprecated <feature>');
    }

    /**
     * Passes for a data set whose name contains HTML.
     *
     * @param int $value A value.
     * @return void
     * @dataProvider htmlNamedProvider
     */
    public function testWithData(int $value): void
    {
        $this->assertSame(1, $value);
    }

    /**
     * Fails for a data set whose name contains HTML.
     *
     * @param int $value A value.
     * @return void
     * @dataProvider htmlNamedProvider
     */
    public function testFailsWithData(int $value): void
    {
        $this->assertSame(2, $value);
    }

    /**
     * Is skipped for a data set whose name contains HTML.
     *
     * @param int $value A value.
     * @return void
     * @dataProvider htmlNamedProvider
     */
    public function testSkippedWithData(int $value): void
    {
        $this->markTestSkipped('Skipped');
    }

    /**
     * Data sets whose names contain HTML.
     *
     * @return array
     */
    public static function htmlNamedProvider(): array
    {
        return ['<b>pass</b>' => [1]];
    }
}
