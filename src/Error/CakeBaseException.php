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

use RuntimeException;

/**
 * Base class that all Exceptions extend.
 *
 * @package       Cake.Error
 */
class CakeBaseException extends RuntimeException
{
    /**
     * Array of headers to be passed to CakeResponse::header()
     *
     * @var array
     */
    protected $_responseHeaders = null;

    /**
     * Get/set the response header to be used
     *
     * @param array|string $header An array of header strings or a single header string
     *  - an associative array of "header name" => "header value"
     *  - an array of string headers is also accepted
     * @param string $value The header value.
     * @return array
     * @see CakeResponse::header()
     */
    public function responseHeader($header = null, $value = null)
    {
        if ($header) {
            if (is_array($header)) {
                return $this->_responseHeaders = $header;
            }
            $this->_responseHeaders = [$header => $value];
        }

        return $this->_responseHeaders;
    }
}
