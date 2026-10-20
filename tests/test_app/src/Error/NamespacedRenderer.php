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

namespace TestApp\Error;

use Cake\Error\ExceptionRenderer;

/**
 * Exception renderer in a namespace
 */
class NamespacedRenderer extends ExceptionRenderer
{
    /**
     * @return void
     */
    public function render()
    {
        echo 'rendered by ' . static::class;
    }
}
