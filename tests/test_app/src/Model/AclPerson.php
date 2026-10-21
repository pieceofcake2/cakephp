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
 * Test Person class - self joined model
 *
 * @package       Cake.Test.Case.Model.Behavior
 */
class AclPerson extends CakeTestModel
{
    /**
     * useTable property
     *
     * @var string|bool|null
     */
    public $useTable = 'people';

    /**
     * actsAs property
     *
     * @var array
     */
    public $actsAs = ['Acl' => 'both'];

    /**
     * belongsTo property
     *
     * @var array
     */
    public $belongsTo = [
        'Mother' => [
            'className' => 'AclPerson',
            'foreignKey' => 'mother_id',
        ],
    ];

    /**
     * hasMany property
     *
     * @var array
     */
    public $hasMany = [
        'Child' => [
            'className' => 'AclPerson',
            'foreignKey' => 'mother_id',
        ],
    ];

    /**
     * parentNode method
     *
     * @return array[]|null
     */
    public function parentNode(): mixed
    {
        if (!$this->id && empty($this->data)) {
            return null;
        }
        $motherId = $this->data['AclPerson']['mother_id'] ?? $this->field('mother_id');
        if (!$motherId) {
            return null;
        }

        return ['AclPerson' => ['id' => $motherId]];
    }
}
