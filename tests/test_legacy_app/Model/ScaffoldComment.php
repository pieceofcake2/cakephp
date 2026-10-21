<?php
/**
 * Test App ScaffoldComment Model
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
 * ScaffoldComment class
 *
 * @package       Cake.Test.TestApp.Model
 */
class ScaffoldComment extends CakeTestModel
{
    /**
     * useTable property
     *
     * @var string
     */
    public $useTable = 'comments';

    /**
     * belongsTo property
     *
     * @var array
     */
    public $belongsTo = [
        'Article' => [
            'className' => 'ScaffoldMock',
            'foreignKey' => 'article_id',
        ],
    ];
}
