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
use Cake\Utility\Hash;

/**
 * ControllerPost class
 *
 * @package       Cake.Test.Case.Controller
 */
class ControllerPost extends CakeTestModel
{
    /**
     * useTable property
     *
     * @var string|bool|null
     */
    public $useTable = 'posts';

    /**
     * invalidFields property
     *
     * @var array
     */
    public $invalidFields = ['name' => 'error_msg'];

    /**
     * lastQuery property
     *
     * @var mixed
     */
    public $lastQuery = null;

    /**
     * beforeFind method
     *
     * @param mixed $query
     * @return array|bool|null
     */
    public function beforeFind($query): void
    {
        $this->lastQuery = $query;
    }

    /**
     * find method
     *
     * @param string|null $type
     * @param array|null $query
     * @return array|int|false|null
     */
    public function find($type = 'first', $options = []): mixed
    {
        if ($type === 'popular') {
            $conditions = [$this->name . '.' . $this->primaryKey . ' > ' => '1'];
            $options = Hash::merge($options, compact('conditions'));

            return parent::find('all', $options);
        }

        return parent::find($type, $options);
    }
}
