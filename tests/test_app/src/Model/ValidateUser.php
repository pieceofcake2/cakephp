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
 * ValidateUser class
 *
 * @package       Cake.Test.Case.View.Helper
 */
class ValidateUser extends CakeTestModel
{
    /**
     * useTable property
     *
     * @var string|bool|null
     */
    public $useTable = false;

    /**
     * hasOne property
     *
     * @var array
     */
    public $hasOne = ['ValidateProfile' => [
        'className' => 'ValidateProfile', 'foreignKey' => 'user_id',
    ]];

    /**
     * schema method
     *
     * @var array
     */
    protected $_schema = [
        'id' => ['type' => 'integer', 'null' => '', 'default' => '', 'length' => '8'],
        'name' => ['type' => 'string', 'null' => '', 'default' => '', 'length' => '255'],
        'email' => ['type' => 'string', 'null' => '', 'default' => '', 'length' => '255'],
        'balance' => ['type' => 'float', 'null' => false, 'length' => '5,2'],
        'cost_decimal' => ['type' => 'decimal', 'null' => false, 'length' => '6,3'],
        'null_decimal' => ['type' => 'decimal', 'null' => false, 'length' => null],
        'ratio' => ['type' => 'decimal', 'null' => false, 'length' => '10,6'],
        'population' => ['type' => 'decimal', 'null' => false, 'length' => '15,0'],
        'created' => ['type' => 'date', 'null' => '1', 'default' => '', 'length' => ''],
        'updated' => ['type' => 'datetime', 'null' => '1', 'default' => '', 'length' => null],
    ];

    /**
     * beforeValidate method
     *
     * @return bool
     */
    public function beforeValidate($options = []): mixed
    {
        $this->invalidate('email');

        return false;
    }
}
