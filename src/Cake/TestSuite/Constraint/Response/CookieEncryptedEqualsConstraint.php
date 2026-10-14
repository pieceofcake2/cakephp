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

App::uses('CookieEqualsConstraint', 'TestSuite/Constraint/Response');

/**
 * Asserts the decrypted value of a cookie written by `CookieComponent`.
 *
 * The value is decrypted like `CookieComponent` does (see `CookieCryptTrait`).
 *
 * Backport of `Cake\TestSuite\Constraint\Response\CookieEncryptedEquals` from CakePHP 5.x
 * (https://github.com/cakephp/cakephp/blob/5.x/src/TestSuite/Constraint/Response/CookieEncryptedEquals.php).
 *
 * @package       Cake.TestSuite.Constraint.Response
 */
class CookieEncryptedEqualsConstraint extends CookieEqualsConstraint
{
    use CookieCryptTrait;

    /**
     * The encryption key.
     *
     * @var string
     */
    protected string $key;

    /**
     * The encryption mode (`cipher`, `aes` or `rijndael`).
     *
     * @var string
     */
    protected string $mode;

    /**
     * Constructor.
     *
     * @param CakeResponse|null $response A response instance.
     * @param string $cookieName Cookie name
     * @param string $mode Mode
     * @param string $key Key
     */
    public function __construct(?CakeResponse $response, string $cookieName, string $mode, string $key)
    {
        parent::__construct($response, $cookieName);
        $this->key = $key;
        $this->mode = $mode;
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

        return $cookie !== null && $this->_decrypt($cookie['value'], $this->mode) === $other;
    }

    /**
     * Assertion message
     *
     * @return string
     */
    public function toString(): string
    {
        return sprintf("is encrypted in cookie '%s'", $this->cookieName);
    }

    /**
     * Returns the encryption key
     *
     * @return string
     */
    protected function _getCookieEncryptionKey(): string
    {
        return $this->key;
    }
}
