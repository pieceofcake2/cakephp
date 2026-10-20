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
 * PostgresTestModel class
 *
 * @package       Cake.Test.Case.Model.Datasource.Database
 */
class PostgresTestModel extends Model
{
    /**
     * useTable property
     *
     * @var string|bool|null
     */
    public $useTable = false;

    /**
     * belongsTo property
     *
     * @var array
     */
    public $belongsTo = [
        'PostgresClientTestModel' => [
            'foreignKey' => 'client_id',
        ],
    ];

    /**
     * find method
     *
     * @param string|null $type
     * @param array|null $query
     * @param mixed $order
     * @param mixed $recursive
     * @return array|int|false|null
     */
    public function find($conditions = null, $fields = null, $order = null, $recursive = null): mixed
    {
        return $conditions;
    }

    /**
     * findAll method
     *
     * @param mixed $conditions
     * @param mixed $fields
     * @param mixed $order
     * @param mixed $recursive
     * @return mixed
     */
    public function findAll($conditions = null, $fields = null, $order = null, $recursive = null): mixed
    {
        return $conditions;
    }

    /**
     * schema method
     *
     * @return array|null
     */
    public function schema($field = false): mixed
    {
        return [
            'id' => ['type' => 'integer', 'null' => '', 'default' => '', 'length' => '8'],
            'client_id' => ['type' => 'integer', 'null' => '', 'default' => '0', 'length' => '11'],
            'name' => ['type' => 'string', 'null' => '', 'default' => '', 'length' => '255'],
            'login' => ['type' => 'string', 'null' => '', 'default' => '', 'length' => '255'],
            'passwd' => ['type' => 'string', 'null' => '1', 'default' => '', 'length' => '255'],
            'addr_1' => ['type' => 'string', 'null' => '1', 'default' => '', 'length' => '255'],
            'addr_2' => ['type' => 'string', 'null' => '1', 'default' => '', 'length' => '25'],
            'zip_code' => ['type' => 'string', 'null' => '1', 'default' => '', 'length' => '155'],
            'city' => ['type' => 'string', 'null' => '1', 'default' => '', 'length' => '155'],
            'country' => ['type' => 'string', 'null' => '1', 'default' => '', 'length' => '155'],
            'phone' => ['type' => 'string', 'null' => '1', 'default' => '', 'length' => '155'],
            'fax' => ['type' => 'string', 'null' => '1', 'default' => '', 'length' => '155'],
            'url' => ['type' => 'string', 'null' => '1', 'default' => '', 'length' => '255'],
            'email' => ['type' => 'string', 'null' => '1', 'default' => '', 'length' => '155'],
            'comments' => ['type' => 'text', 'null' => '1', 'default' => '', 'length' => ''],
            'last_login' => ['type' => 'datetime', 'null' => '1', 'default' => '', 'length' => ''],
            'created' => ['type' => 'date', 'null' => '1', 'default' => '', 'length' => ''],
            'updated' => ['type' => 'datetime', 'null' => '1', 'default' => '', 'length' => null],
            'some_uuid' => ['type' => 'uuid', 'null' => '1', 'default' => null],
        ];
    }
}
