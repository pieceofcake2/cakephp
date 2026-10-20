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
 * AssociationTest1 class
 *
 * @package       Cake.Test.Case.Model
 */
class AssociationTest1 extends CakeTestModel
{
    /**
     * useTable property
     *
     * @var string|bool|null
     */
    public $useTable = 'join_as';

    /**
     * name property
     *
     * @var string|null
     */
    public $name = 'AssociationTest1';

    /**
     * hasAndBelongsToMany property
     *
     * @var array
     */
    public $hasAndBelongsToMany = ['AssociationTest2' => [
        'unique' => false, 'joinTable' => 'join_as_join_bs', 'foreignKey' => false,
    ]];
}
