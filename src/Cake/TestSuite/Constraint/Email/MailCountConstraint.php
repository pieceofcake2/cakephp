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
 * Asserts the number of sent emails.
 *
 * Backport of `Cake\TestSuite\Constraint\Email\MailCount` from CakePHP 5.x
 * (https://github.com/cakephp/cakephp/blob/5.x/src/TestSuite/Constraint/Email/MailCount.php).
 *
 * @package       Cake.TestSuite.Constraint.Email
 */
class MailCountConstraint extends MailConstraintBase
{
    /**
     * Checks constraint
     *
     * @param mixed $other Constraint check
     * @return bool
     */
    public function matches($other): bool
    {
        return count($this->getMessages()) === $other;
    }

    /**
     * Assertion message string
     *
     * @return string
     */
    public function toString(): string
    {
        return 'emails were sent';
    }
}
