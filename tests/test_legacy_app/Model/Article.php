<?php
/**
 * Test App Article Model
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
 * @since         CakePHP(tm) v 1.2.0.6464
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

/**
 * Article class
 *
 * @package       Cake.Test.TestApp.Model
 */
class Article extends CakeTestModel
{
    /**
     * name property
     *
     * @var string
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
     * @return void
     */
    public function beforeSave($options = []): mixed
    {
        return $this->beforeSaveReturn;
    }

    /**
     * titleDuplicate method
     *
     * @param string $title
     * @return void
     */
    public static function titleDuplicate($title): mixed
    {
        if ($title === 'My Article Title') {
            return false;
        }

        return true;
    }
}
