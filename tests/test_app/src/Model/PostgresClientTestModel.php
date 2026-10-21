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

namespace TestApp\Model;

use Cake\Model\Model;

/**
 * PostgresClientTestModel class
 *
 * @package       Cake.Test.Case.Model.Datasource.Database
 */
class PostgresClientTestModel extends Model
{
    /**
     * useTable property
     *
     * @var string|bool|null
     */
    public $useTable = false;

    /**
     * schema method
     *
     * @return array|null
     */
    public function schema($field = false): mixed
    {
        return [
            'id' => ['type' => 'integer', 'null' => '', 'default' => '', 'length' => '8', 'key' => 'primary'],
            'name' => ['type' => 'string', 'null' => '', 'default' => '', 'length' => '255'],
            'email' => ['type' => 'string', 'null' => '1', 'default' => '', 'length' => '155'],
            'created' => ['type' => 'datetime', 'null' => true, 'default' => null, 'length' => ''],
            'updated' => ['type' => 'datetime', 'null' => true, 'default' => null, 'length' => null],
        ];
    }
}
