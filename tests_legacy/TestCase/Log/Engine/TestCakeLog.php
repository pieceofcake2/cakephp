<?php
/**
 * TestCakeLog file
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
 * @package       Cake.Test.Case.Log.Engine
 * @since         CakePHP(tm) v 1.3
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Test\TestCase\Log\Engine;

use Cake\Log\CakeLog;

/**
 * TestCakeLog
 *
 * @package       Cake.Test.Case.Log.Engine
 */
class TestCakeLog extends CakeLog
{
    public static function replace($key, &$engine)
    {
        static::$_Collection->{$key} = $engine;
    }
}
