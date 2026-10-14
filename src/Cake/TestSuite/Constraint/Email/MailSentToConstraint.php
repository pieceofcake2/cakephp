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

App::uses('MailSentWithConstraint', 'TestSuite/Constraint/Email');

/**
 * Asserts that an email was sent to an address.
 *
 * Backport of `Cake\TestSuite\Constraint\Email\MailSentTo` from CakePHP 5.x
 * (https://github.com/cakephp/cakephp/blob/5.x/src/TestSuite/Constraint/Email/MailSentTo.php).
 *
 * @package       Cake.TestSuite.Constraint.Email
 */
class MailSentToConstraint extends MailSentWithConstraint
{
    /**
     * The `CakeEmail` property (getter) to check.
     *
     * @var string
     */
    protected string $method = 'to';

    /**
     * Assertion message string
     *
     * @return string
     */
    public function toString(): string
    {
        if ($this->at !== null) {
            return sprintf('was sent email #%d', $this->at);
        }

        return 'was sent an email';
    }
}
