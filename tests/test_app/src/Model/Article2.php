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
 * Article2 class
 *
 * @package       Cake.Test.Case.Model
 */
class Article2 extends CakeTestModel
{
    /**
     * name property
     *
     * @var string|null
     */
    public $name = 'Article2';

    /**
     * table property
     *
     * @var string|bool
     */
    public $table = 'articles';

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
        'Category2' => ['className' => 'Category2'],
        'User2' => ['className' => 'User2'],
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
                'id' => ['type' => 'integer', 'null' => false, 'default' => '', 'length' => '10'],
                'category_id' => ['type' => 'integer', 'null' => false, 'default' => '0', 'length' => '10'],
                'user_id' => ['type' => 'integer', 'null' => false, 'default' => '0', 'length' => '10'],
                'rate_count' => ['type' => 'integer', 'null' => false, 'default' => '0', 'length' => '10'],
                'rate_sum' => ['type' => 'integer', 'null' => false, 'default' => '0', 'length' => '10'],
                'viewed' => ['type' => 'integer', 'null' => false, 'default' => '0', 'length' => '10'],
                'version' => ['type' => 'string', 'null' => true, 'default' => '', 'length' => '45'],
                'title' => ['type' => 'string', 'null' => false, 'default' => '', 'length' => '200'],
                'intro' => ['text' => 'string', 'null' => true, 'default' => '', 'length' => null],
                'comments' => ['type' => 'integer', 'null' => false, 'default' => '0', 'length' => '4'],
                'body' => ['text' => 'string', 'null' => true, 'default' => '', 'length' => null],
                'isdraft' => ['type' => 'boolean', 'null' => false, 'default' => '0', 'length' => '1'],
                'allow_comments' => ['type' => 'boolean', 'null' => false, 'default' => '1', 'length' => '1'],
                'moderate_comments' => ['type' => 'boolean', 'null' => false, 'default' => '1', 'length' => '1'],
                'published' => ['type' => 'boolean', 'null' => false, 'default' => '0', 'length' => '1'],
                'multipage' => ['type' => 'boolean', 'null' => false, 'default' => '0', 'length' => '1'],
                'published_date' => ['type' => 'datetime', 'null' => true, 'default' => '', 'length' => null],
                'created' => ['type' => 'datetime', 'null' => false, 'default' => '0000-00-00 00:00:00', 'length' => null],
                'modified' => ['type' => 'datetime', 'null' => false, 'default' => '0000-00-00 00:00:00', 'length' => null],
            ];
        }

        return $this->_schema;
    }
}
