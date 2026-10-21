<?php
/**
 * CakePHP(tm) Tests <https://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.Test.Case.TestSuite
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Test\TestCase\Legacy\TestSuite;

use Cake\Core\App;
use Cake\Network\Email\CakeEmail;
use Cake\Network\Email\DebugTransport;
use Cake\Network\Email\SmtpTransport;
use Cake\Network\Email\TestEmailTransport;
use Cake\TestSuite\CakeTestCase;
use Cake\TestSuite\EmailTrait;
use Cake\TestSuite\IntegrationTestTrait;
use PHPUnit\Framework\AssertionFailedError;

/**
 * EmailTraitTest
 *
 * @package       Cake.Test.Case.TestSuite
 */
class EmailTraitTest extends CakeTestCase
{
    use EmailTrait;
    use IntegrationTestTrait;

    /**
     * Set up the test_app paths.
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        App::build([
            'Controller' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Controller' . DS],
            'View' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'View' . DS],
        ], App::RESET);
    }

    /**
     * Asserts that a callable fails with an assertion failure.
     *
     * @param callable $callable The assertion to run.
     * @param string|null $contains A string the failure message must contain.
     * @return void
     */
    protected function assertAssertionFails(callable $callable, ?string $contains = null): void
    {
        try {
            $callable();
        } catch (AssertionFailedError $e) {
            if ($contains !== null) {
                $this->assertStringContainsString($contains, $e->getMessage());
            } else {
                $this->addToAssertionCount(1);
            }

            return;
        }
        $this->fail('The assertion did not fail.');
    }

    /**
     * Sends the emails of IntegrationTestsController::email().
     *
     * @return void
     */
    protected function sendEmails(): void
    {
        $this->get('/integration_tests/email');
        $this->assertResponseEquals('sent');
    }

    /**
     * Test that the transports are replaced and restored.
     *
     * @return void
     */
    public function testTransportsAreReplaced(): void
    {
        $this->assertSame('TestEmail', CakeEmail::transportOverride());

        $email = new CakeEmail(['transport' => 'Smtp', 'host' => 'localhost.invalid']);
        $this->assertInstanceOf(TestEmailTransport::class, $email->transportClass());
        $this->assertSame('Smtp', $email->transport());

        $this->cleanupEmailTrait();
        $this->assertNull(CakeEmail::transportOverride());
        $this->assertInstanceOf(SmtpTransport::class, $email->transportClass());

        $email->transport('Debug');
        $this->assertInstanceOf(DebugTransport::class, $email->transportClass());

        TestEmailTransport::replaceAllTransports();
        $this->assertInstanceOf('TestEmailTransport', $email->transportClass(), 'A cached transport is replaced');
        $other = new CakeEmail(['transport' => 'Debug']);
        $transport = $other->transportClass();
        $this->assertInstanceOf(TestEmailTransport::class, $transport);
        $this->assertSame($transport, $other->transportClass(), 'The transport is cached');
    }

    /**
     * Test the content assertions.
     *
     * @return void
     */
    public function testContentAssertions(): void
    {
        $this->sendEmails();

        $this->assertMailContains('this is the text part');
        $this->assertMailContains('Plain message for Bob');
        $this->assertMailContainsAt(0, 'Hello Alice');
        $this->assertMailContainsAt(1, 'Plain message for Bob');
        $this->assertMailContainsText('Hello Alice, this is the text part.');
        $this->assertMailContainsTextAt(0, 'this is the text part');
        $this->assertMailContainsText('Plain message for Bob');
        $this->assertMailContainsHtml('<strong>Alice</strong>');
        $this->assertMailContainsHtmlAt(0, 'this is the html part');

        $this->assertAssertionFails(fn() => $this->assertMailContains('Not there'), "'Not there' is in an email");
        $this->assertAssertionFails(fn() => $this->assertMailContainsAt(1, 'Hello Alice'), 'is in email #1' . PHP_EOL . 'was: ');
        $this->assertAssertionFails(fn() => $this->assertMailContainsText('<strong>'), 'is in the text message of an email');
        $this->assertAssertionFails(fn() => $this->assertMailContainsTextAt(0, 'Bob'), 'is in the text message of email #0');
        $this->assertAssertionFails(fn() => $this->assertMailContainsHtml('Plain message for Bob'), 'is in the html message of an email');
        $this->assertAssertionFails(fn() => $this->assertMailContainsHtmlAt(1, 'Alice'), 'is in the html message of email #1');
    }
}
