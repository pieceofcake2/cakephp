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
 * @package       Cake.TestSuite.Constraint.Session
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Constraint\Constraint;

/**
 * Asserts that a flash message parameter equals a value.
 *
 * Backport of `Cake\TestSuite\Constraint\Session\FlashParamEquals` from CakePHP 5.x
 * (https://github.com/cakephp/cakephp/blob/5.x/src/TestSuite/Constraint/Session/FlashParamEquals.php).
 *
 * CakePHP 2 stores flash messages in the `Message.<key>` session path (CakePHP 5
 * uses `Flash.<key>`). Both the list format of `FlashComponent::set()` /
 * `SessionComponent::setFlash()` and a single message array are supported.
 *
 * @package       Cake.TestSuite.Constraint.Session
 */
class FlashParamEqualsConstraint extends Constraint
{
    /**
     * The session data to read the flash messages from.
     *
     * @var array
     */
    protected array $session;

    /**
     * The flash key.
     *
     * @var string
     */
    protected string $key;

    /**
     * The flash message parameter (`message`, `element`, ...).
     *
     * @var string
     */
    protected string $param;

    /**
     * The index of the flash message, or null for any.
     *
     * @var int|null
     */
    protected ?int $at = null;

    /**
     * Constructor
     *
     * @param array|null $session The session data (`$_SESSION`), or null if no request was made.
     * @param string $key Flash key
     * @param string $param Param to check
     * @param int|null $at Expected index
     * @throws \PHPUnit\Framework\AssertionFailedError When there is no session data.
     */
    public function __construct(?array $session, string $key, string $param, ?int $at = null)
    {
        if ($session === null) {
            $message = 'There is no stored session data. Perhaps you need to run a request?';
            $message .= ' Additionally, ensure `$this->enableRetainFlashMessages()` has been enabled for the test.';
            throw new AssertionFailedError($message);
        }

        $this->session = $session;
        $this->key = $key;
        $this->param = $param;
        $this->at = $at;
    }

    /**
     * Returns the flash messages to check.
     *
     * @return array
     */
    protected function _getMessages(): array
    {
        $messages = Hash::get($this->session, 'Message.' . $this->key);
        if (!is_array($messages)) {
            return [];
        }
        if (isset($messages['message']) && !is_array($messages['message'])) {
            $messages = [$messages];
        }
        if ($this->at !== null) {
            return isset($messages[$this->at]) ? [$messages[$this->at]] : [];
        }

        return $messages;
    }

    /**
     * Compare to flash message(s)
     *
     * @param mixed $other Value to compare with
     * @return bool
     */
    public function matches($other): bool
    {
        foreach ($this->_getMessages() as $message) {
            if (!isset($message[$this->param])) {
                continue;
            }
            if ($message[$this->param] === $other) {
                return true;
            }
        }

        return false;
    }

    /**
     * Assertion message string
     *
     * @return string
     */
    public function toString(): string
    {
        if ($this->at !== null) {
            return sprintf("is in '%s' %s #%d", $this->key, $this->param, $this->at);
        }

        return sprintf("is in '%s' %s", $this->key, $this->param);
    }
}
