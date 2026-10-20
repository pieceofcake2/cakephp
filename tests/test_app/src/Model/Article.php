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
 * Article class
 *
 * @package       Cake.Test.Case.Model
 */
class Article extends CakeTestModel
{
    /**
     * name property
     *
     * @var string|null
     */
    public $name = 'Article';

    /**
     * belongsTo property
     *
     * @var array
     */
    public $belongsTo = ['User'];

    /**
     * hasMany property
     *
     * @var array
     */
    public $hasMany = ['Comment' => ['dependent' => true]];

    /**
     * hasAndBelongsToMany property
     *
     * @var array
     */
    public $hasAndBelongsToMany = ['Tag'];

    /**
     * validate property
     *
     * @var array
     */
    public $validate = [
        'user_id' => 'numeric',
        'title' => ['required' => false, 'rule' => 'notBlank'],
        'body' => ['required' => false, 'rule' => 'notBlank'],
    ];

    /**
     * beforeSaveReturn property
     *
     * @var bool
     */
    public $beforeSaveReturn = true;

    /**
     * beforeSave method
     *
     * @return bool|null
     */
    public function beforeSave($options = []): mixed
    {
        return $this->beforeSaveReturn;
    }

    /**
     * titleDuplicate method
     *
     * @param string $title
     * @return bool
     */
    public static function titleDuplicate($title): mixed
    {
        if ($title === 'My Article Title') {
            return false;
        }

        return true;
    }
}
