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
 * Asserts that a string is in the response body.
 *
 * Backport of `Cake\TestSuite\Constraint\Response\BodyContains` from CakePHP 5.x
 * (https://github.com/cakephp/cakephp/blob/5.x/src/TestSuite/Constraint/Response/BodyContains.php).
 *
 * @package       Cake.TestSuite.Constraint.Response
 */
class BodyContainsConstraint extends ResponseConstraintBase
{
    /**
     * Whether to ignore case.
     *
     * @var bool
     */
    protected bool $ignoreCase;

    /**
     * Constructor.
     *
     * @param CakeResponse|null $response The response to inspect.
     * @param bool $ignoreCase Whether to ignore case.
     */
    public function __construct(?CakeResponse $response, bool $ignoreCase = false)
    {
        parent::__construct($response);
        $this->ignoreCase = $ignoreCase;
    }

    /**
     * Checks assertion
     *
     * @param mixed $other Expected content
     * @return bool
     */
    public function matches($other): bool
    {
        $method = 'mb_strpos';
        if ($this->ignoreCase) {
            $method = 'mb_stripos';
        }

        return $method($this->_getBodyAsString(), (string)$other) !== false;
    }

    /**
     * Assertion message
     *
     * @return string
     */
    public function toString(): string
    {
        return 'is in response body';
    }
}
