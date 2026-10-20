<?php
/**
 * CakePHP(tm) Tests <https://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace TestApp\Log\Engine;

use Cake\Log\Engine\BaseLog;

/**
 * Log engine in a namespace
 */
class NamespacedLog extends BaseLog
{
    /**
     * @var array<string>
     */
    public static $messages = [];

    /**
     * @param string $type The type of the message
     * @param string $message The message
     * @return void
     */
    public function write($type, $message)
    {
        static::$messages[] = $type . ': ' . $message;
    }
}
