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

App::uses('StatusCodeConstraintBase', 'TestSuite/Constraint/Response');

/**
 * Asserts a specific response status code.
 *
 * Backport of `Cake\TestSuite\Constraint\Response\StatusCode` from CakePHP 5.x
 * (https://github.com/cakephp/cakephp/blob/5.x/src/TestSuite/Constraint/Response/StatusCode.php).
 *
 * @package       Cake.TestSuite.Constraint.Response
 */
class StatusCodeConstraint extends StatusCodeConstraintBase
{
    /**
     * Assertion message
     *
     * @return string
     */
    public function toString(): string
    {
        return sprintf('matches response status code `%d`', $this->response->statusCode());
    }

    /**
     * Failure description
     *
     * @param mixed $other Expected code
     * @return string
     */
    public function failureDescription($other): string
    {
        return '`' . $other . '` ' . $this->toString();
    }
}
