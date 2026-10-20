<?php
/**
 * Test App DbAclTwoTest
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
 * @package       Cake.Test.TestApp.Controller.Component.Acl
 * @since         CakePHP(tm) v 2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

App::uses('AcoTwoTest', 'Model');
App::uses('AroTwoTest', 'Model');
App::uses('PermissionTwoTest', 'Model');

/**
 * DbAclTwoTest class
 *
 * @package       Cake.Test.TestApp.Controller.Component.Acl
 */
class DbAclTwoTest extends DbAcl
{
    /**
     * construct method
     */
    public function __construct()
    {
        $this->Aro = new AroTwoTest();
        $this->Aro->Permission = new PermissionTwoTest();
        $this->Aco = new AcoTwoTest();
        $this->Aro->Permission = new PermissionTwoTest();

        $this->Permission = $this->Aro->Permission;
        $this->Permission->Aro = $this->Aro;
        $this->Permission->Aco = $this->Aco;
    }
}
