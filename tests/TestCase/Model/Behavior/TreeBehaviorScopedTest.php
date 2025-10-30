<?php
/**
 * TreeBehaviorScopedTest file
 *
 * A tree test using scope
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
 * @package       Cake.Test.Case.Model.Behavior
 * @since         CakePHP(tm) v 1.2.0.5330
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Test\TestCase\Model\Behavior;

use App\Model\Ad;
use App\Model\FlagTree;
use App\Model\NumberTreeTwo;
use Cake\Core\App;
use Cake\TestSuite\CakeTestCase;
use Cake\Utility\Hash;

App::uses('AppModel', 'Model');

require_once dirname(__DIR__) . DS . 'models.php';

/**
 * TreeBehaviorScopedTest class
 *
 * @package       Cake.Test.Case.Model.Behavior
 */
class TreeBehaviorScopedTest extends CakeTestCase
{
    /**
     * Whether backup global state for each test method or not
     *
     * @var bool
     */
    public $backupGlobals = false;

    /**
     * settings property
     *
     * @var array
     */
    public array $settings = [
        'modelClass' => 'FlagTree',
        'leftField' => 'lft',
        'rightField' => 'rght',
        'parentField' => 'parent_id',
    ];

    /**
     * fixtures property
     *
     * @var array
     */
    public array $fixtures = [
        'core.flag_tree',
        'core.ad',
        'core.campaign',
        'core.translate',
        'core.number_tree_two',
    ];

    /**
     * testStringScope method
     *
     * @return void
     */
    public function testStringScope()
    {
        $tree = new FlagTree();
        $tree->order = null;
        $tree->initialize(2, 3);

        $tree->id = 1;
        $tree->saveField('flag', 1);
        $tree->id = 2;
        $tree->saveField('flag', 1);

        $result = $tree->children();
        $expected = [
            ['FlagTree' => ['id' => '3', 'name' => '1.1.1', 'parent_id' => '2', 'lft' => '3', 'rght' => '4', 'flag' => '0']],
            ['FlagTree' => ['id' => '4', 'name' => '1.1.2', 'parent_id' => '2', 'lft' => '5', 'rght' => '6', 'flag' => '0']],
            ['FlagTree' => ['id' => '5', 'name' => '1.1.3', 'parent_id' => '2', 'lft' => '7', 'rght' => '8', 'flag' => '0']],
        ];
        $this->assertEquals($expected, $result);

        $tree->Behaviors->load('Tree', ['scope' => 'FlagTree.flag = 1']);
        $this->assertEquals([], $tree->children());

        $tree->id = 1;
        $tree->Behaviors->load('Tree', ['scope' => 'FlagTree.flag = 1']);

        $result = $tree->children();
        $expected = [['FlagTree' => ['id' => '2', 'name' => '1.1', 'parent_id' => '1', 'lft' => '2', 'rght' => '9', 'flag' => '1']]];
        $this->assertEquals($expected, $result);

        $this->assertTrue($tree->delete());
        $this->assertEquals(11, $tree->find('count'));
    }

    /**
     * testArrayScope method
     *
     * @return void
     */
    public function testArrayScope()
    {
        $tree = new FlagTree();
        $tree->order = null;
        $tree->initialize(2, 3);

        $tree->id = 1;
        $tree->saveField('flag', 1);
        $tree->id = 2;
        $tree->saveField('flag', 1);

        $result = $tree->children();
        $expected = [
            ['FlagTree' => ['id' => '3', 'name' => '1.1.1', 'parent_id' => '2', 'lft' => '3', 'rght' => '4', 'flag' => '0']],
            ['FlagTree' => ['id' => '4', 'name' => '1.1.2', 'parent_id' => '2', 'lft' => '5', 'rght' => '6', 'flag' => '0']],
            ['FlagTree' => ['id' => '5', 'name' => '1.1.3', 'parent_id' => '2', 'lft' => '7', 'rght' => '8', 'flag' => '0']],
        ];
        $this->assertEquals($expected, $result);

        $tree->Behaviors->load('Tree', ['scope' => ['FlagTree.flag' => 1]]);
        $this->assertEquals([], $tree->children());

        $tree->id = 1;
        $tree->Behaviors->load('Tree', ['scope' => ['FlagTree.flag' => 1]]);

        $result = $tree->children();
        $expected = [['FlagTree' => ['id' => '2', 'name' => '1.1', 'parent_id' => '1', 'lft' => '2', 'rght' => '9', 'flag' => '1']]];
        $this->assertEquals($expected, $result);

        $this->assertTrue($tree->delete());
        $this->assertEquals(11, $tree->find('count'));
    }

    /**
     * testSaveWithParentAndInvalidScope method
     *
     * Attempting to save an invalid data should not trigger an `Undefined offset`
     * error
     *
     * @return void
     */
    public function testSaveWithParentAndInvalidScope()
    {
        $tree = new FlagTree();
        $tree->order = null;
        $data = $tree->create([
            'name' => 'Flag',
        ]);
        $_tree = $tree->save($data);
        $tree->Behaviors->load('Tree', [
            'scope' => ['FlagTree.flag' => 100],
        ]);
        $_tree['FlagTree']['parent_id'] = 1;
        $result = $tree->save($_tree);
        $this->assertFalse($result);
    }

    /**
     * testMoveUpWithScope method
     *
     * @return void
     */
    public function testMoveUpWithScope()
    {
        $this->Ad = new Ad();
        $this->Ad->order = null;
        $this->Ad->Behaviors->load('Tree', ['scope' => 'Campaign']);
        $this->Ad->moveUp(6);

        $this->Ad->id = 4;
        $result = $this->Ad->children();
        $this->assertEquals([6, 5], Hash::extract($result, '{n}.Ad.id'));
        $this->assertEquals([2, 2], Hash::extract($result, '{n}.Campaign.id'));
    }

    /**
     * testMoveDownWithScope method
     *
     * @return void
     */
    public function testMoveDownWithScope()
    {
        $this->Ad = new Ad();
        $this->Ad->order = null;
        $this->Ad->Behaviors->load('Tree', ['scope' => 'Campaign']);
        $this->Ad->moveDown(6);

        $this->Ad->id = 4;
        $result = $this->Ad->children();
        $this->assertEquals([5, 6], Hash::extract($result, '{n}.Ad.id'));
        $this->assertEquals([2, 2], Hash::extract($result, '{n}.Campaign.id'));
    }

    /**
     * Tests the interaction (non-interference) between TreeBehavior and other behaviors with respect
     * to callback hooks
     *
     * @return void
     */
    public function testTranslatingTree()
    {
        $tree = new FlagTree();
        $tree->order = null;
        $tree->cacheQueries = false;
        $tree->Behaviors->load('Translate', ['title']);

        //Save
        $tree->create();
        $tree->locale = 'eng';
        $data = ['FlagTree' => [
            'title' => 'name #1',
            'name' => 'test',
            'locale' => 'eng',
            'parent_id' => null,
        ]];
        $tree->save($data);
        $result = $tree->find('all');
        $expected = [['FlagTree' => [
            'id' => 1,
            'title' => 'name #1',
            'name' => 'test',
            'parent_id' => null,
            'lft' => 1,
            'rght' => 2,
            'flag' => 0,
            'locale' => 'eng',
        ]]];
        $this->assertEquals($expected, $result);

        // update existing record, same locale
        $tree->create();
        $data['FlagTree']['title'] = 'Named 2';
        $tree->id = 1;
        $tree->save($data);
        $result = $tree->find('all');
        $expected = [['FlagTree' => [
            'id' => 1,
            'title' => 'Named 2',
            'name' => 'test',
            'parent_id' => null,
            'lft' => 1,
            'rght' => 2,
            'flag' => 0,
            'locale' => 'eng',
        ]]];
        $this->assertEquals($expected, $result);

        // update different locale, same record
        $tree->create();
        $tree->locale = 'deu';
        $tree->id = 1;
        $data = ['FlagTree' => [
            'id' => 1,
            'parent_id' => null,
            'title' => 'namen #1',
            'name' => 'test',
            'locale' => 'deu',
        ]];
        $tree->save($data);

        $tree->locale = 'deu';
        $result = $tree->find('all');
        $expected = [
            [
                'FlagTree' => [
                    'id' => 1,
                    'title' => 'namen #1',
                    'name' => 'test',
                    'parent_id' => null,
                    'lft' => 1,
                    'rght' => 2,
                    'flag' => 0,
                    'locale' => 'deu',
                ],
            ],
        ];
        $this->assertEquals($expected, $result);

        // Save with bindTranslation
        $tree->locale = 'eng';
        $data = [
            'title' => ['eng' => 'New title', 'spa' => 'Nuevo leyenda'],
            'name' => 'test',
            'parent_id' => null,
        ];
        $tree->create($data);
        $tree->save();

        $tree->unbindTranslation();
        $translations = ['title' => 'Title'];
        $tree->bindTranslation($translations, false);
        $tree->locale = ['eng', 'spa'];

        $result = $tree->read();
        $expected = [
            'FlagTree' => [
                'id' => 2,
                'parent_id' => null,
                'locale' => 'eng',
                'name' => 'test',
                'title' => 'New title',
                'flag' => 0,
                'lft' => 3,
                'rght' => 4,
            ],
            'Title' => [
                ['id' => 21, 'locale' => 'eng', 'model' => 'FlagTree', 'foreign_key' => 2, 'field' => 'title', 'content' => 'New title'],
                ['id' => 22, 'locale' => 'spa', 'model' => 'FlagTree', 'foreign_key' => 2, 'field' => 'title', 'content' => 'Nuevo leyenda'],
            ],
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * testGenerateTreeListWithSelfJoin method
     *
     * @return void
     */
    public function testAliasesWithScopeInTwoTreeAssociations()
    {
        extract($this->settings);
        $tree = new FlagTree();
        $tree->order = null;
        $tree->initialize(2, 2);

        $treeTwo = new NumberTreeTwo();
        $treeTwo->order = null;

        $record = $tree->find('first');

        $tree->bindModel([
            'hasMany' => [
                'SecondTree' => [
                    'className' => 'NumberTreeTwo',
                    'foreignKey' => 'number_tree_id',
                ],
            ],
        ]);
        $treeTwo->bindModel([
            'belongsTo' => [
                'FirstTree' => [
                    'className' => $modelClass,
                    'foreignKey' => 'number_tree_id',
                ],
            ],
        ]);
        $treeTwo->Behaviors->load('Tree', [
            'scope' => 'FirstTree',
        ]);

        $data = [
            'NumberTreeTwo' => [
                'name' => 'First',
                'number_tree_id' => $record['FlagTree']['id'],
            ],
        ];
        $treeTwo->create();
        $result = $treeTwo->save($data);
        $this->assertFalse(empty($result));

        $result = $treeTwo->find('first');
        $expected = ['NumberTreeTwo' => [
            'id' => 1,
            'name' => 'First',
            'number_tree_id' => $record['FlagTree']['id'],
            'parent_id' => null,
            'lft' => 1,
            'rght' => 2,
        ]];
        $this->assertEquals($expected, $result);
    }

    /**
     * testGenerateTreeListWithScope method
     *
     * @return void
     */
    public function testGenerateTreeListWithScope()
    {
        extract($this->settings);
        $tree = new FlagTree();
        $tree->order = null;
        $tree->initialize(2, 3);

        $tree->id = 1;
        $tree->saveField('flag', 1);
        $tree->id = 2;
        $tree->saveField('flag', 1);

        $tree->Behaviors->load('Tree', ['scope' => ['FlagTree.flag' => 1]]);

        $result = $tree->generateTreeList();
        $expected = [
            1 => '1. Root',
            2 => '_1.1',
        ];
        $this->assertEquals($expected, $result);

        // As string.
        $tree->Behaviors->load('Tree', ['scope' => 'FlagTree.flag = 1']);

        $result = $tree->generateTreeList();
        $this->assertEquals($expected, $result);

        // Merging conditions.
        $result = $tree->generateTreeList(['FlagTree.id >' => 1]);
        $expected = [
            2 => '1.1',
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * testRecoverUsingParentMode method
     *
     * @return void
     */
    public function testRecoverUsingParentMode()
    {
        extract($this->settings);
        $tree = new FlagTree();
        $tree->order = null;
        $tree->initialize(2, 3);

        $tree->Behaviors->load('Tree', ['scope' => 'FlagTree.flag = 1']);
        $tree->Behaviors->disable('Tree');

        $tree->create();
        $tree->save(['name' => 'Main', $parentField => null, $leftField => 0, $rightField => 0, 'flag' => 1]);
        $node1 = $tree->id;

        $tree->create();
        $tree->save(['name' => 'About Us', $parentField => $node1, $leftField => 0, $rightField => 0, 'flag' => 1]);
        $node11 = $tree->id;

        $tree->create();
        $tree->save(['name' => 'Programs', $parentField => $node1, $leftField => 0, $rightField => 0, 'flag' => 1]);
        $node12 = $tree->id;

        $tree->create();
        $tree->save(['name' => 'Mission and History', $parentField => $node11, $leftField => 0, $rightField => 0, 'flag' => 1]);

        $tree->create();
        $tree->save(['name' => 'Overview', $parentField => $node12, $leftField => 0, $rightField => 0, 'flag' => 1]);

        $tree->Behaviors->enable('Tree');

        $result = $tree->verify();
        $this->assertNotSame(true, $result);

        $result = $tree->recover();
        $this->assertTrue($result);

        $result = $tree->verify();
        $this->assertTrue($result);

        $result = $tree->find('first', [
            'fields' => ['name', $parentField, $leftField, $rightField, 'flag'],
            'conditions' => ['name' => 'Main'],
            'recursive' => -1,
        ]);
        $expected = [
            $modelClass => [
                'name' => 'Main',
                $parentField => null,
                $leftField => 1,
                $rightField => 10,
                'flag' => 1,
            ],
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * testRecoverFromMissingParent method
     *
     * @return void
     */
    public function testRecoverFromMissingParent()
    {
        extract($this->settings);
        $tree = new FlagTree();
        $tree->order = null;
        $tree->initialize(2, 2);

        $tree->id = 1;
        $tree->saveField('flag', 1);
        $tree->id = 2;
        $tree->saveField('flag', 1);

        $tree->Behaviors->load('Tree', ['scope' => ['FlagTree.flag' => 1]]);

        $result = $tree->findByName('1.1');
        $tree->updateAll([$parentField => 999999], ['id' => $result[$modelClass]['id']]);

        $result = $tree->verify();
        $this->assertNotSame(true, $result);

        $result = $tree->recover();
        $this->assertTrue($result);

        $result = $tree->verify();
        $this->assertTrue($result);
    }

    /**
     * testDetectInvalidParents method
     *
     * @return void
     */
    public function testDetectInvalidParents()
    {
        extract($this->settings);
        $tree = new FlagTree();
        $tree->order = null;
        $tree->initialize(2, 2);

        $tree->id = 1;
        $tree->saveField('flag', 1);
        $tree->id = 2;
        $tree->saveField('flag', 1);

        $tree->Behaviors->load('Tree', ['scope' => ['FlagTree.flag' => 1]]);

        $tree->updateAll([$parentField => null]);

        $result = $tree->verify();
        $this->assertNotSame(true, $result);

        $result = $tree->recover();
        $this->assertTrue($result);

        $result = $tree->verify();
        $this->assertTrue($result);
    }

    /**
     * testDetectInvalidLftsRghts method
     *
     * @return void
     */
    public function testDetectInvalidLftsRghts()
    {
        extract($this->settings);
        $tree = new FlagTree();
        $tree->order = null;
        $tree->initialize(2, 2);

        $tree->id = 1;
        $tree->saveField('flag', 1);
        $tree->id = 2;
        $tree->saveField('flag', 1);

        $tree->Behaviors->load('Tree', ['scope' => ['FlagTree.flag' => 1]]);

        $tree->updateAll([$leftField => 0, $rightField => 0]);

        $result = $tree->verify();
        $this->assertNotSame(true, $result);

        $tree->recover();

        $result = $tree->verify();
        $this->assertTrue($result);
    }

    /**
     * Reproduces a situation where a single node has lft= rght, and all other lft and rght fields follow sequentially
     *
     * @return void
     */
    public function testDetectEqualLftsRghts()
    {
        extract($this->settings);
        $tree = new FlagTree();
        $tree->order = null;
        $tree->initialize(1, 3);

        $tree->id = 1;
        $tree->saveField('flag', 1);
        $tree->id = 2;
        $tree->saveField('flag', 1);

        $tree->Behaviors->load('Tree', ['scope' => ['FlagTree.flag' => 1]]);

        $result = $tree->findByName('1.1');
        $tree->updateAll([$rightField => $result[$modelClass][$leftField]], ['id' => $result[$modelClass]['id']]);
        $tree->updateAll(
            [$leftField => $tree->escapeField($leftField) . ' -1'],
            [$leftField . ' >' => $result[$modelClass][$leftField]],
        );
        $tree->updateAll(
            [$rightField => $tree->escapeField($rightField) . ' -1'],
            [$rightField . ' >' => $result[$modelClass][$leftField]],
        );

        $result = $tree->verify();
        $this->assertNotSame(true, $result);

        $result = $tree->recover();
        $this->assertTrue($result);

        $result = $tree->verify();
        $this->assertTrue($result);
    }
}
