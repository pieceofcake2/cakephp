<?php
/**
 * ModelWriteTest file
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
use Cake\Core\Configure;
use Cake\Utility\Set;
use CounterCachePost;
use CounterCacheUser;
use Something;

App::uses('CounterCachePost', 'Model');
App::uses('CounterCacheUser', 'Model');
App::uses('Something', 'Model');

/**
 * ModelWriteTest
 *
 * @package       Cake.Test.Case.Model
 */
class ModelWriteTest extends BaseModelTest
{
    /**
     * override locale to the default (eng).
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        Configure::write('Config.language', 'eng');
    }

    /**
     * Tests having multiple counter caches for an associated model
     *
     * @return void
     */
    public function testCounterCacheMultipleCaches(): void
    {
        $this->loadFixtures('CounterCacheUser', 'CounterCachePost');
        $User = new CounterCacheUser();
        $Post = new CounterCachePost();
        $Post->unbindModel(['belongsTo' => ['User']], false);
        $Post->bindModel([
            'belongsTo' => [
                'User' => [
                    'className' => 'CounterCacheUser',
                    'foreignKey' => 'user_id',
                    'counterCache' => [
                        true,
                        'posts_published' => ['Post.published' => true],
                    ],
                ],
            ],
        ], false);

        // Count Increase
        $data = ['Post' => [
            'id' => 22,
            'title' => 'New Post',
            'user_id' => 66,
            'published' => true,
        ]];
        $Post->save($data);
        $result = $User->find('first', [
            'conditions' => ['id' => 66],
            'recursive' => -1,
        ]);
        $this->assertEquals(3, $result[$User->alias]['post_count']);
        $this->assertEquals(2, $result[$User->alias]['posts_published']);

        // Count decrease
        $Post->delete(1);
        $result = $User->find('first', [
            'conditions' => ['id' => 66],
            'recursive' => -1,
        ]);
        $this->assertEquals(2, $result[$User->alias]['post_count']);
        $this->assertEquals(2, $result[$User->alias]['posts_published']);

        // Count update
        $data = $Post->find('first', [
            'conditions' => ['id' => 1],
            'recursive' => -1,
        ]);
        $data[$Post->alias]['user_id'] = 301;
        $Post->save($data);
        $result = $User->find('all', ['order' => 'User.id']);
        $this->assertEquals(2, $result[0]['User']['post_count']);
        $this->assertEquals(1, $result[1]['User']['posts_published']);
    }

    /**
     * test that saving habtm records respects conditions set in the 'conditions' key
     * for the association.
     *
     * @return void
     */
    public function testHabtmSaveWithConditionsInAssociation(): void
    {
        $this->loadFixtures('JoinThing', 'Something', 'SomethingElse');
        $Something = new Something();
        $Something->unbindModel(['hasAndBelongsToMany' => ['SomethingElse']], false);

        $Something->bindModel([
            'hasAndBelongsToMany' => [
                'DoomedSomethingElse' => [
                    'className' => 'SomethingElse',
                    'joinTable' => 'join_things',
                    'conditions' => ['JoinThing.doomed' => true],
                    'unique' => true,
                ],
                'NotDoomedSomethingElse' => [
                    'className' => 'SomethingElse',
                    'joinTable' => 'join_things',
                    'conditions' => ['JoinThing.doomed' => 0],
                    'unique' => true,
                ],
            ],
        ], false);
        $result = $Something->read(null, 1);
        $this->assertTrue(empty($result['NotDoomedSomethingElse']));
        $this->assertEquals(1, count($result['DoomedSomethingElse']));

        $data = [
            'Something' => ['id' => 1],
            'NotDoomedSomethingElse' => [
                'NotDoomedSomethingElse' => [
                    ['something_else_id' => 2, 'doomed' => 0],
                    ['something_else_id' => 3, 'doomed' => 0],
                ],
            ],
        ];
        $Something->create($data);
        $result = $Something->save();
        $this->assertFalse(empty($result));

        $result = $Something->read(null, 1);
        $this->assertEquals(2, count($result['NotDoomedSomethingElse']));
        $this->assertEquals(1, count($result['DoomedSomethingElse']));
    }

    /**
     * Test SaveAll with Habtm relations and extra join table fields
     *
     * @return void
     */
    public function testSaveAllHabtmWithExtraJoinTableFields(): void
    {
        $this->loadFixtures('Something', 'SomethingElse', 'JoinThing');

        $data = [
            'Something' => [
                'id' => 4,
                'title' => 'Extra Fields',
                'body' => 'Extra Fields Body',
                'published' => '1',
            ],
            'SomethingElse' => [
                ['something_else_id' => 1, 'doomed' => '1'],
                ['something_else_id' => 2, 'doomed' => '0'],
                ['something_else_id' => 3, 'doomed' => '1'],
            ],
        ];

        $Something = new Something();
        $result = $Something->saveAll($data);
        $this->assertFalse(empty($result));
        $result = $Something->read();

        $this->assertEquals(3, count($result['SomethingElse']));
        $this->assertTrue(Set::matches('/Something[id=4]', $result));

        $this->assertTrue(Set::matches('/SomethingElse[id=1]', $result));
        $this->assertTrue(Set::matches('/SomethingElse[id=1]/JoinThing[something_else_id=1]', $result));
        $this->assertTrue(Set::matches('/SomethingElse[id=1]/JoinThing[doomed=1]', $result));

        $this->assertTrue(Set::matches('/SomethingElse[id=2]', $result));
        $this->assertTrue(Set::matches('/SomethingElse[id=2]/JoinThing[something_else_id=2]', $result));
        $this->assertTrue(Set::matches('/SomethingElse[id=2]/JoinThing[doomed=0]', $result));

        $this->assertTrue(Set::matches('/SomethingElse[id=3]', $result));
        $this->assertTrue(Set::matches('/SomethingElse[id=3]/JoinThing[something_else_id=3]', $result));
        $this->assertTrue(Set::matches('/SomethingElse[id=3]/JoinThing[doomed=1]', $result));
    }
}
