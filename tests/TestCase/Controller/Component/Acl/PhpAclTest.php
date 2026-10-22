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

namespace Cake\Test\TestCase\Controller\Component\Acl;

use Cake\Controller\Component\Acl\PhpAcl;
use Cake\Controller\Component\Acl\PhpAro;
use Cake\Controller\Component\AclComponent;
use Cake\Controller\ComponentCollection;
use Cake\Core\Configure;
use Cake\Error\AclException;
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
        Configure::write('Acl.classname', PhpAcl::class);
        $Collection = new ComponentCollection();
        $this->PhpAcl = new PhpAcl();
        $this->Acl = new AclComponent($Collection, [
            'adapter' => [
                'config' => CORE_TESTS . DS . 'test_app' . DS . 'config' . DS . 'acl.php',
            ],
        ]);
    }

    /**
     * Test role inheritance
     *
     * @return void
     */
    public function testRoleInheritance(): void
    {
        $roles = $this->Acl->Aro->roles('User/peter');
        $this->assertEquals(['Role/accounting'], $roles[0]);
        $this->assertEquals(['User/peter'], $roles[1]);

        $roles = $this->Acl->Aro->roles('hardy');
        $this->assertEquals(['Role/database_manager', 'Role/data_acquirer'], $roles[0]);
        $this->assertEquals(['Role/accounting', 'Role/data_analyst'], $roles[1]);
        $this->assertEquals(['Role/accounting_manager', 'Role/reports'], $roles[2]);
        $this->assertEquals(['User/hardy'], $roles[3]);
    }

    /**
     * Test adding a role
     *
     * @return void
     */
    public function testAddRole(): void
    {
        $this->assertEquals([[PhpAro::DEFAULT_ROLE]], $this->Acl->Aro->roles('foobar'));
        $this->Acl->Aro->addRole(['User/foobar' => 'Role/accounting']);
        $this->assertEquals([['Role/accounting'], ['User/foobar']], $this->Acl->Aro->roles('foobar'));
    }

    /**
     * Test resolving ARO
     *
     * @return void
     */
    public function testAroResolve(): void
    {
        $this->Acl->Aro->map = [
            'User' => 'FooModel/nickname',
            'Role' => 'FooModel/role',
        ];

        $this->assertEquals('Role/default', $this->Acl->Aro->resolve('Foo.bar'));
        $this->assertEquals('User/hardy', $this->Acl->Aro->resolve('FooModel/hardy'));
        $this->assertEquals('User/hardy', $this->Acl->Aro->resolve('hardy'));
        $this->assertEquals('User/hardy', $this->Acl->Aro->resolve(['FooModel' => ['nickname' => 'hardy']]));
        $this->assertEquals('Role/admin', $this->Acl->Aro->resolve(['FooModel' => ['role' => 'admin']]));
        $this->assertEquals('Role/admin', $this->Acl->Aro->resolve('Role/admin'));

        $this->assertEquals('Role/admin', $this->Acl->Aro->resolve('admin'));
        $this->assertEquals('Role/admin', $this->Acl->Aro->resolve('FooModel/admin'));
        $this->assertEquals('Role/accounting', $this->Acl->Aro->resolve('accounting'));

        $this->assertEquals(PhpAro::DEFAULT_ROLE, $this->Acl->Aro->resolve('bla'));
        $this->assertEquals(PhpAro::DEFAULT_ROLE, $this->Acl->Aro->resolve(['FooModel' => ['role' => 'hardy']]));
    }

    /**
     * test correct resolution of defined aliases
     *
     * @return void
     */
    public function testAroAliases(): void
    {
        $this->Acl->Aro->map = [
            'User' => 'User/username',
            'Role' => 'User/group_id',
        ];

        $this->Acl->Aro->aliases = [
            'Role/1' => 'Role/admin',
            'Role/24' => 'Role/accounting',
        ];

        $user = [
            'User' => [
                'username' => 'unknown_user',
                'group_id' => '1',
            ],
        ];
        // group/1
        $this->assertEquals('Role/admin', $this->Acl->Aro->resolve($user));
        // group/24
        $this->assertEquals('Role/accounting', $this->Acl->Aro->resolve('Role/24'));
        $this->assertEquals('Role/accounting', $this->Acl->Aro->resolve('24'));

        // check department
        $user = [
            'User' => [
                'username' => 'foo',
                'group_id' => '25',
            ],
        ];

        $this->Acl->Aro->addRole(['Role/IT' => null]);
        $this->Acl->Aro->addAlias(['Role/25' => 'Role/IT']);
        $this->Acl->allow('Role/IT', '/rules/debugging/*');

        $this->assertEquals([['Role/IT']], $this->Acl->Aro->roles($user));
        $this->assertTrue($this->Acl->check($user, '/rules/debugging/stats/pageload'));
        $this->assertTrue($this->Acl->check($user, '/rules/debugging/sql/queries'));
        // Role/default is allowed users dashboard, but not Role/IT
        $this->assertFalse($this->Acl->check($user, '/controllers/users/dashboard'));

        $this->assertFalse($this->Acl->check($user, '/controllers/invoices/send'));
        // wee add an more specific entry for user foo to also inherit from Role/accounting
        $this->Acl->Aro->addRole(['User/foo' => 'Role/IT, Role/accounting']);
        $this->assertTrue($this->Acl->check($user, '/controllers/invoices/send'));
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

    /**
     * lhs of defined rules are case insensitive
     *
     * @return void
     */
    public function testCheckIsCaseInsensitive(): void
    {
        $this->assertTrue($this->Acl->check('hardy', 'controllers/forms/new'));
        $this->assertTrue($this->Acl->check('Role/data_acquirer', 'controllers/forms/new'));
        $this->assertTrue($this->Acl->check('hardy', 'controllers/FORMS/NEW'));
        $this->assertTrue($this->Acl->check('Role/data_acquirer', 'controllers/FORMS/NEW'));
    }

    /**
     * allow should work in-memory
     *
     * @return void
     */
    public function testAllow(): void
    {
        $this->assertFalse($this->Acl->check('jeff', 'foo/bar'));

        $this->Acl->allow('jeff', 'foo/bar');

        $this->assertTrue($this->Acl->check('jeff', 'foo/bar'));
        $this->assertFalse($this->Acl->check('peter', 'foo/bar'));
        $this->assertFalse($this->Acl->check('hardy', 'foo/bar'));

        $this->Acl->allow('Role/accounting', 'foo/bar');

        $this->assertTrue($this->Acl->check('peter', 'foo/bar'));
        $this->assertTrue($this->Acl->check('hardy', 'foo/bar'));

        $this->assertFalse($this->Acl->check('Role/reports', 'foo/bar'));
    }

    /**
     * deny should work in-memory
     *
     * @return void
     */
    public function testDeny(): void
    {
        $this->assertTrue($this->Acl->check('stan', 'controllers/baz/manager_foo'));

        $this->Acl->deny('stan', 'controllers/baz/manager_foo');

        $this->assertFalse($this->Acl->check('stan', 'controllers/baz/manager_foo'));
        $this->assertTrue($this->Acl->check('Role/manager', 'controllers/baz/manager_foo'));
        $this->assertTrue($this->Acl->check('stan', 'controllers/baz/manager_bar'));
        $this->assertTrue($this->Acl->check('stan', 'controllers/baz/manager_foooooo'));
    }

    /**
     * test that a deny rule wins over an equally specific allow rule
     *
     * @return void
     */
    public function testDenyRuleIsStrongerThanAllowRule(): void
    {
        $this->assertFalse($this->Acl->check('peter', 'baz/bam'));
        $this->Acl->allow('peter', 'baz/bam');
        $this->assertTrue($this->Acl->check('peter', 'baz/bam'));
        $this->Acl->deny('peter', 'baz/bam');
        $this->assertFalse($this->Acl->check('peter', 'baz/bam'));

        $this->assertTrue($this->Acl->check('stan', 'controllers/reports/foo'));
        // stan is denied as he's sales and sales is denied /controllers/*/delete
        $this->assertFalse($this->Acl->check('stan', 'controllers/reports/delete'));
        $this->Acl->allow('stan', 'controllers/reports/delete');
        $this->assertFalse($this->Acl->check('Role/sales', 'controllers/reports/delete'));
        $this->assertTrue($this->Acl->check('stan', 'controllers/reports/delete'));
        $this->Acl->deny('stan', 'controllers/reports/delete');
        $this->assertFalse($this->Acl->check('stan', 'controllers/reports/delete'));

        // there is already an equally specific deny rule that will win
        $this->Acl->allow('stan', 'controllers/reports/delete');
        $this->assertFalse($this->Acl->check('stan', 'controllers/reports/delete'));
    }

    /**
     * test that an invalid configuration throws exception
     *
     * @return void
     */
    public function testInvalidConfigWithAroMissing(): void
    {
        $this->expectException(AclException::class);
        $this->expectExceptionMessage('"roles" section not found in configuration');
        $config = ['aco' => ['allow' => ['foo' => '']]];
        $this->PhpAcl->build($config);
    }

    public function testInvalidConfigWithAcosMissing(): void
    {
        $this->expectException(AclException::class);
        $this->expectExceptionMessage('Neither "allow" nor "deny" rules were provided in configuration.');

        $config = [
            'roles' => ['Role/foo' => null],
        ];

        $this->PhpAcl->build($config);
    }

    /**
     * Test that inherit() is not supported: it reports failure and changes no permission.
     *
     * @return void
     */
    public function testInherit(): void
    {
        $this->assertFalse($this->Acl->check('peter', 'foo/bar'));
        $this->assertFalse($this->Acl->inherit('peter', 'foo/bar'));
        $this->assertFalse($this->Acl->check('peter', 'foo/bar'));

        $this->assertTrue($this->Acl->check('jan', 'foo/bar'));
        $this->assertFalse($this->Acl->inherit('jan', 'foo/bar'));
        $this->assertTrue($this->Acl->check('jan', 'foo/bar'));
    }

    /**
     * Test that an ACO given as an array of nodes resolves to the lowercased nodes.
     *
     * @return void
     */
    public function testAcoResolveArray(): void
    {
        $this->assertSame(['controllers', 'invoices', 'edit'], $this->Acl->Aco->resolve(['Controllers', 'INVOICES', 'edit']));
        $this->assertSame([], $this->Acl->Aco->resolve([]));
    }

    /**
     * Test that the "map" section of the configuration replaces the default mapping of the AROs.
     *
     * @return void
     */
    public function testBuildWithMap(): void
    {
        $this->PhpAcl->build([
            'map' => [
                'User' => 'Member/login',
                'Role' => 'Member/group',
            ],
            'roles' => [
                'Role/editors' => null,
                'User/mariano' => 'Role/editors',
            ],
            'rules' => [
                'allow' => ['/articles/*' => 'Role/editors'],
            ],
        ]);

        $this->assertSame(['User' => 'Member/login', 'Role' => 'Member/group'], $this->PhpAcl->Aro->map);
        $this->assertSame('User/mariano', $this->PhpAcl->Aro->resolve(['Member' => ['login' => 'mariano']]));
        $this->assertSame('Role/editors', $this->PhpAcl->Aro->resolve(['Member' => ['group' => 'editors']]));
        $this->assertSame('User/mariano', $this->PhpAcl->Aro->resolve('Member/mariano'));
        // the default mapping of User/username is gone
        $this->assertSame(PhpAro::DEFAULT_ROLE, $this->PhpAcl->Aro->resolve(['User' => ['username' => 'mariano']]));

        $this->assertTrue($this->PhpAcl->check(['Member' => ['login' => 'mariano']], '/articles/edit'));
        $this->assertFalse($this->PhpAcl->check(['Member' => ['login' => 'jose']], '/articles/edit'));
    }

    /**
     * Test resolving an ARO given as an array of model and foreign key, as AclNode does,
     * or as the fields of a record without the model.
     *
     * @return void
     */
    public function testAroResolveModelAndForeignKey(): void
    {
        $this->Acl->Aro->addRole(['User/7' => 'Role/accounting']);

        $this->assertSame('User/7', $this->Acl->Aro->resolve(['model' => 'User', 'foreign_key' => 7]));
        $this->assertSame('Role/sales', $this->Acl->Aro->resolve(['model' => 'Role', 'foreign_key' => 'sales']));
        $this->assertSame(PhpAro::DEFAULT_ROLE, $this->Acl->Aro->resolve(['model' => 'User', 'foreign_key' => 8]));
        $this->assertTrue($this->Acl->check(['model' => 'User', 'foreign_key' => 7], '/controllers/invoices/send'));

        $this->assertSame('User/jeff', $this->Acl->Aro->resolve(['username' => 'jeff']));
        $this->assertSame('Role/accounting', $this->Acl->Aro->resolve(['role' => 'accounting']));
        $this->assertTrue($this->Acl->check(['username' => 'jeff'], '/controllers/invoices/send'));
    }

    /**
     * Test that a role can inherit from a role that is not declared: the parent is added to the tree.
     *
     * @return void
     */
    public function testAddRoleInheritingUndeclaredRole(): void
    {
        $this->assertSame(PhpAro::DEFAULT_ROLE, $this->Acl->Aro->resolve('Role/support'));

        $this->Acl->Aro->addRole(['User/linda' => 'Role/support']);

        $this->assertSame('Role/support', $this->Acl->Aro->resolve('Role/support'));
        $this->assertSame([['Role/support'], ['User/linda']], $this->Acl->Aro->roles('linda'));

        $this->Acl->allow('Role/support', '/controllers/tickets/*');
        $this->assertTrue($this->Acl->check('linda', '/controllers/tickets/view'));
        $this->assertFalse($this->Acl->check('peter', '/controllers/tickets/view'));
    }

    /**
     * Test checking an ACO without nodes, such as '/': no rule matches, so the policy decides.
     *
     * @return void
     */
    public function testCheckAcoWithoutNodes(): void
    {
        $this->assertSame([], $this->Acl->Aco->path('/'));
        $this->assertSame([], $this->Acl->Aco->path(''));

        $this->assertFalse($this->Acl->check('peter', '/'));
        $this->assertFalse($this->Acl->check('peter', ''));
        // '/*' allows the nodes below the root, not an ACO without nodes
        $this->assertFalse($this->Acl->check('jan', '/'));

        $this->Acl->settings['adapter']['policy'] = PhpAcl::ALLOW;
        $this->Acl->adapter($this->PhpAcl);
        $this->assertTrue($this->Acl->check('peter', '/'));
    }

    /**
     * test resolving of ACOs
     *
     * @return void
     */
    public function testAcoResolve(): void
    {
        $this->assertEquals(['foo', 'bar'], $this->Acl->Aco->resolve('foo/bar'));
        $this->assertEquals(['foo', 'bar'], $this->Acl->Aco->resolve('foo/bar'));
        $this->assertEquals(['foo', 'bar', 'baz'], $this->Acl->Aco->resolve('foo/bar/baz'));
        $this->assertEquals(['foo', '*-bar', '?-baz'], $this->Acl->Aco->resolve('foo/*-bar/?-baz'));

        $this->assertEquals(['foo', 'bar', '[a-f0-9]{24}', '*_bla', 'bla'], $this->Acl->Aco->resolve('foo/bar/[a-f0-9]{24}/*_bla/bla'));

        // multiple slashes will be squashed to a single, trimmed and then exploded
        $this->assertEquals(['foo', 'bar'], $this->Acl->Aco->resolve('foo//bar'));
        $this->assertEquals(['foo', 'bar'], $this->Acl->Aco->resolve('//foo///bar/'));
        $this->assertEquals(['foo', 'bar'], $this->Acl->Aco->resolve('/foo//bar//'));
        $this->assertEquals(['foo', 'bar'], $this->Acl->Aco->resolve('/foo // bar'));
        $this->assertEquals([], $this->Acl->Aco->resolve('/////'));
    }

    /**
     * test that declaring cyclic dependencies should give an error when building the tree
     *
     * @return void
     */
    public function testAroDeclarationContainsCycles(): void
    {
        $config = [
            'roles' => [
                'Role/a' => null,
                'Role/b' => 'User/b',
                'User/a' => 'Role/a, Role/b',
                'User/b' => 'User/a',
            ],
            'rules' => [
                'allow' => [
                    '*' => 'Role/a',
                ],
            ],
        ];

        $noticeTriggered = false;
        $noticeMessage = '';
        set_error_handler(function ($errno, $errstr) use (&$noticeTriggered, &$noticeMessage) {
            $noticeTriggered = true;
            $noticeMessage = $errstr;

            return true;
        }, E_NOTICE | E_USER_NOTICE);

        try {
            $this->PhpAcl->build($config);
        } finally {
            restore_error_handler();
        }

        $this->assertTrue($noticeTriggered, 'Expected notice was not triggered');
        $this->assertSame('cycle detected when inheriting Role/b from User/b. Path: Role/b -> Role/b', $noticeMessage);
    }

    /**
     * test that with policy allow, only denies count
     *
     * @return void
     */
    public function testPolicy(): void
    {
        // allow by default
        $this->Acl->settings['adapter']['policy'] = PhpAcl::ALLOW;
        $this->Acl->adapter($this->PhpAcl);

        $this->assertTrue($this->Acl->check('Role/sales', 'foo'));
        $this->assertTrue($this->Acl->check('Role/sales', 'controllers/bla/create'));
        $this->assertTrue($this->Acl->check('Role/default', 'foo'));
        // undefined user, undefined aco
        $this->assertTrue($this->Acl->check('foobar', 'foo/bar'));

        // deny rule: Role.sales -> controllers.*.delete
        $this->assertFalse($this->Acl->check('Role/sales', 'controllers/bar/delete'));
        $this->assertFalse($this->Acl->check('Role/sales', 'controllers/bar', 'delete'));
    }
}
