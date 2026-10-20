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
 * Apple class
 *
 * @package       Cake.Test.Case.Model
 */
class Apple extends CakeTestModel
{
    /**
     * name property
     *
     * @var string|null
     */
    public $name = 'Apple';

    /**
     * validate property
     *
     * @var array
     */
    public $validate = ['name' => 'notBlank'];

    /**
     * hasOne property
     *
     * @var array
     */
    public $hasOne = ['Sample'];

    /**
     * hasMany property
     *
     * @var array
     */
    public $hasMany = ['Child' => ['className' => 'Apple', 'dependent' => true]];

    /**
     * belongsTo property
     *
     * @var array
     */
    public $belongsTo = ['Parent' => ['className' => 'Apple', 'foreignKey' => 'apple_id']];
}
