<?php
/**
 * Exceptions file. Contains the various exceptions CakePHP will throw until they are
 * moved into their permanent location.
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://book.cakephp.org/2.0/en/development/testing.html
 * @package       Cake.Error
 * @since         CakePHP(tm) v 2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Error;

use Cake\Network\CakeResponse;
use Exception;
use RuntimeException;

/**
 * Security exception - used when SecurityComponent detects any issue with the current request
 *
 * @package       Cake.Error
 */
class SecurityException extends BadRequestException
{
    /**
     * Security Exception type
     *
     * @var string
     */
    protected $_type = 'secure';

    /**
     * Reason for request blackhole
     *
     * @var string
     */
    protected $_reason = null;

    /**
     * Getter for type
     *
     * @return string
     */
    public function getType()
    {
        return $this->_type;
    }

    /**
     * Set Message
     *
     * @param string $message Exception message
     * @return void
     */
    public function setMessage($message)
    {
        $this->message = $message;
    }

    /**
     * Set Reason
     *
     * @param string|null $reason Reason details
     * @return void
     */
    public function setReason($reason = null)
    {
        $this->_reason = $reason;
    }

    /**
     * Get Reason
     *
     * @return string
     */
    public function getReason()
    {
        return $this->_reason;
    }
}

class_alias(SecurityException::class, 'SecurityException');
