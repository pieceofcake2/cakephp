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
 * Base class for status code constraints.
 *
 * Backport of `Cake\TestSuite\Constraint\Response\StatusCodeBase` from CakePHP 5.x
 * (https://github.com/cakephp/cakephp/blob/5.x/src/TestSuite/Constraint/Response/StatusCodeBase.php).
 *
 * @package       Cake.TestSuite.Constraint.Response
 */
abstract class StatusCodeConstraintBase extends ResponseConstraintBase
{
    /**
     * The expected status code, or `[min, max]` range.
     *
     * @var array<int>|int
     */
    protected array|int $code;

    /**
     * Check assertion
     *
     * @param mixed $other Array of min/max status codes, or a single code
     * @return bool
     */
    public function matches($other): bool
    {
        if (!$other) {
            $other = $this->code;
        }

        if (is_array($other)) {
            return $this->statusCodeBetween($other[0], $other[1]);
        }

        return $this->response->statusCode() === $other;
    }

    /**
     * Helper for checking status codes
     *
     * @param int $min Min status code (inclusive)
     * @param int $max Max status code (inclusive)
     * @return bool
     */
    protected function statusCodeBetween(int $min, int $max): bool
    {
        return $this->response->statusCode() >= $min && $this->response->statusCode() <= $max;
    }

    /**
     * Overwrites the descriptions so we can remove the automatic "expected" message
     *
     * @param mixed $other Value
     * @return string
     */
    protected function failureDescription($other): string
    {
        return $this->toString();
    }
}
