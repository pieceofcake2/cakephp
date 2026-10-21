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
 * NodeAfterFind class
 *
 * @package       Cake.Test.Case.Model
 */
class NodeAfterFind extends CakeTestModel
{
    /**
     * name property
     *
     * @var string|null
     */
    public $name = 'NodeAfterFind';

    /**
     * validate property
     *
     * @var array
     */
    public $validate = ['name' => 'notBlank'];

    /**
     * useTable property
     *
     * @var string|bool|null
     */
    public $useTable = 'apples';

    /**
     * hasOne property
     *
     * @var array
     */
    public $hasOne = ['Sample' => ['className' => 'NodeAfterFindSample']];

    /**
     * hasMany property
     *
     * @var array
     */
    public $hasMany = ['Child' => ['className' => 'NodeAfterFind', 'dependent' => true]];

    /**
     * belongsTo property
     *
     * @var array
     */
    public $belongsTo = ['Parent' => ['className' => 'NodeAfterFind', 'foreignKey' => 'apple_id']];

    /**
     * afterFind method
     *
     * @param mixed $results
     * @return mixed
     */
    public function afterFind($results, $primary = false): mixed
    {
        return $results;
    }
}
