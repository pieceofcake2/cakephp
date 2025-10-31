<?php
/**
 * Test Suite Test App Cache Engine class.
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
 * @package       Cake.Test.TestApp.Lib.Cache.Engine
 * @since         CakePHP(tm) v 1.3
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace TestApp\Cache\Engine;

use Cake\Cache\CacheEngine;

/**
 * TestAppCacheEngine
 *
 * @package       Cake.Test.TestApp.Lib.Cache.Engine
 */
class TestAppCacheEngine extends CacheEngine
{
    public function write(string $key, mixed $value, int $duration): bool
    {
        if ($key === 'fail') {
            return false;
        }

        return true;
    }

    public function read(string $key): mixed
    {
        return true;
    }

    public function increment(string $key, int $offset = 1): int|false
    {
        return 1;
    }

    public function decrement(string $key, int $offset = 1): int|false
    {
        return 0;
    }

    public function delete(string $key): bool
    {
        return true;
    }

    public function clear(bool $check): bool
    {
        return true;
    }

    public function clearGroup(string $group): bool
    {
        return true;
    }

    public function add(string $key, mixed $value, int $duration): bool
    {
        return true;
    }
}
