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

App::uses('MailContainsConstraint', 'TestSuite/Constraint/Email');

/**
 * Asserts that a string is in the text message of an email.
 *
 * Backport of `Cake\TestSuite\Constraint\Email\MailContainsText` from CakePHP 5.x
 * (https://github.com/cakephp/cakephp/blob/5.x/src/TestSuite/Constraint/Email/MailContainsText.php).
 *
 * @package       Cake.TestSuite.Constraint.Email
 */
class MailContainsTextConstraint extends MailContainsConstraint
{
    /**
     * Message type to check.
     *
     * @var string|null
     */
    protected ?string $type = CakeEmail::MESSAGE_TEXT;

    /**
     * Assertion message string
     *
     * @return string
     */
    public function toString(): string
    {
        if ($this->at !== null) {
            return sprintf('is in the text message of email #%d', $this->at) . $this->getAssertedMessages();
        }

        return 'is in the text message of an email' . $this->getAssertedMessages();
    }
}
