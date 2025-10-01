<?php
/**
 * Send mail using mail() function
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
 * @package       Cake.Network.Email
 * @since         CakePHP(tm) v 2.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
App::uses('AbstractTransport', 'Network/Email');

/**
 * Send mail using mail() function
 *
 * @package       Cake.Network.Email
 */
class MailTransport extends AbstractTransport
{
    /**
     * Send mail
     *
     * @param CakeEmail $email CakeEmail
     * @return array
     * @throws SocketException When mail cannot be sent.
     */
    public function send(CakeEmail $email)
    {
        // https://github.com/cakephp/cakephp/issues/2209
        // https://bugs.php.net/bug.php?id=47983
        $eol = $this->_config['eol'] ?? "\r\n";
        $headers = $email->getHeaders(['from', 'sender', 'replyTo', 'readReceipt', 'returnPath', 'to', 'cc', 'bcc']);
        $to = $headers['To'];
        unset($headers['To']);
        foreach ($headers as $key => $header) {
            $headers[$key] = str_replace("\r\n", '', $header);
        }
        $headers = $this->_headersToString($headers, $eol);
        $subject = str_replace("\r\n", '', $email->subject());
        $to = str_replace("\r\n", '', $to);

        $message = implode($eol, $email->message());

        $params = $this->_config['additionalParameters'] ?? '';
        $this->_mail($to, $subject, $message, $headers, $params);

        $headers .= $eol . 'Subject: ' . $subject;
        $headers .= $eol . 'To: ' . $to;

        return ['headers' => $headers, 'message' => $message];
    }

    /**
     * Wraps internal function mail() and throws exception instead of errors if anything goes wrong
     *
     * @param string $to email's recipient
     * @param string $subject email's subject
     * @param string $message email's body
     * @param array|string $headers email's custom headers
     * @param string $params additional params for sending email
     * @throws SocketException if mail could not be sent
     * @return void
     */
    protected function _mail(string $to, string $subject, string $message, array|string $headers = [], string $params = ''): void
    {
        $errors = [];
        set_error_handler(function ($errno, $errstr, $errfile, $errline) use (&$errors) {
            $errors[] = [
                'level' => $errno,
                'message' => $errstr,
                'file' => $errfile,
                'line' => $errline
            ];
            return true;
        });
        $result = mail($to, $subject, $message, $headers, $params);
        restore_error_handler();

        if (!$result) {
            $lastError = !empty($errors) ? end($errors) : null;
            $msg = 'Could not send email: ' . ($lastError['message'] ?? 'unknown');

            throw new SocketException($msg);
        }
    }
}
