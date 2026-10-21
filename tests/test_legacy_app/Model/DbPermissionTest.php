<?php
/**
 * Test App DbPermissionTest Model
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
 * @since         CakePHP(tm) v 1.2.0.4206
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

/**
 * Permission Test Wrapper
 *
 * @package       Cake.Test.TestApp.Model
 */
class DbPermissionTest extends CakeTestModel
{
    /**
     * useTable property
     *
     * @var string
     */
    public $useTable = 'aros_acos';

    /**
     * cacheQueries property
     *
     * @var bool
     */
    public $cacheQueries = false;

    /**
     * belongsTo property
     *
     * @var array
     */
    public $belongsTo = ['DbAroTest' => ['foreignKey' => 'aro_id'], 'DbAcoTest' => ['foreignKey' => 'aco_id']];
}
