<?php
/**
 * Test App TestNlValidation class
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
 * @package       Cake.Test.TestApp.Lib.Utility
 * @since         CakePHP(tm) v 1.2.0.4206
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

/**
 * TestNlValidation class
 *
 * Used to test pass through of Validation
 *
 * @package       Cake.Test.TestApp.Lib.Utility
 */
class TestNlValidation
{
    /**
     * postal function, for testing postal pass through.
     *
     * @param string $check
     * @return void
     */
    public static function postal($check): mixed
    {
        return true;
    }

    /**
     * ssn function for testing ssn pass through
     *
     * @return void
     */
    public static function ssn($check): mixed
    {
        return true;
    }
}
