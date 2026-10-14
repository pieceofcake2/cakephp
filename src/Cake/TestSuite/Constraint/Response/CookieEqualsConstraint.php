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
 * Asserts the value of a cookie set in the response.
 *
 * Backport of `Cake\TestSuite\Constraint\Response\CookieEquals` from CakePHP 5.x
 * (https://github.com/cakephp/cakephp/blob/5.x/src/TestSuite/Constraint/Response/CookieEquals.php).
 *
 * @package       Cake.TestSuite.Constraint.Response
 */
class CookieEqualsConstraint extends ResponseConstraintBase
{
    /**
     * The cookie name.
     *
     * @var string
     */
    protected string $cookieName;

    /**
     * Constructor.
     *
     * @param CakeResponse|null $response A response instance.
     * @param string $cookieName Cookie name
     */
    public function __construct(?CakeResponse $response, string $cookieName)
    {
        parent::__construct($response);
        $this->cookieName = $cookieName;
    }

    /**
     * Checks assertion
     *
     * @param mixed $other Expected content
     * @return bool
     */
    public function matches($other): bool
    {
        $cookie = $this->_readCookie($this->cookieName);

        return $cookie !== null && $cookie['value'] === $other;
    }

    /**
     * Assertion message
     *
     * @return string
     */
    public function toString(): string
    {
        return sprintf("is in cookie '%s'", $this->cookieName);
    }
}
