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
 * TestModel7 class
 *
 * @package       Cake.Test.Case.Model
 */
class TestModel7 extends CakeTestModel
{
    /**
     * name property
     *
     * @var string|null
     */
    public $name = 'TestModel7';

    /**
     * table property
     *
     * @var string|bool
     */
    public $table = 'test_model7';

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
        if (!isset($this->_schema)) {
            $this->_schema = [
                'id' => ['type' => 'integer', 'null' => '', 'default' => '', 'length' => '8'],
                'name' => ['type' => 'string', 'null' => '', 'default' => '', 'length' => '255'],
                'created' => ['type' => 'date', 'null' => '1', 'default' => '', 'length' => ''],
                'updated' => ['type' => 'datetime', 'null' => '1', 'default' => '', 'length' => null],
            ];
        }

        return $this->_schema;
    }
}
