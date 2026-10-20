<?php
/**
 * AclNodeTest file
 *
 * CakePHP(tm) Tests <https://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.Test.Case.Model
 * @since         CakePHP(tm) v 1.2.0.4206
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Test\TestCase\Model;

use Cake\Controller\Component\Acl\DbAcl;
use Cake\Core\App;
use Cake\Core\CakePlugin;
use Cake\Core\Configure;
use Cake\TestSuite\CakeTestCase;
use Cake\Utility\ClassRegistry;
use Cake\Utility\Hash;
use TestApp\Model\DbAcoTest;
use TestApp\Model\DbAroTest;
use TestApp\Model\DbAroUserTest;
use TestApp\Model\DbPermissionTest;

/**
 * TestDbAcl class
 *
 * @package       Cake.Test.Case.Model
 */
class TestDbAcl extends DbAcl
{
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->Aro = new DbAroTest();
        $this->Aro->Permission = new DbPermissionTest();
        $this->Aco = new DbAcoTest();
        $this->Aco->Permission = new DbPermissionTest();
    }
}

/**
 * AclNodeTest class
 *
 * @package       Cake.Test.Case.Model
 */
class AclNodeTest extends CakeTestCase
{
    /**
     * fixtures property
     *
     * @var array<string>
     */
    public $fixtures = [
        'core.aro',
        'core.aco',
        'core.aros_aco',
        'core.aco_action',
        'core.auth_user',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        Configure::write('App.namespace', 'TestApp');
        Configure::write('Acl.classname', TestDbAcl::class);
        Configure::write('Acl.database', 'test');
    }

    /**
     * testNode method
     *
     * @return void
     */
    public function testNode(): void
    {
        $Aco = new DbAcoTest();
        $result = Hash::extract($Aco->node('Controller1'), '{n}.DbAcoTest.id');
        $expected = [2, 1];
        $this->assertEquals($expected, $result);

        $result = Hash::extract($Aco->node('Controller1/action1'), '{n}.DbAcoTest.id');
        $expected = [3, 2, 1];
        $this->assertEquals($expected, $result);

        $result = Hash::extract($Aco->node('Controller2/action1'), '{n}.DbAcoTest.id');
        $expected = [7, 6, 1];
        $this->assertEquals($expected, $result);

        $result = Hash::extract($Aco->node('Controller1/action2'), '{n}.DbAcoTest.id');
        $expected = [5, 2, 1];
        $this->assertEquals($expected, $result);

        $result = Hash::extract($Aco->node('Controller1/action1/record1'), '{n}.DbAcoTest.id');
        $expected = [4, 3, 2, 1];
        $this->assertEquals($expected, $result);

        $result = Hash::extract($Aco->node('Controller2/action1/record1'), '{n}.DbAcoTest.id');
        $expected = [8, 7, 6, 1];
        $this->assertEquals($expected, $result);

        $this->assertFalse($Aco->node('Controller2/action3'));

        $this->assertFalse($Aco->node('Controller2/action3/record5'));

        $result = $Aco->node('');
        $this->assertEquals(null, $result);
    }

    /**
     * test that node() doesn't dig deeper than it should.
     *
     * @return void
     */
    public function testNodeWithDuplicatePathSegments(): void
    {
        $Aco = new DbAcoTest();
        $nodes = $Aco->node('ROOT/Users');
        $this->assertEquals(1, $nodes[0]['DbAcoTest']['parent_id'], 'Parent id does not point at ROOT. %s');
    }

    /**
     * testNodeArrayFind method
     *
     * @return void
     */
    public function testNodeArrayFind(): void
    {
        $Aro = new DbAroTest();
        Configure::write('DbAclbindMode', 'string');
        $result = Hash::extract($Aro->node(['DbAroUserTest' => ['id' => '1', 'foreign_key' => '1']]), '{n}.DbAroTest.id');
        $expected = [3, 2, 1];
        $this->assertEquals($expected, $result);

        Configure::write('DbAclbindMode', 'array');
        $result = Hash::extract($Aro->node(['DbAroUserTest' => ['id' => 4, 'foreign_key' => 2]]), '{n}.DbAroTest.id');
        $expected = [4];
        $this->assertEquals($expected, $result);
    }

    /**
     * testNodeObjectFind method
     *
     * @return void
     */
    public function testNodeObjectFind(): void
    {
        $Aro = new DbAroTest();
        $Model = new DbAroUserTest();
        $Model->id = 1;
        $result = Hash::extract($Aro->node($Model), '{n}.DbAroTest.id');
        $expected = [3, 2, 1];
        $this->assertEquals($expected, $result);

        $Model->id = 2;
        $result = Hash::extract($Aro->node($Model), '{n}.DbAroTest.id');
        $expected = [4, 2, 1];
        $this->assertEquals($expected, $result);
    }

    /**
     * testNodeAliasParenting method
     *
     * @return void
     */
    public function testNodeAliasParenting(): void
    {
        /** @var DbAcoTest $Aco */
        $Aco = ClassRegistry::init('DbAcoTest');
        $db = $Aco->getDataSource();
        $db->truncate($Aco);

        $Aco->create(['model' => null, 'foreign_key' => null, 'parent_id' => null, 'alias' => 'Application']);
        $Aco->save();

        $Aco->create(['model' => null, 'foreign_key' => null, 'parent_id' => $Aco->id, 'alias' => 'Pages']);
        $Aco->save();

        $result = $Aco->find('all');
        $expected = [
            ['DbAcoTest' => ['id' => '1', 'parent_id' => null, 'model' => null, 'foreign_key' => null, 'alias' => 'Application', 'lft' => '1', 'rght' => '4'], 'DbAroTest' => []],
            ['DbAcoTest' => ['id' => '2', 'parent_id' => '1', 'model' => null, 'foreign_key' => null, 'alias' => 'Pages', 'lft' => '2', 'rght' => '3'], 'DbAroTest' => []],
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * testNodeActionAuthorize method
     *
     * @return void
     */
    public function testNodeActionAuthorize(): void
    {
        App::build([
            'Plugin' => [CORE_TESTS . DS . 'test_app' . DS . 'plugins' . DS],
        ], App::RESET);
        CakePlugin::load('TestPlugin');

        $Aro = new DbAroTest();
        $Aro->create();
        $Aro->save(['model' => 'TestPluginAuthUser', 'foreign_key' => 1]);
        $result = $Aro->id;
        $expected = 5;
        $this->assertEquals($expected, $result);

        $node = $Aro->node(['TestPlugin.TestPluginAuthUser' => ['id' => 1, 'user' => 'mariano']]);
        $result = Hash::get($node, '0.DbAroTest.id');
        $expected = $Aro->id;
        $this->assertEquals($expected, $result);
        CakePlugin::unload('TestPlugin');
    }
}
