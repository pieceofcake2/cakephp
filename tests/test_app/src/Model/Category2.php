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

use Cake\TestSuite\Fixture\CakeTestModel;

/**
 * Category2 class
 *
 * @package       Cake.Test.Case.Model
 */
class Category2 extends CakeTestModel
{
    /**
     * name property
     *
     * @var string|null
     */
    public $name = 'Category2';

    /**
     * table property
     *
     * @var string|bool
     */
    public $table = 'category';

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
        'Group' => [
            'className' => 'Group',
            'foreignKey' => 'group_id',
        ],
        'ParentCat' => [
            'className' => 'Category2',
            'foreignKey' => 'parent_id',
        ],
    ];

    /**
     * hasMany property
     *
     * @var array
     */
    public $hasMany = [
        'ChildCat' => [
            'className' => 'Category2',
            'foreignKey' => 'parent_id',
        ],
        'Article2' => [
            'className' => 'Article2',
            'order' => 'Article2.published_date DESC',
            'foreignKey' => 'category_id',
            'limit' => '3'],
    ];

    /**
     * schema method
     *
     * @return array|null
     */
    public function schema($field = false): mixed
    {
        if (!isset($this->_schema)) {
            $this->_schema = [
                'id' => ['type' => 'integer', 'null' => false, 'default' => '', 'length' => '10'],
                'group_id' => ['type' => 'integer', 'null' => false, 'default' => '', 'length' => '10'],
                'parent_id' => ['type' => 'integer', 'null' => false, 'default' => '', 'length' => '10'],
                'name' => ['type' => 'string', 'null' => false, 'default' => '', 'length' => '255'],
                'icon' => ['type' => 'string', 'null' => false, 'default' => '', 'length' => '255'],
                'description' => ['type' => 'text', 'null' => false, 'default' => '', 'length' => null],

            ];
        }

        return $this->_schema;
    }
}
