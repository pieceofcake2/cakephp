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

App::uses('HeaderContainsConstraint', 'TestSuite/Constraint/Response');

/**
 * Asserts that a response header does not contain a string.
 *
 * Backport of `Cake\TestSuite\Constraint\Response\HeaderNotContains` from CakePHP 5.x
 * (https://github.com/cakephp/cakephp/blob/5.x/src/TestSuite/Constraint/Response/HeaderNotContains.php).
 *
 * @package       Cake.TestSuite.Constraint.Response
 */
class HeaderNotContainsConstraint extends HeaderContainsConstraint
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
        return sprintf(
            "is not in header '%s' (`%s`)",
            $this->headerName,
            $this->_getHeaderLine($this->headerName),
        );
    }
}
