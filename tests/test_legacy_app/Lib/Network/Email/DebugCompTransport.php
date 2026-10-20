<?php
/**
 * Test App DebugCompTransport
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @package       Cake.Test.TestApp.Lib.Network.Email
 * @since         CakePHP(tm) v 1.2.0.5347
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

/**
 * DebugCompTransport class
 *
 * @package       Cake.Test.TestApp.Lib.Network.Email
 */
class DebugCompTransport extends AbstractTransport
{
    /**
     * Last email
     *
     * @var string
     */
    public static $lastEmail = null;

    /**
     * sentDate
     *
     * @var string|null
     */
    public static $sentDate = null;

    /**
     * Send mail
     *
     * @params object $email CakeEmail
     * @return bool
     */
    public function send(CakeEmail $email): mixed
    {
        $email->addHeaders(['Date' => static::$sentDate]);
        $headers = $email->getHeaders(array_fill_keys(['from', 'replyTo', 'readReceipt', 'returnPath', 'to', 'cc', 'bcc', 'subject'], true));
        $to = $headers['To'];
        $subject = $headers['Subject'];
        unset($headers['To'], $headers['Subject']);

        $message = implode("\n", $email->message());

        $last = '<pre>';
        $last .= sprintf("%s %s\n", 'To:', $to);
        $last .= sprintf("%s %s\n", 'From:', $headers['From']);
        $last .= sprintf("%s %s\n", 'Subject:', $subject);
        $last .= sprintf("%s\n\n%s", 'Header:', $this->_headersToString($headers, "\n"));
        $last .= sprintf("%s\n\n%s", 'Message:', $message);
        $last .= '</pre>';

        static::$lastEmail = $last;

        return true;
    }
}
