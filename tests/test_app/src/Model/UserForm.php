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
 * UserForm class
 *
 * @package       Cake.Test.Case.View.Helper
 */
class UserForm extends CakeTestModel
{
    /**
     * useTable property
     *
     * @var string|bool|null
     */
    public $useTable = false;

    /**
     * hasMany property
     *
     * @var array
     */
    public $hasMany = [
        'OpenidUrl' => [
            'className' => 'OpenidUrl', 'foreignKey' => 'user_form_id',
        ],
    ];

    /**
     * schema definition
     *
     * @var array
     */
    protected $_schema = [
        'id' => ['type' => 'integer', 'null' => '', 'default' => '', 'length' => '8'],
        'published' => ['type' => 'date', 'null' => true, 'default' => null, 'length' => null],
        'other' => ['type' => 'text', 'null' => true, 'default' => null, 'length' => null],
        'stuff' => ['type' => 'string', 'null' => true, 'default' => null, 'length' => 10],
        'something' => ['type' => 'string', 'null' => true, 'default' => null, 'length' => 255],
        'active' => ['type' => 'boolean', 'null' => false, 'default' => false],
        'created' => ['type' => 'date', 'null' => '1', 'default' => '', 'length' => ''],
        'updated' => ['type' => 'datetime', 'null' => '1', 'default' => '', 'length' => null],
    ];
}
