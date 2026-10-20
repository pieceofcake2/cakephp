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
 * Not Implemented Exception - used when an API method is not implemented
 *
 * @package       Cake.Error
 */
class NotImplementedException extends CakeException
{
    protected $_messageTemplate = '%s is not implemented.';

//@codingStandardsIgnoreStart

    public function __construct($message, $code = 501)
    {
        parent::__construct($message, $code);
    }

//@codingStandardsIgnoreEnd
}

class_alias(NotImplementedException::class, 'NotImplementedException');
