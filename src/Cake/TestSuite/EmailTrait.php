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
 * @package       Cake.TestSuite
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

App::uses('CakeEmail', 'Network/Email');
App::uses('TestEmailTransport', 'Network/Email');

/**
 * Make assertions on emails sent through `CakeEmail`.
 *
 * Backport of `Cake\TestSuite\EmailTrait` from CakePHP 5.x
 * (https://github.com/cakephp/cakephp/blob/5.x/src/TestSuite/EmailTrait.php) and its
 * constraints (`Constraint/Email/*`).
 *
 * Before each test, every `CakeEmail` instance is made to send with
 * `TestEmailTransport`, which delivers nothing and records the sent emails. After
 * each test the recorded emails are cleared and the configured transports are
 * restored.
 *
 * ```
 * class UsersControllerTest extends CakeTestCase
 * {
 *     use EmailTrait;
 *     use IntegrationTestTrait;
 *
 *     public function testRegister(): void
 *     {
 *         $this->post('/users/register', ['User' => ['email' => 'alice@example.com']]);
 *
 *         $this->assertMailCount(1);
 *         $this->assertMailSentTo('alice@example.com');
 *         $this->assertMailSubjectContains('Welcome');
 *         $this->assertMailContainsText('Thanks for registering');
 *     }
 * }
 * ```
 *
 * Indexes (`$at`) are 0-based, as in CakePHP 5.x. Differences from CakePHP 5.x:
 *
 * - The captured emails are `CakeEmail` instances (CakePHP 5: `Message`), and
 *   properties are read with the `CakeEmail` getters (`to()`, `subject()`, ...).
 * - Subjects are MIME-decoded before `assertMailSubjectContains()` compares them.
 * - `assertMailContainsAttachment()` always requires the file name to match.
 *
 * @package       Cake.TestSuite
 */
trait EmailTrait
{
    /**
     * Replaces all transports with the test transport during test setup
     *
     * @before
     * @return void
     */
    public function setupTransports(): void
    {
        TestEmailTransport::replaceAllTransports();
    }

    /**
     * Resets transport state and restores the configured transports
     *
     * @after
     * @return void
     */
    public function cleanupEmailTrait(): void
    {
        TestEmailTransport::clearMessages();
        TestEmailTransport::restoreTransports();
    }

    /**
     * Asserts an expected number of emails were sent
     *
     * @param int $count Email count
     * @param string $message Message
     * @return void
     */
    public function assertMailCount(int $count, string $message = ''): void
    {
        $this->assertThat($count, new MailCountConstraint(), $message);
    }

    /**
     * Asserts that no emails were sent
     *
     * @param string $message Message
     * @return void
     */
    public function assertNoMailSent(string $message = ''): void
    {
        $this->assertThat(null, new NoMailSentConstraint(), $message);
    }

    /**
     * Asserts an email at a specific index was sent to an address
     *
     * @param int $at Email index (0-based)
     * @param string $address Email address
     * @param string $message Message
     * @return void
     */
    public function assertMailSentToAt(int $at, string $address, string $message = ''): void
    {
        $this->assertThat($address, new MailSentToConstraint($at), $message);
    }

    /**
     * Asserts an email at a specific index was sent from an address
     *
     * @param int $at Email index (0-based)
     * @param string $address Email address
     * @param string $message Message
     * @return void
     */
    public function assertMailSentFromAt(int $at, string $address, string $message = ''): void
    {
        $this->assertThat($address, new MailSentFromConstraint($at), $message);
    }

    /**
     * Asserts an email at a specific index contains expected contents
     *
     * The whole rendered message is searched (all parts of a multipart email).
     *
     * @param int $at Email index (0-based)
     * @param string $contents Contents
     * @param string $message Message
     * @return void
     */
    public function assertMailContainsAt(int $at, string $contents, string $message = ''): void
    {
        $this->assertThat($contents, new MailContainsConstraint($at), $message);
    }

    /**
     * Asserts an email at a specific index contains expected html contents
     *
     * @param int $at Email index (0-based)
     * @param string $contents Contents
     * @param string $message Message
     * @return void
     */
    public function assertMailContainsHtmlAt(int $at, string $contents, string $message = ''): void
    {
        $this->assertThat($contents, new MailContainsHtmlConstraint($at), $message);
    }

    /**
     * Asserts an email at a specific index contains expected text contents
     *
     * @param int $at Email index (0-based)
     * @param string $contents Contents
     * @param string $message Message
     * @return void
     */
    public function assertMailContainsTextAt(int $at, string $contents, string $message = ''): void
    {
        $this->assertThat($contents, new MailContainsTextConstraint($at), $message);
    }

    /**
     * Asserts an email at a specific index contains the expected value within an Email getter
     *
     * @param int $at Email index (0-based)
     * @param string $expected Contents
     * @param string $parameter Email getter parameter (e.g. "cc", "subject")
     * @param string $message Message
     * @return void
     */
    public function assertMailSentWithAt(int $at, string $expected, string $parameter, string $message = ''): void
    {
        $this->assertThat($expected, new MailSentWithConstraint($at, $parameter), $message);
    }

    /**
     * Asserts an email was sent to an address
     *
     * @param string $address Email address
     * @param string $message Message
     * @return void
     */
    public function assertMailSentTo(string $address, string $message = ''): void
    {
        $this->assertThat($address, new MailSentToConstraint(), $message);
    }

    /**
     * Asserts an email was sent from an address
     *
     * @param array|string $address Email address, or an `[email => name]` array to
     *   also check the name.
     * @param string $message Message
     * @return void
     */
    public function assertMailSentFrom(array|string $address, string $message = ''): void
    {
        $this->assertThat($address, new MailSentFromConstraint(), $message);
    }

    /**
     * Asserts an email contains expected contents
     *
     * The whole rendered message is searched (all parts of a multipart email).
     *
     * @param string $contents Contents
     * @param string $message Message
     * @return void
     */
    public function assertMailContains(string $contents, string $message = ''): void
    {
        $this->assertThat($contents, new MailContainsConstraint(), $message);
    }

    /**
     * Asserts an email contains expected attachment
     *
     * ```
     * $this->assertMailContainsAttachment('invoice.pdf');
     * $this->assertMailContainsAttachment('logo.png', ['mimetype' => 'image/png']);
     * ```
     *
     * @param string $filename Filename
     * @param array $file Additional file properties to match, as returned by
     *   `CakeEmail::attachments()` (`file`, `mimetype`, `contentId`, ...).
     * @param string $message Message
     * @return void
     */
    public function assertMailContainsAttachment(string $filename, array $file = [], string $message = ''): void
    {
        $this->assertThat([$filename, $file], new MailContainsAttachmentConstraint(), $message);
    }

    /**
     * Asserts an email contains expected html contents
     *
     * @param string $contents Contents
     * @param string $message Message
     * @return void
     */
    public function assertMailContainsHtml(string $contents, string $message = ''): void
    {
        $this->assertThat($contents, new MailContainsHtmlConstraint(), $message);
    }

    /**
     * Asserts an email contains an expected text content
     *
     * @param string $expected Expected text.
     * @param string $message Message to display if assertion fails.
     * @return void
     */
    public function assertMailContainsText(string $expected, string $message = ''): void
    {
        $this->assertThat($expected, new MailContainsTextConstraint(), $message);
    }

    /**
     * Asserts an email contains the expected value within an Email getter
     *
     * ```
     * $this->assertMailSentWith('Welcome', 'subject');
     * $this->assertMailSentWith('both', 'emailFormat');
     * $this->assertMailSentWith('boss@example.com', 'cc');
     * ```
     *
     * @param string $expected Contents
     * @param string $parameter Email getter parameter (e.g. "cc", "bcc", "subject")
     * @param string $message Message
     * @return void
     */
    public function assertMailSentWith(string $expected, string $parameter, string $message = ''): void
    {
        $this->assertThat($expected, new MailSentWithConstraint(null, $parameter), $message);
    }

    /**
     * Asserts an email subject contains expected contents
     *
     * @param string $contents Contents
     * @param string $message Message
     * @return void
     */
    public function assertMailSubjectContains(string $contents, string $message = ''): void
    {
        $this->assertThat($contents, new MailSubjectContainsConstraint(), $message);
    }

    /**
     * Asserts the subject of an email at a specific index contains expected contents
     *
     * @param int $at Email index (0-based)
     * @param string $contents Contents
     * @param string $message Message
     * @return void
     */
    public function assertMailSubjectContainsAt(int $at, string $contents, string $message = ''): void
    {
        $this->assertThat($contents, new MailSubjectContainsConstraint($at), $message);
    }
}
