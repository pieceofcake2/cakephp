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

App::uses('ResponseConstraintBase', 'TestSuite/Constraint/Response');

/**
 * Asserts that the response body matches a PCRE pattern.
 *
 * Backport of `Cake\TestSuite\Constraint\Response\BodyRegExp` from CakePHP 5.x
 * (https://github.com/cakephp/cakephp/blob/5.x/src/TestSuite/Constraint/Response/BodyRegExp.php).
 *
 * @package       Cake.TestSuite.Constraint.Response
 */
class BodyRegExpConstraint extends ResponseConstraintBase
{
    /**
     * Checks assertion
     *
     * @param mixed $other Expected pattern
     * @return bool
     */
    public function matches($other): bool
    {
        return preg_match((string)$other, $this->_getBodyAsString()) > 0;
    }

    /**
     * Assertion message
     *
     * @return string
     */
    public function toString(): string
    {
        return 'PCRE pattern found in response body';
    }

    /**
     * @param mixed $other Expected
     * @return string
     */
    public function failureDescription($other): string
    {
        return '`' . $other . '`' . ' ' . $this->toString();
    }
}
