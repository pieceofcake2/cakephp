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
 * @package       Cake.TestSuite.Constraint.Response
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

App::uses('BodyContainsConstraint', 'TestSuite/Constraint/Response');

/**
 * Asserts that a string is not in the response body.
 *
 * Backport of `Cake\TestSuite\Constraint\Response\BodyNotContains` from CakePHP 5.x
 * (https://github.com/cakephp/cakephp/blob/5.x/src/TestSuite/Constraint/Response/BodyNotContains.php).
 *
 * @package       Cake.TestSuite.Constraint.Response
 */
class BodyNotContainsConstraint extends BodyContainsConstraint
{
    /**
     * Checks assertion
     *
     * @param mixed $other Expected value
     * @return bool
     */
    public function matches($other): bool
    {
        return parent::matches($other) === false;
    }

    /**
     * Assertion message
     *
     * @return string
     */
    public function toString(): string
    {
        return 'is not in response body';
    }
}
