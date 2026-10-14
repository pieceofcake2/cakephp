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

use PHPUnit\Framework\AssertionFailedError;

App::uses('CakeEmail', 'Network/Email');
App::uses('EmailTrait', 'TestSuite');
App::uses('IntegrationTestTrait', 'TestSuite');
App::uses('TestEmailTransport', 'Network/Email');

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
            'Controller' => [CORE_TESTS . DS . 'test_app' . DS . 'Controller' . DS],
            'View' => [CORE_TESTS . DS . 'test_app' . DS . 'View' . DS],
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
        $this->assertSame('TestEmailTransport', get_class($email->transportClass()), 'A cached transport is replaced');
        $other = new CakeEmail(['transport' => 'Debug']);
        $transport = $other->transportClass();
        $this->assertInstanceOf(TestEmailTransport::class, $transport);
        $this->assertSame($transport, $other->transportClass(), 'The transport is cached');
    }

    /**
     * Test that TestEmailTransport captures sent emails.
     *
     * @return void
     */
    public function testTestEmailTransport(): void
    {
        $email = new CakeEmail(['transport' => 'Mail']);
        $result = $email->from('app@example.com')->to('user@example.com')->subject('Hi')->send('Hello');

        $this->assertStringContainsString('Subject: Hi', $result['headers']);
        $this->assertStringContainsString('Hello', $result['message']);
        $messages = TestEmailTransport::getMessages();
        $this->assertCount(1, $messages);
        $this->assertNotSame($email, $messages[0]);
        $this->assertEquals(['user@example.com' => 'user@example.com'], $messages[0]->to());

        TestEmailTransport::clearMessages();
        $this->assertSame([], TestEmailTransport::getMessages());

        TestEmailTransport::restoreTransports();
        $explicit = new CakeEmail(['transport' => 'TestEmail']);
        $explicit->from('app@example.com')->to('user@example.com')->send('Explicit');
        $this->assertCount(1, TestEmailTransport::getMessages());
    }

    /**
     * Test the count assertions.
     *
     * @return void
     */
    public function testCountAssertions(): void
    {
        $this->assertNoMailSent();
        $this->assertMailCount(0);
        $this->assertAssertionFails(fn() => $this->assertMailCount(1), 'Failed asserting that 1 emails were sent.');

        $this->sendEmails();

        $this->assertMailCount(2);
        $this->assertAssertionFails(fn() => $this->assertMailCount(1), 'emails were sent');
        $this->assertAssertionFails(fn() => $this->assertNoMailSent(), 'Failed asserting that no emails were sent.');
    }

    /**
     * Test the address assertions.
     *
     * @return void
     */
    public function testAddressAssertions(): void
    {
        $this->sendEmails();

        $this->assertMailSentTo('alice@example.com');
        $this->assertMailSentTo('bob@example.com');
        $this->assertMailSentToAt(0, 'alice@example.com');
        $this->assertMailSentToAt(1, 'bob@example.com');
        $this->assertMailSentFrom('app@example.com');
        $this->assertMailSentFrom(['app@example.com' => 'Integration App']);
        $this->assertMailSentFrom('noreply@example.com');
        $this->assertMailSentFromAt(0, 'app@example.com');
        $this->assertMailSentFromAt(1, 'noreply@example.com');
        $this->assertMailSentWith('boss@example.com', 'cc');
        $this->assertMailSentWithAt(0, 'boss@example.com', 'cc');

        $this->assertAssertionFails(fn() => $this->assertMailSentTo('carol@example.com'), "'carol@example.com' was sent an email");
        $this->assertAssertionFails(fn() => $this->assertMailSentToAt(0, 'bob@example.com'), 'was sent email #0');
        $this->assertAssertionFails(fn() => $this->assertMailSentToAt(5, 'bob@example.com'), 'was sent email #5');
        $this->assertAssertionFails(fn() => $this->assertMailSentFrom('other@example.com'), 'sent an email');
        $this->assertAssertionFails(fn() => $this->assertMailSentFrom(['app@example.com' => 'Other name']), 'sent an email');
        $this->assertAssertionFails(fn() => $this->assertMailSentFromAt(1, 'app@example.com'), 'sent email #1');
        $this->assertAssertionFails(fn() => $this->assertMailSentWith('boss@example.com', 'bcc'), 'is in an email `bcc`');
        $this->assertAssertionFails(fn() => $this->assertMailSentWithAt(1, 'boss@example.com', 'cc'), 'is in email #1 `cc`');
    }

    /**
     * Test assertMailSentWith() with other properties.
     *
     * @return void
     */
    public function testAssertMailSentWith(): void
    {
        $this->sendEmails();

        $this->assertMailSentWith('Welcome Alice', 'subject');
        $this->assertMailSentWith('both', 'emailFormat');
        $this->assertMailSentWithAt(1, 'text', 'emailFormat');

        $this->assertAssertionFails(fn() => $this->assertMailSentWith('html', 'emailFormat'), 'is in an email `emailFormat`');
        $this->assertAssertionFails(fn() => $this->assertMailSentWith('value', 'noSuchGetter'), 'is in an email `noSuchGetter`');
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

    /**
     * Test the subject assertions.
     *
     * @return void
     */
    public function testSubjectAssertions(): void
    {
        $this->sendEmails();

        $this->assertMailSubjectContains('Welcome');
        $this->assertMailSubjectContains('Ünïcode subject');
        $this->assertMailSubjectContainsAt(0, 'Alice');
        $this->assertMailSubjectContainsAt(1, 'for Bob');

        $this->assertAssertionFails(fn() => $this->assertMailSubjectContains('Goodbye'), 'is in an email subject' . PHP_EOL . 'was: Welcome Alice');
        $this->assertAssertionFails(fn() => $this->assertMailSubjectContainsAt(0, 'Bob'), 'is in an email subject #0');
    }

    /**
     * Test the attachment assertions.
     *
     * @return void
     */
    public function testAttachmentAssertions(): void
    {
        $this->sendEmails();

        $this->assertMailContainsAttachment('notes.txt');
        $this->assertMailContainsAttachment('notes.txt', ['mimetype' => 'text/plain']);

        $this->assertAssertionFails(fn() => $this->assertMailContainsAttachment('other.txt'), "'other.txt' is an attachment of an email");
        $this->assertAssertionFails(fn() => $this->assertMailContainsAttachment('notes.txt', ['mimetype' => 'image/png']));
        $this->assertAssertionFails(fn() => $this->assertMailContainsAttachment('other.txt', ['mimetype' => 'text/plain']));
    }

    /**
     * Test the constraint messages with an index.
     *
     * @return void
     */
    public function testConstraintMessagesWithIndex(): void
    {
        $this->sendEmails();

        $this->assertSame('is an attachment of email #1', (new MailContainsAttachmentConstraint(1))->toString());
        $this->assertFalse((new MailContainsAttachmentConstraint(1))->matches(['notes.txt', []]));
        $this->assertSame('emails were sent', (new MailCountConstraint())->toString());
        $this->assertTrue((new MailCountConstraint(1))->matches(1));
    }

    /**
     * Test that a non-string subject is rejected.
     *
     * @return void
     */
    public function testSubjectContainsInvalidType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new MailSubjectContainsConstraint())->matches(['array']);
    }
}
