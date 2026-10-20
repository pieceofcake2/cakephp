<?php
/**
 * FaultyExceptionRenderer file
 *
 * CakePHP(tm) Tests <https://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.Test.TestApp.Lib.Error
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

/**
 * A faulty ExceptionRenderer to test nesting.
 */
class FaultyExceptionRenderer extends ExceptionRenderer
{
    /**
     * Dummy rendering implementation.
     *
     * @return void
     * @throws Exception
     */
    public function render(): void
    {
        throw new Exception('Error from renderer.');
    }
}
