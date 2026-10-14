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
 * Asserts that a string is in the subject of an email.
 *
 * The subject is MIME-decoded first (`CakeEmail::subject()` returns it encoded).
 *
 * Backport of `Cake\TestSuite\Constraint\Email\MailSubjectContains` from CakePHP 5.x
 * (https://github.com/cakephp/cakephp/blob/5.x/src/TestSuite/Constraint/Email/MailSubjectContains.php).
 *
 * @package       Cake.TestSuite.Constraint.Email
 */
class MailSubjectContainsConstraint extends MailConstraintBase
{
    /**
     * Checks constraint
     *
     * @param mixed $other Constraint check
     * @return bool
     * @throws \InvalidArgumentException When the expected value is not a string.
     */
    public function matches($other): bool
    {
        if (!is_string($other)) {
            throw new InvalidArgumentException(
                'Invalid data type, must be a string.',
            );
        }
        foreach ($this->getMessages() as $message) {
            if (str_contains($this->_getSubject($message), $other)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns the decoded subject of an email.
     *
     * @param CakeEmail $email The email.
     * @return string
     */
    protected function _getSubject(CakeEmail $email): string
    {
        return mb_decode_mimeheader((string)$email->subject());
    }

    /**
     * Returns the subjects of all messages
     * respects $this->at
     *
     * @return string
     */
    protected function getAssertedMessages(): string
    {
        $messageMembers = [];
        foreach ($this->getMessages() as $message) {
            $messageMembers[] = $this->_getSubject($message);
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
            return sprintf('is in an email subject #%d', $this->at) . $this->getAssertedMessages();
        }

        return 'is in an email subject' . $this->getAssertedMessages();
    }
}
