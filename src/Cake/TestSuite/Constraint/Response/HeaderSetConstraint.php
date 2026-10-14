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
 * Asserts that a response header is set.
 *
 * Backport of `Cake\TestSuite\Constraint\Response\HeaderSet` from CakePHP 5.x
 * (https://github.com/cakephp/cakephp/blob/5.x/src/TestSuite/Constraint/Response/HeaderSet.php).
 *
 * @package       Cake.TestSuite.Constraint.Response
 */
class HeaderSetConstraint extends ResponseConstraintBase
{
    /**
     * The header name.
     *
     * @var string
     */
    protected string $headerName;

    /**
     * Constructor.
     *
     * @param CakeResponse|null $response A response instance.
     * @param string $headerName Header name
     */
    public function __construct(?CakeResponse $response, string $headerName)
    {
        parent::__construct($response);
        $this->headerName = $headerName;
    }

    /**
     * Checks assertion
     *
     * @param mixed $other Expected content
     * @return bool
     */
    public function matches($other): bool
    {
        return $this->_hasHeader($this->headerName);
    }

    /**
     * Assertion message
     *
     * @return string
     */
    public function toString(): string
    {
        return sprintf("response has header '%s'", $this->headerName);
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
