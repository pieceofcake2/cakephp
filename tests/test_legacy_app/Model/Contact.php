<?php
/**
 * Test App Contact Model
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
 * @since         CakePHP(tm) v 1.2.0.4206
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

/**
 * Contact class
 *
 * @package       Cake.Test.TestApp.Model
 */
class Contact extends CakeTestModel
{
    /**
     * useTable property
     *
     * @var bool
     */
    public $useTable = false;

    /**
     * Default schema
     *
     * @var array
     */
    protected $_schema = [
        'id' => ['type' => 'integer', 'null' => '', 'default' => '', 'length' => '8'],
        'name' => ['type' => 'string', 'null' => '', 'default' => '', 'length' => '255'],
        'email' => ['type' => 'string', 'null' => '', 'default' => '', 'length' => '255'],
        'phone' => ['type' => 'string', 'null' => '', 'default' => '', 'length' => '255'],
        'password' => ['type' => 'string', 'null' => '', 'default' => '', 'length' => '255'],
        'lap_time' => ['type' => 'time', 'null' => '', 'default' => '', 'length' => '2'],
        'last_seen' => ['type' => 'datetime', 'null' => '', 'default' => '', 'length' => '3'],
        'published' => ['type' => 'date', 'null' => true, 'default' => null, 'length' => null],
        'created' => ['type' => 'date', 'null' => '1', 'default' => '', 'length' => ''],
        'updated' => ['type' => 'datetime', 'null' => '1', 'default' => '', 'length' => null],
        'age' => ['type' => 'integer', 'null' => '', 'default' => '', 'length' => null],
    ];

    /**
     * validate property
     *
     * @var array
     */
    public $validate = [
        'non_existing' => [],
        'idontexist' => [],
        'imrequired' => ['rule' => ['between', 5, 30], 'allowEmpty' => false],
        'imrequiredonupdate' => ['notBlank' => ['rule' => 'alphaNumeric', 'on' => 'update']],
        'imrequiredoncreate' => ['required' => ['rule' => 'alphaNumeric', 'on' => 'create']],
        'imrequiredonboth' => [
            'required' => ['rule' => 'alphaNumeric'],
        ],
        'string_required' => 'notBlank',
        'imalsorequired' => ['rule' => 'alphaNumeric', 'allowEmpty' => false],
        'imrequiredtoo' => ['rule' => 'notBlank'],
        'required_one' => ['required' => ['rule' => ['notBlank']]],
        'imnotrequired' => ['required' => false, 'rule' => 'alphaNumeric', 'allowEmpty' => true],
        'imalsonotrequired' => [
            'alpha' => ['rule' => 'alphaNumeric', 'allowEmpty' => true],
            'between' => ['rule' => ['between', 5, 30]],
        ],
        'imalsonotrequired2' => [
            'alpha' => ['rule' => 'alphaNumeric', 'allowEmpty' => true],
            'between' => ['rule' => ['between', 5, 30], 'allowEmpty' => true],
        ],
        'imnotrequiredeither' => ['required' => true, 'rule' => ['between', 5, 30], 'allowEmpty' => true],
        'iamrequiredalways' => [
            'email' => ['rule' => 'email'],
            'rule_on_create' => ['rule' => ['maxLength', 50], 'on' => 'create'],
            'rule_on_update' => ['rule' => ['between', 1, 50], 'on' => 'update'],
        ],
        'boolean_field' => ['rule' => 'boolean'],
    ];

    /**
     * schema method
     *
     * @return void
     */
    public function setSchema($schema): void
    {
        $this->_schema = $schema;
    }

    /**
     * hasAndBelongsToMany property
     *
     * @var array
     */
    public $hasAndBelongsToMany = ['ContactTag' => ['with' => 'ContactTagsContact']];

    /**
     * hasAndBelongsToMany property
     *
     * @var array
     */
    public $belongsTo = ['User' => ['className' => 'UserForm']];
}
