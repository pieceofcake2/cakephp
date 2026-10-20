<?php
/**
 * Test App AclPerson Model
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @package       Cake.Test.TestApp.Model
 * @since         CakePHP v 1.2.0.4487
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

/**
 * Test Person class - self joined model
 *
 * @package       Cake.Test.TestApp.Model
 */
class AclPerson extends CakeTestModel
{
    /**
     * useTable property
     *
     * @var string
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
     * @return void
     */
    public function parentNode(): mixed
    {
        if (!$this->id && empty($this->data)) {
            return null;
        }
        if (isset($this->data['AclPerson']['mother_id'])) {
            $motherId = $this->data['AclPerson']['mother_id'];
        } else {
            $motherId = $this->field('mother_id');
        }
        if (!$motherId) {
            return null;
        }

        return ['AclPerson' => ['id' => $motherId]];
    }
}
