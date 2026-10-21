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
 * TestModel5 class
 *
 * @package       Cake.Test.Case.Model
 */
class TestModel5 extends CakeTestModel
{
    /**
     * name property
     *
     * @var string|null
     */
    public $name = 'TestModel5';

    /**
     * table property
     *
     * @var string|bool
     */
    public $table = 'test_model5';

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
    public $belongsTo = ['TestModel4' => [
        'className' => TestModel4::class,
        'foreignKey' => 'test_model4_id',
    ]];

    /**
     * hasMany property
     *
     * @var array
     */
    public $hasMany = ['TestModel6' => [
        'className' => TestModel6::class,
        'foreignKey' => 'test_model5_id',
    ]];

    /**
     * schema method
     *
     * @return array|null
     */
    public function schema($field = false): mixed
    {
        if (!isset($this->_schema)) {
            $this->_schema = [
                'id' => ['type' => 'integer', 'null' => '', 'default' => '', 'length' => '8'],
                'test_model4_id' => ['type' => 'integer', 'null' => '', 'default' => '', 'length' => '8'],
                'name' => ['type' => 'string', 'null' => '', 'default' => '', 'length' => '255'],
                'created' => ['type' => 'date', 'null' => '1', 'default' => '', 'length' => ''],
                'updated' => ['type' => 'datetime', 'null' => '1', 'default' => '', 'length' => null],
            ];
        }

        return $this->_schema;
    }
}
