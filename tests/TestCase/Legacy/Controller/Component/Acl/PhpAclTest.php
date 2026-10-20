<?php
/**
 * PhpAclTest file.
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
 * @package       Cake.Test.Case.Controller.Component.Acl
 * @since         CakePHP(tm) v 2.1
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Test\TestCase\Legacy\Controller\Component\Acl;

use Cake\Controller\Component\Acl\PhpAcl;
use Cake\Controller\Component\AclComponent;
use Cake\Controller\ComponentCollection;
use Cake\Core\Configure;
use Cake\TestSuite\CakeTestCase;

/**
 * Test case for the PhpAcl implementation
 *
 * @package       Cake.Test.Case.Controller.Component.Acl
 */
class PhpAclTest extends CakeTestCase
{
    /**
     * Setup
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        Configure::write('Acl.classname', 'PhpAcl');
        $Collection = new ComponentCollection();
        $this->PhpAcl = new PhpAcl();
        $this->Acl = new AclComponent($Collection, [
            'adapter' => [
                'config' => CORE_TESTS . DS . 'test_legacy_app' . DS . 'Config' . DS . 'acl.php',
            ],
        ]);
    }

    /**
     * test check method
     *
     * @return void
     */
    public function testCheck(): void
    {
        $this->assertTrue($this->Acl->check('jan', '/controllers/users/Dashboard'));
        $this->assertTrue($this->Acl->check('some_unknown_role', '/controllers/users/Dashboard'));
        $this->assertTrue($this->Acl->check('Role/admin', 'foo/bar'));
        $this->assertTrue($this->Acl->check('role/admin', '/foo/bar'));
        $this->assertTrue($this->Acl->check('jan', 'foo/bar'));
        $this->assertTrue($this->Acl->check('user/jan', 'foo/bar'));
        $this->assertTrue($this->Acl->check('Role/admin', 'controllers/bar'));
        $this->assertTrue($this->Acl->check(['User' => ['username' => 'jan']], '/controllers/bar/bll'));
        $this->assertTrue($this->Acl->check('Role/database_manager', 'controllers/db/create'));
        $this->assertTrue($this->Acl->check('User/db_manager_2', 'controllers/db/create'));
        $this->assertFalse($this->Acl->check('db_manager_2', '/controllers/users/Dashboard'));

        // inheritance: hardy -> reports -> data_analyst -> database_manager
        $this->assertTrue($this->Acl->check('User/hardy', 'controllers/db/create'));
        $this->assertFalse($this->Acl->check('User/jeff', 'controllers/db/create'));

        $this->assertTrue($this->Acl->check('Role/database_manager', 'controllers/db/select'));
        $this->assertTrue($this->Acl->check('User/db_manager_2', 'controllers/db/select'));
        $this->assertFalse($this->Acl->check('User/jeff', 'controllers/db/select'));

        $this->assertTrue($this->Acl->check('Role/database_manager', 'controllers/db/drop'));
        $this->assertTrue($this->Acl->check('User/db_manager_1', 'controllers/db/drop'));
        $this->assertFalse($this->Acl->check('db_manager_2', 'controllers/db/drop'));

        $this->assertTrue($this->Acl->check('db_manager_2', 'controllers/invoices/edit'));
        $this->assertFalse($this->Acl->check('database_manager', 'controllers/invoices/edit'));
        $this->assertFalse($this->Acl->check('db_manager_1', 'controllers/invoices/edit'));

        // Role/manager is allowed /controllers/*/*_manager
        $this->assertTrue($this->Acl->check('stan', 'controllers/invoices/manager_edit'));
        $this->assertTrue($this->Acl->check('Role/manager', 'controllers/baz/manager_foo'));
        $this->assertFalse($this->Acl->check('User/stan', 'custom/foo/manager_edit'));
        $this->assertFalse($this->Acl->check('stan', 'bar/baz/manager_foo'));
        $this->assertFalse($this->Acl->check('Role/accounting', 'bar/baz/manager_foo'));
        $this->assertFalse($this->Acl->check('accounting', 'controllers/baz/manager_foo'));

        $this->assertTrue($this->Acl->check('User/stan', 'controllers/articles/edit'));
        $this->assertTrue($this->Acl->check('stan', 'controllers/articles/add'));
        $this->assertTrue($this->Acl->check('stan', 'controllers/articles/publish'));
        $this->assertFalse($this->Acl->check('User/stan', 'controllers/articles/delete'));
        $this->assertFalse($this->Acl->check('accounting', 'controllers/articles/edit'));
        $this->assertFalse($this->Acl->check('accounting', 'controllers/articles/add'));
        $this->assertFalse($this->Acl->check('role/accounting', 'controllers/articles/publish'));
    }
}
