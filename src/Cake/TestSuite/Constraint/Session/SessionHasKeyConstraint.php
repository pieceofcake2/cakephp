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
 * @package       Cake.TestSuite.Constraint.Session
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

use PHPUnit\Framework\Constraint\Constraint;

/**
 * Asserts that a `Hash::check()` path exists in the session data.
 *
 * Backport of `Cake\TestSuite\Constraint\Session\SessionHasKey` from CakePHP 5.x
 * (https://github.com/cakephp/cakephp/blob/5.x/src/TestSuite/Constraint/Session/SessionHasKey.php).
 *
 * Reads `$_SESSION` directly, as `CakeSession` stores its data there.
 *
 * @package       Cake.TestSuite.Constraint.Session
 */
class SessionHasKeyConstraint extends Constraint
{
    /**
     * The session path.
     *
     * @var string
     */
    protected string $path;

    /**
     * Constructor
     *
     * @param string $path Session path
     */
    public function __construct(string $path)
    {
        $this->path = $path;
    }

    /**
     * Checks if the path exists in the session
     *
     * @param mixed $other Value to compare with
     * @return bool
     */
    public function matches($other): bool
    {
        return Hash::check($_SESSION ?? [], $this->path);
    }

    /**
     * Assertion message string
     *
     * @return string
     */
    public function toString(): string
    {
        return 'is a path present in the session';
    }
}
