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
 * Constructor
 *
 * @param array|string $message Error message
 * @param int $code Error code
 */
public function __construct(array|string $message, int $code = 404)
{
    parent::__construct($message, $code);
}
}
