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
 * Asserts the value of a response header.
 *
 * Backport of `Cake\TestSuite\Constraint\Response\HeaderEquals` from CakePHP 5.x
 * (https://github.com/cakephp/cakephp/blob/5.x/src/TestSuite/Constraint/Response/HeaderEquals.php).
 *
 * @package       Cake.TestSuite.Constraint.Response
 */
class HeaderEqualsConstraint extends ResponseConstraintBase
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
        return $this->_getHeaderLine($this->headerName) === $other;
    }

    /**
     * Assertion message
     *
     * @return string
     */
    public function toString(): string
    {
        $responseHeader = $this->_getHeaderLine($this->headerName);

        return sprintf("equals content in header '%s' (`%s`)", $this->headerName, $responseHeader);
    }
}
