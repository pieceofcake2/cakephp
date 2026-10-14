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

use PHPUnit\Framework\Constraint\Constraint;

/**
 * Base class for all mail assertion constraints.
 *
 * Backport of `Cake\TestSuite\Constraint\Email\MailConstraintBase` from CakePHP 5.x
 * (https://github.com/cakephp/cakephp/blob/5.x/src/TestSuite/Constraint/Email/MailConstraintBase.php).
 *
 * Emails are captured by `TestEmailTransport` as `CakeEmail` instances.
 *
 * @package       Cake.TestSuite.Constraint.Email
 */
abstract class MailConstraintBase extends Constraint
{
    /**
     * The index of the email to check (0-based), or null for any email.
     *
     * @var int|null
     */
    protected ?int $at = null;

    /**
     * Constructor
     *
     * @param int|null $at At
     */
    public function __construct(?int $at = null)
    {
        $this->at = $at;
    }

    /**
     * Gets the email or emails to check
     *
     * @return array<CakeEmail>
     */
    public function getMessages(): array
    {
        $messages = TestEmailTransport::getMessages();

        if ($this->at !== null) {
            if (!isset($messages[$this->at])) {
                return [];
            }

            return [$messages[$this->at]];
        }

        return $messages;
    }
}
