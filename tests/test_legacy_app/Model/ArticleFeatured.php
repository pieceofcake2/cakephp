<?php
/**
 * Test App ArticleFeatured Model
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
 * ArticleFeatured class
 *
 * @package       Cake.Test.TestApp.Model
 */
class ArticleFeatured extends CakeTestModel
{
    /**
     * name property
     *
     * @var string
     */
    public $name = 'ArticleFeatured';

    /**
     * belongsTo property
     *
     * @var array
     */
    public $belongsTo = ['User', 'Category'];

    /**
     * hasOne property
     *
     * @var array
     */
    public $hasOne = ['Featured'];

    /**
     * hasMany property
     *
     * @var array
     */
    public $hasMany = ['Comment' => ['className' => 'Comment', 'dependent' => true]];

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
    public $validate = ['user_id' => 'numeric', 'title' => 'notBlank', 'body' => 'notBlank'];
}
