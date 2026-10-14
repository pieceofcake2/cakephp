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
 * @package       Cake.TestSuite.Constraint.Email
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

App::uses('MailConstraintBase', 'TestSuite/Constraint/Email');

/**
 * Asserts that a string is in the message of an email.
 *
 * Without a type, the whole rendered message is searched (both parts of a
 * multipart email, including the MIME boundaries and part headers).
 *
 * Backport of `Cake\TestSuite\Constraint\Email\MailContains` from CakePHP 5.x
 * (https://github.com/cakephp/cakephp/blob/5.x/src/TestSuite/Constraint/Email/MailContains.php).
 *
 * Emails are captured by `TestEmailTransport` as `CakeEmail` instances.
 *
 * @package       Cake.TestSuite.Constraint.Email
 */
class MailContainsConstraint extends MailConstraintBase
{
    /**
     * Message type to check: null for the whole message, `html` or `text`.
     *
     * @var string|null
     */
    protected ?string $type = null;

    /**
     * Checks constraint
     *
     * @param mixed $other Constraint check
     * @return bool
     */
    public function matches($other): bool
    {
        $other = preg_quote((string)$other, '/');
        foreach ($this->getMessages() as $message) {
            if (preg_match("/{$other}/", $this->_getBody($message)) > 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns the message of the given type.
     *
     * @param CakeEmail $email The email.
     * @return string
     */
    protected function _getBody(CakeEmail $email): string
    {
        return implode("\r\n", (array)$email->message($this->type));
    }

    /**
     * Returns the type-dependent strings of all messages
     * respects $this->at
     *
     * @return string
     */
    protected function getAssertedMessages(): string
    {
        $messageMembers = [];
        foreach ($this->getMessages() as $message) {
            $messageMembers[] = $this->_getBody($message);
        }
        $result = implode(PHP_EOL, $messageMembers);

        return PHP_EOL . 'was: ' . mb_substr($result, 0, 1000);
    }

    /**
     * Assertion message string
     *
     * @return string
     */
    public function toString(): string
    {
        if ($this->at !== null) {
            return sprintf('is in email #%d', $this->at) . $this->getAssertedMessages();
        }

        return 'is in an email' . $this->getAssertedMessages();
    }
}
