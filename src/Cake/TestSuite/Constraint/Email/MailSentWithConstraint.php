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
 * Asserts that an email was sent with a property value.
 *
 * The property is read with the `CakeEmail` getter of the same name (e.g. `subject`,
 * `emailFormat`, `to`, `cc`, `bcc`, `from`, `replyTo`, `sender`, `returnPath`,
 * `readReceipt`, `messageId`, `domain`, `theme`). For address properties, the
 * expected value can be a single email address, or the whole `[email => name]` array.
 *
 * Backport of `Cake\TestSuite\Constraint\Email\MailSentWith` from CakePHP 5.x
 * (https://github.com/cakephp/cakephp/blob/5.x/src/TestSuite/Constraint/Email/MailSentWith.php).
 *
 * @package       Cake.TestSuite.Constraint.Email
 */
class MailSentWithConstraint extends MailConstraintBase
{
    /**
     * The `CakeEmail` property (getter) to check.
     *
     * @var string
     */
    protected string $method;

    /**
     * Constructor
     *
     * @param int|null $at At
     * @param string|null $method Method
     */
    public function __construct(?int $at = null, ?string $method = null)
    {
        if ($method !== null) {
            $this->method = $method;
        }

        parent::__construct($at);
    }

    /**
     * Checks constraint
     *
     * @param mixed $other Constraint check
     * @return bool
     */
    public function matches($other): bool
    {
        $addressMethods = ['to', 'cc', 'bcc', 'from', 'replyTo', 'sender', 'returnPath', 'readReceipt'];
        foreach ($this->getMessages() as $email) {
            if (!method_exists($email, $this->method)) {
                continue;
            }
            $value = $email->{$this->method}();
            if ($value === $other) {
                return true;
            }
            if (
                !is_array($other)
                && is_array($value)
                && in_array($this->method, $addressMethods, true)
                && array_key_exists((string)$other, $value)
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Assertion message string
     *
     * @return string
     */
    public function toString(): string
    {
        if ($this->at !== null) {
            return sprintf('is in email #%d `%s`', $this->at, $this->method);
        }

        return sprintf('is in an email `%s`', $this->method);
    }
}
