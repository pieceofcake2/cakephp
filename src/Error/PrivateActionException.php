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
 * Private Action exception - used when a controller action
 * starts with a  `_`.
 *
 * @package       Cake.Error
 */
class PrivateActionException extends CakeException
{
    protected $_messageTemplate = 'Private Action %s::%s() is not directly accessible.';

//@codingStandardsIgnoreStart

    public function __construct($message, $code = 404, ?Exception $previous = null)
    {
        // CakeException::__construct() does not accept a previous exception,
        // so the message template is applied here and RuntimeException is called directly.
        if (is_array($message)) {
            $this->_attributes = $message;
            $message = __d('cake_dev', $this->_messageTemplate, $message);
        }
        RuntimeException::__construct($message, $code, $previous);
    }

//@codingStandardsIgnoreEnd
}

class_alias(PrivateActionException::class, 'PrivateActionException');
