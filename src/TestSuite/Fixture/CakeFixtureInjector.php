<?php

namespace Cake\TestSuite\Fixture;

use Cake\TestSuite\CakeTestCase;
use Exception;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Test;
use PHPUnit\Framework\TestListener;
use PHPUnit\Framework\TestSuite;
use PHPUnit\Framework\Warning;
use Throwable;

class CakeFixtureInjector implements TestListener
{
    protected ?CakeFixtureManager $_fixtureManager = null;

    protected ?TestSuite $_first = null;

    public function __construct(?CakeFixtureManager $manager = null)
    {
        $this->_fixtureManager = $manager ?? new CakeFixtureManager();
        $this->_fixtureManager->shutDown();
    }

    public function startTestSuite(TestSuite $suite): void
    {
        if (empty($this->_first)) {
            $this->_first = $suite;
        }
    }

    public function endTestSuite(TestSuite $suite): void
    {
        if ($this->_first === $suite) {
            $this->_fixtureManager->shutDown();
        }
    }

    public function startTest(Test $test): void
    {
        if ($test instanceof CakeTestCase) {
            $test->fixtureManager = $this->_fixtureManager;
            $this->_fixtureManager->fixturize($test);
            $this->_fixtureManager->load($test);
        }
    }

    public function endTest(Test $test, $time): void
    {
        if ($test instanceof CakeTestCase) {
            $this->_fixtureManager->unload($test);
        }
    }

    public function addError(Test $test, Exception|Throwable $t, $time): void
    {
    }

    public function addFailure(Test $test, AssertionFailedError $exception, $time): void
    {
    }

    public function addIncompleteTest(Test $test, Throwable $exception, $time): void
    {
    }

    public function addSkippedTest(Test $test, Throwable $exception, $time): void
    {
    }

    public function addRiskyTest(Test $test, Throwable $exception, $time): void
    {
    }

    public function addWarning(Test $test, Warning $exception, float $time): void
    {
    }
}
