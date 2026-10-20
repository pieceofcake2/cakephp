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
 * TestValidate class
 *
 * @package       Cake.Test.Case.Model
 */
class TestValidate extends CakeTestModel
{
    /**
     * useTable property
     *
     * @var string|bool|null
     */
    public $useTable = false;

    /**
     * name property
     *
     * @var string|null
     */
    public $name = 'TestValidate';

    /**
     * schema property
     *
     * @var array
     */
    protected $_schema = [
        'id' => ['type' => 'integer', 'null' => '', 'default' => '', 'length' => '8'],
        'title' => ['type' => 'string', 'null' => '', 'default' => '', 'length' => '255'],
        'body' => ['type' => 'string', 'null' => '1', 'default' => '', 'length' => ''],
        'number' => ['type' => 'integer', 'null' => '', 'default' => '', 'length' => '8'],
        'created' => ['type' => 'date', 'null' => '1', 'default' => '', 'length' => ''],
        'modified' => ['type' => 'datetime', 'null' => '1', 'default' => '', 'length' => null],
    ];

    /**
     * validateNumber method
     *
     * @param mixed $value
     * @param mixed $options
     * @return void
     */
    public function validateNumber($value, $options): mixed
    {
        $options += ['min' => 0, 'max' => 100];
        $valid = ($value['number'] >= $options['min'] && $value['number'] <= $options['max']);

        return $valid;
    }

    /**
     * validateTitle method
     *
     * @param mixed $value
     * @return void
     */
    public function validateTitle($value): mixed
    {
        return !empty($value) && str_starts_with(strtolower($value['title']), 'title-');
    }
}
