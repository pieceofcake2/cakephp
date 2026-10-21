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
 * ValidateItem class
 *
 * @package       Cake.Test.Case.View.Helper
 */
class ValidateItem extends CakeTestModel
{
    /**
     * useTable property
     *
     * @var string|bool|null
     */
    public $useTable = false;

    /**
     * schema property
     *
     * @var array
     */
    protected $_schema = [
        'id' => ['type' => 'integer', 'null' => '', 'default' => '', 'length' => '8'],
        'profile_id' => ['type' => 'integer', 'null' => '', 'default' => '', 'length' => '8'],
        'name' => ['type' => 'text', 'null' => '', 'default' => '', 'length' => '255'],
        'description' => [
            'type' => 'string', 'null' => '', 'default' => '', 'length' => '255',
        ],
        'created' => ['type' => 'date', 'null' => '1', 'default' => '', 'length' => ''],
        'updated' => ['type' => 'datetime', 'null' => '1', 'default' => '', 'length' => null],
    ];

    /**
     * belongsTo property
     *
     * @var array
     */
    public $belongsTo = ['ValidateProfile' => ['foreignKey' => 'profile_id']];

    /**
     * beforeValidate method
     *
     * @return bool
     */
    public function beforeValidate($options = []): mixed
    {
        $this->invalidate('description');

        return false;
    }
}
