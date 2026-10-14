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
 * Asserts the value at a `Hash::get()` path of the session data.
 *
 * Backport of `Cake\TestSuite\Constraint\Session\SessionEquals` from CakePHP 5.x
 * (https://github.com/cakephp/cakephp/blob/5.x/src/TestSuite/Constraint/Session/SessionEquals.php).
 *
 * Reads `$_SESSION` directly, as `CakeSession` stores its data there.
 *
 * @package       Cake.TestSuite.Constraint.Session
 */
class SessionEqualsConstraint extends Constraint
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
     * Compare session value
     *
     * @param mixed $other Value to compare with
     * @return bool
     */
    public function matches($other): bool
    {
        return Hash::get($_SESSION ?? [], $this->path) === $other;
    }

    /**
     * Assertion message string
     *
     * @return string
     */
    public function toString(): string
    {
        return sprintf("is in session path '%s'", $this->path);
    }
}
