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

use Cake\Model\Permission;

/**
 * PermissionTwoTest class
 *
 * @package       Cake.Test.Case.Controller.Component.Acl
 */
class PermissionTwoTest extends Permission
{
    /**
     * name property
     *
     * @var string|null
     */
    public $name = 'PermissionTwoTest';

    /**
     * useTable property
     *
     * @var string|bool|null
     */
    public $useTable = 'aros_aco_twos';

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
    public $belongsTo = ['AroTwoTest' => ['foreignKey' => 'aro_id'], 'AcoTwoTest' => ['foreignKey' => 'aco_id']];

    /**
     * actsAs property
     *
     * @var array<string|array<string, array>>
     */
    public $actsAs = [];
}
