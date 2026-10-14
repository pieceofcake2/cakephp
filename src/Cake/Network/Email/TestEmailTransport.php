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
 * @package       Cake.Network.Email
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

App::uses('DebugTransport', 'Network/Email');

/**
 * Email transport that captures sent emails for test assertions.
 *
 * Backport of `Cake\TestSuite\TestEmailTransport` from CakePHP 5.x
 * (https://github.com/cakephp/cakephp/blob/5.x/src/TestSuite/TestEmailTransport.php).
 * Like `DebugTransport`, nothing is delivered; each sent `CakeEmail` is also
 * stored (as a clone) so `EmailTrait` assertions can inspect it.
 *
 * CakePHP 2 has no transport registry, so `replaceAllTransports()` uses
 * `CakeEmail::overrideTransport()` to make every `CakeEmail` instance use this
 * transport, whatever its `transport` setting or `EmailConfig` configuration.
 * You can also select it explicitly with `'transport' => 'TestEmail'`.
 *
 * ```
 * TestEmailTransport::replaceAllTransports();
 * $email = new CakeEmail(['from' => 'app@example.com']);
 * $email->to('user@example.com')->subject('Hello')->send('Hi');
 * $messages = TestEmailTransport::getMessages(); // [CakeEmail]
 * TestEmailTransport::restoreTransports();
 * ```
 *
 * @package       Cake.Network.Email
 * @see EmailTrait
 */
class TestEmailTransport extends DebugTransport
{
    /**
     * Emails sent since the last `clearMessages()` call.
     *
     * @var array<CakeEmail>
     */
    protected static $_messages = [];

    /**
     * Stores a copy of the email and returns the rendered headers and message like
     * `DebugTransport`.
     *
     * @param CakeEmail $email The email being sent.
     * @return array Array with the `headers` and `message` keys.
     */
    public function send(CakeEmail $email)
    {
        static::$_messages[] = clone $email;

        return parent::send($email);
    }

    /**
     * Makes every `CakeEmail` instance send with this transport.
     *
     * Unlike CakePHP 5.x, which rewrites the `TransportFactory` configurations, this
     * sets `CakeEmail::overrideTransport('TestEmail')`. Call `restoreTransports()` to
     * undo it (`EmailTrait` does this after each test).
     *
     * @return void
     */
    public static function replaceAllTransports()
    {
        CakeEmail::overrideTransport('TestEmail');
    }

    /**
     * Removes the override set by `replaceAllTransports()`, so emails use their
     * configured transports again.
     *
     * This method does not exist in CakePHP 5.x.
     *
     * @return void
     */
    public static function restoreTransports()
    {
        CakeEmail::overrideTransport(null);
    }

    /**
     * Returns the emails sent since the last `clearMessages()` call.
     *
     * @return array<CakeEmail> The sent emails (clones), oldest first.
     */
    public static function getMessages()
    {
        return static::$_messages;
    }

    /**
     * Forgets the captured emails.
     *
     * @return void
     */
    public static function clearMessages()
    {
        static::$_messages = [];
    }
}
