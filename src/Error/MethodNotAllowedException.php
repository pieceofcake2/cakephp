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
 * @link          https://book.cakephp.org/2.0/en/development/testing.html
 * @package       Cake.Error
 * @since         CakePHP(tm) v 2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Error;

/**
 * Represents an HTTP 405 error.
 *
 * @package       Cake.Error
 */
class MethodNotAllowedException extends HttpException
{
    /**
     * Constructor
     *
     * @param string|null $message If no message is given 'Method Not Allowed' will be the message
     * @param int $code Status code, defaults to 405
     */
    public function __construct(?string $message = null, int $code = 405)
    {
        if (empty($message)) {
            $message = 'Method Not Allowed';
        }

        parent::__construct($message, $code);
    }
}
