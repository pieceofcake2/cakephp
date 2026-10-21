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

namespace TestApp\Model\Datasource;

use Cake\Model\Datasource\DboSource;

/**
 * DboFourthTestSource
 *
 * @package       Cake.Test.Case.Model.Datasource
 */
class DboFourthTestSource extends DboSource
{
    public function connect($config = []): void
    {
        $this->connected = true;
    }

    public function cacheMethodFilter($method, $key, $value): mixed
    {
        if ($method === 'name') {
            if ($value === '`menus`') {
                return false;
            } elseif ($key === '1fca740733997f1ebbedacfc7678592a') {
                return false;
            }
        } elseif ($method === 'fields') {
            $endsWithName = preg_grep('/`name`$/', $value);

            return count($endsWithName) === 0;
        }

        return true;
    }
}
