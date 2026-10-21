<?php
/**
 * DbAclTest file.
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
 * @since         CakePHP(tm) v 2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Test\TestCase\Legacy\Controller\Component\Acl;

use Cake\Controller\Component\AclComponent;
use Cake\Controller\ComponentCollection;
use Cake\Core\App;
use Cake\Core\Configure;
use Cake\TestSuite\CakeTestCase;

/**
 * Test case for AclComponent using the DbAcl implementation.
 *
 * @package       Cake.Test.Case.Controller.Component.Acl
 */
class DbAclTest extends CakeTestCase
{
    /**
     * fixtures property
     *
     * @var array
     */
    public $fixtures = ['core.aro_two', 'core.aco_two', 'core.aros_aco_two'];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        App::build([
            'Model' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Model' . DS],
            'Controller/Component/Acl' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Controller' . DS . 'Component' . DS . 'Acl' . DS],
        ]);
        Configure::write('Acl.classname', 'DbAclTwoTest');
        Configure::write('Acl.database', 'test');
        $Collection = new ComponentCollection();
        $this->Acl = new AclComponent($Collection);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->Acl);

        parent::tearDown();
    }

    /**
     * testAclCreate method
     *
     * @return void
     */
    public function testCreate(): void
    {
        $this->Acl->Aro->create(['alias' => 'Chotchkey']);
        $this->assertTrue((bool)$this->Acl->Aro->save());

        $parent = $this->Acl->Aro->id;

        $this->Acl->Aro->create(['parent_id' => $parent, 'alias' => 'Joanna']);
        $this->assertTrue((bool)$this->Acl->Aro->save());

        $this->Acl->Aro->create(['parent_id' => $parent, 'alias' => 'Stapler']);
        $this->assertTrue((bool)$this->Acl->Aro->save());

        $root = $this->Acl->Aco->node('ROOT');
        $parent = $root[0]['AcoTwoTest']['id'];

        $this->Acl->Aco->create(['parent_id' => $parent, 'alias' => 'Drinks']);
        $this->assertTrue((bool)$this->Acl->Aco->save());

        $this->Acl->Aco->create(['parent_id' => $parent, 'alias' => 'PiecesOfFlair']);
        $this->assertTrue((bool)$this->Acl->Aco->save());
    }

    /**
     * testDbAclAllow method
     *
     * @return void
     */
    public function testAllow(): void
    {
        $this->assertFalse($this->Acl->check('Micheal', 'tpsReports', 'read'));
        $this->assertTrue($this->Acl->allow('Micheal', 'tpsReports', ['read', 'delete', 'update']));
        $this->assertTrue($this->Acl->check('Micheal', 'tpsReports', 'update'));
        $this->assertTrue($this->Acl->check('Micheal', 'tpsReports', 'read'));
        $this->assertTrue($this->Acl->check('Micheal', 'tpsReports', 'delete'));

        $this->assertFalse($this->Acl->check('Micheal', 'tpsReports', 'create'));
        $this->assertTrue($this->Acl->allow('Micheal', 'ROOT/tpsReports', 'create'));
        $this->assertTrue($this->Acl->check('Micheal', 'tpsReports', 'create'));
        $this->assertTrue($this->Acl->check('Micheal', 'tpsReports', 'delete'));
        $this->assertTrue($this->Acl->allow('Micheal', 'printers', 'create'));
        // Michael no longer has his delete permission for tpsReports!
        $this->assertTrue($this->Acl->check('Micheal', 'tpsReports', 'delete'));
        $this->assertTrue($this->Acl->check('Micheal', 'printers', 'create'));

        $this->assertFalse($this->Acl->check('root/users/Samir', 'ROOT/tpsReports/view'));
        $this->assertTrue($this->Acl->allow('root/users/Samir', 'ROOT/tpsReports/view', '*'));
        $this->assertTrue($this->Acl->check('Samir', 'view', 'read'));
        $this->assertTrue($this->Acl->check('root/users/Samir', 'ROOT/tpsReports/view', 'update'));

        $this->assertFalse($this->Acl->check('root/users/Samir', 'ROOT/tpsReports/update', '*'));
        $this->assertTrue($this->Acl->allow('root/users/Samir', 'ROOT/tpsReports/update', '*'));
        $this->assertTrue($this->Acl->check('Samir', 'update', 'read'));
        $this->assertTrue($this->Acl->check('root/users/Samir', 'ROOT/tpsReports/update', 'update'));
        // Samir should still have his tpsReports/view permissions, but does not
        $this->assertTrue($this->Acl->check('root/users/Samir', 'ROOT/tpsReports/view', 'update'));

        $this->assertFalse($this->Acl->allow('Lumbergh', 'ROOT/tpsReports/DoesNotExist', 'create'));
    }

    /**
     * testDbAclCheck method
     *
     * @return void
     */
    public function testCheck(): void
    {
        $this->assertTrue($this->Acl->check('Samir', 'print', 'read'));
        $this->assertTrue($this->Acl->check('Lumbergh', 'current', 'read'));
        $this->assertFalse($this->Acl->check('Milton', 'smash', 'read'));
        $this->assertFalse($this->Acl->check('Milton', 'current', 'update'));

        $this->assertFalse($this->Acl->check(null, 'printers', 'create'));
        $this->assertFalse($this->Acl->check('managers', null, 'read'));

        $this->assertTrue($this->Acl->check('Bobs', 'ROOT/tpsReports/view/current', 'read'));
        $this->assertFalse($this->Acl->check('Samir', 'ROOT/tpsReports/update', 'read'));

        $this->assertFalse($this->Acl->check('root/users/Milton', 'smash', 'delete'));
    }
}
