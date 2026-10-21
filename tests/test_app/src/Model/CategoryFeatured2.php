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
 * CategoryFeatured2 class
 *
 * @package       Cake.Test.Case.Model
 */
class CategoryFeatured2 extends CakeTestModel
{
    /**
     * name property
     *
     * @var string|null
     */
    public $name = 'CategoryFeatured2';

    /**
     * table property
     *
     * @var string|bool
     */
    public $table = 'category_featured';

    /**
     * useTable property
     *
     * @var string|bool|null
     */
    public $useTable = false;

    /**
     * schema method
     *
     * @return array|null
     */
    public function schema($field = false): mixed
    {
        if (!isset($this->_schema)) {
            $this->_schema = [
                'id' => ['type' => 'integer', 'null' => false, 'default' => '', 'length' => '10'],
                'parent_id' => ['type' => 'integer', 'null' => false, 'default' => '', 'length' => '10'],
                'name' => ['type' => 'string', 'null' => false, 'default' => '', 'length' => '255'],
                'icon' => ['type' => 'string', 'null' => false, 'default' => '', 'length' => '255'],
                'description' => ['text' => 'string', 'null' => false, 'default' => '', 'length' => null],
            ];
        }

        return $this->_schema;
    }
}
