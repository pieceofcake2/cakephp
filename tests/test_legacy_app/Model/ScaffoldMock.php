<?php
/**
 * Test App ScaffoldMock Model
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
 * ScaffoldMock class
 *
 * @package       Cake.Test.TestApp.Model
 */
class ScaffoldMock extends CakeTestModel
{
    /**
     * useTable property
     *
     * @var string
     */
    public $useTable = 'articles';

    /**
     * belongsTo property
     *
     * @var array
     */
    public $belongsTo = [
        'User' => [
            'className' => 'ScaffoldUser',
            'foreignKey' => 'user_id',
        ],
    ];

    /**
     * hasMany property
     *
     * @var array
     */
    public $hasMany = [
        'Comment' => [
            'className' => 'ScaffoldComment',
            'foreignKey' => 'article_id',
        ],
    ];

    /**
     * hasAndBelongsToMany property
     *
     * @var string
     */
    public $hasAndBelongsToMany = [
        'ScaffoldTag' => [
            'className' => 'ScaffoldTag',
            'foreignKey' => 'something_id',
            'associationForeignKey' => 'something_else_id',
            'joinTable' => 'join_things',
        ],
    ];
}
