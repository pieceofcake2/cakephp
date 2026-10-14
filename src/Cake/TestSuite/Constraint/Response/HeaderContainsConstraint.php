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

App::uses('HeaderEqualsConstraint', 'TestSuite/Constraint/Response');

/**
 * Asserts that a response header contains a string.
 *
 * Backport of `Cake\TestSuite\Constraint\Response\HeaderContains` from CakePHP 5.x
 * (https://github.com/cakephp/cakephp/blob/5.x/src/TestSuite/Constraint/Response/HeaderContains.php).
 *
 * @package       Cake.TestSuite.Constraint.Response
 */
class HeaderContainsConstraint extends HeaderEqualsConstraint
{
    /**
     * Checks assertion
     *
     * @param mixed $other Expected content
     * @return bool
     */
    public function matches($other): bool
    {
        return mb_strpos($this->_getHeaderLine($this->headerName), (string)$other) !== false;
    }

    /**
     * Assertion message
     *
     * @return string
     */
    public function toString(): string
    {
        return sprintf(
            "is in header '%s' (`%s`)",
            $this->headerName,
            $this->_getHeaderLine($this->headerName),
        );
    }
}
