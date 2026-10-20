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

namespace Cake\Test\TestCase\Legacy\Model;

use Cake\Core\App;
use Cake\Core\CakePlugin;
use Cake\Core\Configure;
use Cake\TestSuite\CakeTestCase;
use Cake\Utility\ClassRegistry;
use Cake\Utility\Hash;
use DbAroTest;

App::uses('DbAroTest', 'Model');

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
     * @var array
     */
    public $fixtures = ['core.aro', 'core.aco', 'core.aros_aco', 'core.aco_action', 'core.auth_user'];

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
        ]);
        Configure::write('Acl.database', 'test');
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
     * testNodeAliasParenting method
     *
     * @return void
     */
    public function testNodeAliasParenting(): void
    {
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
            'Plugin' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Plugin' . DS],
        ], App::RESET);
        CakePlugin::load('TestLegacyPlugin');

        $Aro = new DbAroTest();
        $Aro->create();
        $Aro->save(['model' => 'TestPluginAuthUser', 'foreign_key' => 1]);
        $result = $Aro->id;
        $expected = 5;
        $this->assertEquals($expected, $result);

        $node = $Aro->node(['TestLegacyPlugin.TestPluginAuthUser' => ['id' => 1, 'user' => 'mariano']]);
        $result = Hash::get($node, '0.DbAroTest.id');
        $expected = $Aro->id;
        $this->assertEquals($expected, $result);
        CakePlugin::unload('TestLegacyPlugin');
    }
}
