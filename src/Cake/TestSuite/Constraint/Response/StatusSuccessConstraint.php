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
 * Asserts that the response status code is between 200 and 308.
 *
 * Backport of `Cake\TestSuite\Constraint\Response\StatusSuccess` from CakePHP 5.x
 * (https://github.com/cakephp/cakephp/blob/5.x/src/TestSuite/Constraint/Response/StatusSuccess.php).
 *
 * @package       Cake.TestSuite.Constraint.Response
 */
class StatusSuccessConstraint extends StatusCodeConstraintBase
{
    /**
     * The status code range.
     *
     * @var array<int>|int
     */
    protected array|int $code = [200, 308];

    /**
     * Assertion message
     *
     * @return string
     */
    public function toString(): string
    {
        return sprintf('%d is between 200 and 308', $this->response->statusCode());
    }
}
