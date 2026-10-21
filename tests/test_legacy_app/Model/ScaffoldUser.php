<?php
/**
 * Test App ScaffoldUser Model
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
 * ScaffoldUser class
 *
 * @package       Cake.Test.TestApp.Model
 */
class ScaffoldUser extends CakeTestModel
{
    /**
     * useTable property
     *
     * @var string
     */
    public $useTable = 'users';

    /**
     * hasMany property
     *
     * @var array
     */
    public $hasMany = [
        'Article' => [
            'className' => 'ScaffoldMock',
            'foreignKey' => 'article_id',
        ],
    ];
}
