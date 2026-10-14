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

App::uses('BodyRegExpConstraint', 'TestSuite/Constraint/Response');

/**
 * Asserts that the response body does not match a PCRE pattern.
 *
 * Backport of `Cake\TestSuite\Constraint\Response\BodyNotRegExp` from CakePHP 5.x
 * (https://github.com/cakephp/cakephp/blob/5.x/src/TestSuite/Constraint/Response/BodyNotRegExp.php).
 *
 * @package       Cake.TestSuite.Constraint.Response
 */
class BodyNotRegExpConstraint extends BodyRegExpConstraint
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
        return 'PCRE pattern not found in response body';
    }
}
