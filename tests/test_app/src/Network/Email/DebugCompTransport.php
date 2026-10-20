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
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace TestApp\Network\Email;

use Cake\Network\Email\AbstractTransport;
use Cake\Network\Email\CakeEmail;
use Cake\Test\TestCase\Controller\Component\EmailComponentTest;

/**
 * DebugCompTransport class
 *
 * @package       Cake.Test.Case.Controller.Component
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
     * Send mail
     *
     * @params CakeEmail $email CakeEmail
     * @return array
     */
    public function send(CakeEmail $email): mixed
    {
        $email->addHeaders(['Date' => EmailComponentTest::$sentDate]);
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

        return [$last];
    }
}
