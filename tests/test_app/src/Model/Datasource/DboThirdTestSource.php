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
 * DboThirdTestSource
 *
 * @package       Cake.Test.Case.Model.Datasource
 */
class DboThirdTestSource extends DboSource
{
    public function connect($config = []): void
    {
        $this->connected = true;
    }

    public function cacheMethodHasher($value): mixed
    {
        return hash('sha1', $value);
    }
}
