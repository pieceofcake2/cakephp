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
 * OpenidUrl class
 *
 * @package       Cake.Test.Case.View.Helper
 */
class OpenidUrl extends CakeTestModel
{
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
    public $belongsTo = ['UserForm' => [
        'className' => 'UserForm', 'foreignKey' => 'user_form_id',
    ]];

    /**
     * validate property
     *
     * @var array
     */
    public $validate = ['openid_not_registered' => []];

    /**
     * schema method
     *
     * @var array
     */
    protected $_schema = [
        'id' => ['type' => 'integer', 'null' => '', 'default' => '', 'length' => '8'],
        'user_form_id' => [
            'type' => 'user_form_id', 'null' => '', 'default' => '', 'length' => '8',
        ],
        'url' => ['type' => 'string', 'null' => '', 'default' => '', 'length' => '255'],
    ];

    /**
     * beforeValidate method
     *
     * @return bool
     */
    public function beforeValidate($options = []): mixed
    {
        $this->invalidate('openid_not_registered');

        return true;
    }
}
