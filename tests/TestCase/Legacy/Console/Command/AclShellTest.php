<?php
/**
 * AclShell Test file
 *
 * CakePHP :  Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP Project
 * @package       Cake.Test.Case.Console.Command
 * @since         CakePHP v 1.2.0.7726
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Test\TestCase\Legacy\Console\Command;

use Cake\Controller\Component\AclComponent;
use Cake\Controller\ComponentCollection;
use Cake\Core\App;
use Cake\Core\Configure;
use Cake\TestSuite\CakeTestCase;
use Cake\Utility\ClassRegistry;

/**
 * AclShellTest class
 *
 * @package       Cake.Test.Case.Console.Command
 */
class AclShellTest extends CakeTestCase
{
    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = ['core.aco', 'core.aro', 'core.aros_aco'];

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
        Configure::write('Acl.classname', 'DbAcl');

        $out = $this->getMock('ConsoleOutput', [], [], '', false);
        $in = $this->getMock('ConsoleInput', [], [], '', false);

        $this->Task = $this->getMock(
            'AclShell',
            ['in', 'out', 'hr', 'createFile', 'error', 'err', 'clear', 'dispatchShell'],
            [$out, $out, $in],
        );
        $collection = new ComponentCollection();
        $this->Task->Acl = new AclComponent($collection);
        $this->Task->params['datasource'] = 'test';
    }

    /**
     * test creating aro/aco nodes
     *
     * @return void
     */
    public function testCreate(): void
    {
        $outCalls = [];
        $this->Task->expects($this->any())
            ->method('out')
            ->willReturnCallback(function ($message, $newlines = 1) use (&$outCalls) {
                $outCalls[] = ['message' => $message, 'newlines' => $newlines];
            });

        $this->Task->args = ['aro', 'root', 'User.1'];

        $this->Task->create();

        $Aro = ClassRegistry::init('Aro');
        $Aro->cacheQueries = false;
        $result = $Aro->read();
        $this->assertEquals('User', $result['Aro']['model']);
        $this->assertEquals(1, $result['Aro']['foreign_key']);
        $this->assertEquals(null, $result['Aro']['parent_id']);
        $id = $result['Aro']['id'];

        $this->Task->args = ['aro', 'User.1', 'User.3'];
        $this->Task->create();

        $Aro = ClassRegistry::init('Aro');
        $result = $Aro->read();
        $this->assertEquals('User', $result['Aro']['model']);
        $this->assertEquals(3, $result['Aro']['foreign_key']);
        $this->assertEquals($id, $result['Aro']['parent_id']);

        $this->Task->args = ['aro', 'root', 'somealias'];
        $this->Task->create();

        $Aro = ClassRegistry::init('Aro');
        $result = $Aro->read();
        $this->assertEquals('somealias', $result['Aro']['alias']);
        $this->assertEquals(null, $result['Aro']['model']);
        $this->assertEquals(null, $result['Aro']['foreign_key']);
        $this->assertEquals(null, $result['Aro']['parent_id']);

        $this->assertEquals("<success>New Aro</success> 'User.1' created.", $outCalls[0]['message']);
        $this->assertEquals(2, $outCalls[0]['newlines']);

        $this->assertEquals("<success>New Aro</success> 'User.3' created.", $outCalls[1]['message']);
        $this->assertEquals(2, $outCalls[1]['newlines']);

        $this->assertEquals("<success>New Aro</success> 'somealias' created.", $outCalls[2]['message']);
        $this->assertEquals(2, $outCalls[2]['newlines']);
    }
}
