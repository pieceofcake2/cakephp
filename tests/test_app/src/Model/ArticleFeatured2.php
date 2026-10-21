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
 * ArticleFeatured2 class
 *
 * @package       Cake.Test.Case.Model
 */
class ArticleFeatured2 extends CakeTestModel
{
    /**
     * name property
     *
     * @var string|null
     */
    public $name = 'ArticleFeatured2';

    /**
     * table property
     *
     * @var string|bool
     */
    public $table = 'article_featured';

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
        'CategoryFeatured2' => ['className' => 'CategoryFeatured2'],
        'User2' => ['className' => 'User2'],
    ];

    /**
     * hasOne property
     *
     * @var array
     */
    public $hasOne = [
        'Featured2' => ['className' => 'Featured2'],
    ];

    /**
     * hasMany property
     *
     * @var array
     */
    public $hasMany = [
        'Comment2' => ['className' => 'Comment2', 'dependent' => true],
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
                'category_featured_id' => ['type' => 'integer', 'null' => false, 'default' => '0', 'length' => '10'],
                'user_id' => ['type' => 'integer', 'null' => false, 'default' => '0', 'length' => '10'],
                'title' => ['type' => 'string', 'null' => true, 'default' => null, 'length' => '20'],
                'body' => ['text' => 'string', 'null' => true, 'default' => '', 'length' => null],
                'published' => ['type' => 'boolean', 'null' => false, 'default' => '0', 'length' => '1'],
                'published_date' => ['type' => 'datetime', 'null' => true, 'default' => '', 'length' => null],
                'created' => ['type' => 'datetime', 'null' => false, 'default' => '0000-00-00 00:00:00', 'length' => null],
                'modified' => ['type' => 'datetime', 'null' => false, 'default' => '0000-00-00 00:00:00', 'length' => null],
            ];
        }

        return $this->_schema;
    }
}
