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
 * Featured2 class
 *
 * @package       Cake.Test.Case.Model
 */
class Featured2 extends CakeTestModel
{
    /**
     * name property
     *
     * @var string|null
     */
    public $name = 'Featured2';

    /**
     * table property
     *
     * @var string|bool
     */
    public $table = 'featured2';

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
    public $belongsTo = [
        'CategoryFeatured2' => [
            'className' => 'CategoryFeatured2',
        ],
    ];

    /**
     * schema method
     *
     * @return array|null
     */
    public function schema($field = false): mixed
    {
        if (!isset($this->_schema)) {
            $this->_schema = [
                'id' => ['type' => 'integer', 'null' => false, 'default' => null, 'length' => '10'],
                'article_id' => ['type' => 'integer', 'null' => false, 'default' => '0', 'length' => '10'],
                'category_id' => ['type' => 'integer', 'null' => false, 'default' => '0', 'length' => '10'],
                'name' => ['type' => 'string', 'null' => true, 'default' => null, 'length' => '20'],
            ];
        }

        return $this->_schema;
    }
}
