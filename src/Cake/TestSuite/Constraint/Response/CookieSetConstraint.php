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
 * Asserts that a cookie with a non-empty value is set in the response.
 *
 * Backport of `Cake\TestSuite\Constraint\Response\CookieSet` from CakePHP 5.x
 * (https://github.com/cakephp/cakephp/blob/5.x/src/TestSuite/Constraint/Response/CookieSet.php).
 *
 * @package       Cake.TestSuite.Constraint.Response
 */
class CookieSetConstraint extends ResponseConstraintBase
{
    /**
     * Checks assertion
     *
     * @param mixed $other Expected content
     * @return bool
     */
    public function matches($other): bool
    {
        $cookie = $this->_readCookie((string)$other);

        return $cookie !== null && $cookie['value'] !== '';
    }

    /**
     * Assertion message
     *
     * @return string
     */
    public function toString(): string
    {
        return 'cookie is set';
    }
}
