<?php
/**
 * ModelIntegrationTest file
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

use ArticleB;
use ArticleFeatured;
use AssociationTest1;
use Cake\Core\App;
use Cake\Core\CakePlugin;
use Cake\Utility\ClassRegistry;
use ModelA;
use Person;
use Something;
use Test;
use TestAlias;
use TestPluginArticle;
use TheVoid;
use UuidTag;

App::uses('ArticleB', 'Model');
App::uses('ArticleFeatured', 'Model');
App::uses('AssociationTest1', 'Model');
App::uses('ModelA', 'Model');
App::uses('Person', 'Model');
App::uses('Something', 'Model');
App::uses('Test', 'Model');
App::uses('TestAlias', 'Model');
App::uses('TestPluginArticle', 'Model');
App::uses('TheVoid', 'Model');
App::uses('UuidTag', 'Model');

/**
 * ModelIntegrationTest
 *
 * @package       Cake.Test.Case.Model
 */
class ModelIntegrationTest extends BaseModelTest
{
    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        App::build([
            'Plugin' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Plugin' . DS],
        ]);
        CakePlugin::load('TestLegacyPlugin');
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        CakePlugin::unload('TestLegacyPlugin');
        parent::tearDown();
    }

    /**
     * testAssociationLazyLoading
     *
     * @group lazyloading
     * @return void
     */
    public function testAssociationLazyLoading(): void
    {
        $this->loadFixtures('ArticleFeaturedsTags');
        $Article = new ArticleFeatured();
        $this->assertTrue(isset($Article->belongsTo['User']));
        $this->assertFalse(property_exists($Article, 'User'));
        $this->assertInstanceOf('User', $Article->User);

        $this->assertTrue(isset($Article->belongsTo['Category']));
        $this->assertFalse(property_exists($Article, 'Category'));
        $this->assertTrue(isset($Article->Category));
        $this->assertInstanceOf('Category', $Article->Category);

        $this->assertTrue(isset($Article->hasMany['Comment']));
        $this->assertFalse(property_exists($Article, 'Comment'));
        $this->assertTrue(isset($Article->Comment));
        $this->assertInstanceOf('Comment', $Article->Comment);

        $this->assertTrue(isset($Article->hasAndBelongsToMany['Tag']));
        //There was not enough information to setup the association (joinTable and associationForeignKey)
        //so the model was not lazy loaded
        $this->assertTrue(property_exists($Article, 'Tag'));
        $this->assertTrue(isset($Article->Tag));
        $this->assertInstanceOf('Tag', $Article->Tag);

        $this->assertFalse(property_exists($Article, 'ArticleFeaturedsTag'));
        $this->assertInstanceOf('AppModel', $Article->ArticleFeaturedsTag);
        $this->assertEquals('article_featureds_tags', $Article->hasAndBelongsToMany['Tag']['joinTable']);
        $this->assertEquals('tag_id', $Article->hasAndBelongsToMany['Tag']['associationForeignKey']);
    }

    /**
     * testAssociationLazyLoadWithHABTM
     *
     * @group lazyloading
     * @return void
     */
    public function testAssociationLazyLoadWithHABTM(): void
    {
        $this->loadFixtures('FruitsUuidTag', 'ArticlesTag');
        $this->db->cacheSources = false;
        $Article = new ArticleB();
        $this->assertTrue(isset($Article->hasAndBelongsToMany['TagB']));
        $this->assertFalse(property_exists($Article, 'TagB'));
        $this->assertInstanceOf('TagB', $Article->TagB);

        $this->assertFalse(property_exists($Article, 'ArticlesTag'));
        $this->assertInstanceOf('AppModel', $Article->ArticlesTag);

        $UuidTag = new UuidTag();
        $this->assertTrue(isset($UuidTag->hasAndBelongsToMany['Fruit']));
        $this->assertFalse(property_exists($UuidTag, 'Fruit'));
        $this->assertFalse(property_exists($UuidTag, 'FruitsUuidTag'));
        $this->assertTrue(isset($UuidTag->Fruit));

        $this->assertFalse(property_exists($UuidTag, 'FruitsUuidTag'));
        $this->assertTrue(isset($UuidTag->FruitsUuidTag));
        $this->assertInstanceOf('FruitsUuidTag', $UuidTag->FruitsUuidTag);
    }

    /**
     * testAssociationLazyLoadWithBindModel
     *
     * @group lazyloading
     * @return void
     */
    public function testAssociationLazyLoadWithBindModel(): void
    {
        $this->loadFixtures('Article', 'User');
        $Article = new ArticleB();

        $this->assertFalse(isset($Article->belongsTo['User']));
        $this->assertFalse(property_exists($Article, 'User'));

        $Article->bindModel(['belongsTo' => ['User']]);
        $this->assertTrue(isset($Article->belongsTo['User']));
        $this->assertFalse(property_exists($Article, 'User'));
        $this->assertInstanceOf('User', $Article->User);
    }

    /**
     * testLoadModelSecondIteration method
     *
     * @return void
     */
    public function testLoadModelSecondIteration(): void
    {
        $this->loadFixtures('Apple', 'Message', 'Thread', 'Bid');
        $model = new ModelA();
        $this->assertInstanceOf('ModelA', $model);

        $this->assertInstanceOf('ModelB', $model->ModelB);
        $this->assertInstanceOf('ModelD', $model->ModelB->ModelD);

        $this->assertInstanceOf('ModelC', $model->ModelC);
        $this->assertInstanceOf('ModelD', $model->ModelC->ModelD);
    }

    /**
     * testPluginAssociations method
     *
     * @return void
     */
    public function testPluginAssociations(): void
    {
        $this->loadFixtures('TestPluginArticle', 'User', 'TestPluginComment');
        $TestModel = new TestPluginArticle();

        $result = $TestModel->find('all');
        $expected = [
            [
                'TestPluginArticle' => [
                    'id' => 1,
                    'user_id' => 1,
                    'title' => 'First Plugin Article',
                    'body' => 'First Plugin Article Body',
                    'published' => 'Y',
                    'created' => '2008-09-24 10:39:23',
                    'updated' => '2008-09-24 10:41:31',
                ],
                'User' => [
                    'id' => 1,
                    'user' => 'mariano',
                    'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
                    'created' => '2007-03-17 01:16:23',
                    'updated' => '2007-03-17 01:18:31',
                ],
                'TestPluginComment' => [
                    [
                        'id' => 1,
                        'article_id' => 1,
                        'user_id' => 2,
                        'comment' => 'First Comment for First Plugin Article',
                        'published' => 'Y',
                        'created' => '2008-09-24 10:45:23',
                        'updated' => '2008-09-24 10:47:31',
                    ],
                    [
                        'id' => 2,
                        'article_id' => 1,
                        'user_id' => 4,
                        'comment' => 'Second Comment for First Plugin Article',
                        'published' => 'Y',
                        'created' => '2008-09-24 10:47:23',
                        'updated' => '2008-09-24 10:49:31',
                    ],
                    [
                        'id' => 3,
                        'article_id' => 1,
                        'user_id' => 1,
                        'comment' => 'Third Comment for First Plugin Article',
                        'published' => 'Y',
                        'created' => '2008-09-24 10:49:23',
                        'updated' => '2008-09-24 10:51:31',
                    ],
                    [
                        'id' => 4,
                        'article_id' => 1,
                        'user_id' => 1,
                        'comment' => 'Fourth Comment for First Plugin Article',
                        'published' => 'N',
                        'created' => '2008-09-24 10:51:23',
                        'updated' => '2008-09-24 10:53:31',
                    ]]],
            [
                'TestPluginArticle' => [
                    'id' => 2,
                    'user_id' => 3,
                    'title' => 'Second Plugin Article',
                    'body' => 'Second Plugin Article Body',
                    'published' => 'Y',
                    'created' => '2008-09-24 10:41:23',
                    'updated' => '2008-09-24 10:43:31',
                ],
                'User' => [
                    'id' => 3,
                    'user' => 'larry',
                    'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
                    'created' => '2007-03-17 01:20:23',
                    'updated' => '2007-03-17 01:22:31',
                ],
                'TestPluginComment' => [
                    [
                        'id' => 5,
                        'article_id' => 2,
                        'user_id' => 1,
                        'comment' => 'First Comment for Second Plugin Article',
                        'published' => 'Y',
                        'created' => '2008-09-24 10:53:23',
                        'updated' => '2008-09-24 10:55:31',
                    ],
                    [
                        'id' => 6,
                        'article_id' => 2,
                        'user_id' => 2,
                        'comment' => 'Second Comment for Second Plugin Article',
                        'published' => 'Y',
                        'created' => '2008-09-24 10:55:23',
                        'updated' => '2008-09-24 10:57:31',
                    ]]],
            [
                'TestPluginArticle' => [
                    'id' => 3,
                    'user_id' => 1,
                    'title' => 'Third Plugin Article',
                    'body' => 'Third Plugin Article Body',
                    'published' => 'Y',
                    'created' => '2008-09-24 10:43:23',
                    'updated' => '2008-09-24 10:45:31',
                ],
                'User' => [
                    'id' => 1,
                    'user' => 'mariano',
                    'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
                    'created' => '2007-03-17 01:16:23',
                    'updated' => '2007-03-17 01:18:31',
                ],
                'TestPluginComment' => [],
            ]];

        $this->assertEquals($expected, $result);
    }

    /**
     * Tests getAssociated method
     *
     * @return void
     */
    public function testGetAssociated(): void
    {
        $this->loadFixtures('Article', 'Tag');
        $Article = ClassRegistry::init('Article');

        $assocTypes = ['hasMany', 'hasOne', 'belongsTo', 'hasAndBelongsToMany'];
        foreach ($assocTypes as $type) {
            $this->assertEquals($Article->getAssociated($type), array_keys($Article->{$type}));
        }

        $Article->bindModel(['hasMany' => ['Category']]);
        $this->assertEquals(['Comment', 'Category'], $Article->getAssociated('hasMany'));

        $results = $Article->getAssociated();
        $results = array_keys($results);
        sort($results);
        $this->assertEquals(['Category', 'Comment', 'Tag', 'User'], $results);

        $Article->unbindModel(['hasAndBelongsToMany' => ['Tag']]);
        $this->assertEquals([], $Article->getAssociated('hasAndBelongsToMany'));

        $result = $Article->getAssociated('Category');
        $expected = [
            'className' => 'Category',
            'foreignKey' => 'article_id',
            'conditions' => '',
            'fields' => '',
            'order' => '',
            'limit' => '',
            'offset' => '',
            'dependent' => '',
            'exclusive' => '',
            'finderQuery' => '',
            'counterQuery' => '',
            'association' => 'hasMany',
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * testAutoConstructAssociations method
     *
     * @return void
     */
    public function testAutoConstructAssociations(): void
    {
        $this->loadFixtures('User', 'ArticleFeatured', 'Featured', 'ArticleFeaturedsTags');
        $TestModel = new AssociationTest1();

        $result = $TestModel->hasAndBelongsToMany;
        $expected = ['AssociationTest2' => [
                'unique' => false,
                'joinTable' => 'join_as_join_bs',
                'foreignKey' => false,
                'className' => 'AssociationTest2',
                'with' => 'JoinAsJoinB',
                'dynamicWith' => true,
                'associationForeignKey' => 'join_b_id',
                'conditions' => '', 'fields' => '', 'order' => '', 'limit' => '', 'offset' => '',
                'finderQuery' => '',
        ]];
        $this->assertEquals($expected, $result);

        $TestModel = new ArticleFeatured();
        $TestFakeModel = new ArticleFeatured(['table' => false]);

        $expected = [
            'User' => [
                'className' => 'User', 'foreignKey' => 'user_id',
                'conditions' => '', 'fields' => '', 'order' => '', 'counterCache' => '',
            ],
            'Category' => [
                'className' => 'Category', 'foreignKey' => 'category_id',
                'conditions' => '', 'fields' => '', 'order' => '', 'counterCache' => '',
            ],
        ];
        $this->assertSame($expected, $TestModel->belongsTo);
        $this->assertSame($expected, $TestFakeModel->belongsTo);

        $this->assertEquals('User', $TestModel->User->name);
        $this->assertEquals('User', $TestFakeModel->User->name);
        $this->assertEquals('Category', $TestModel->Category->name);
        $this->assertEquals('Category', $TestFakeModel->Category->name);

        $expected = [
            'Featured' => [
                'className' => 'Featured',
                'foreignKey' => 'article_featured_id',
                'conditions' => '',
                'fields' => '',
                'order' => '',
                'dependent' => '',
            ]];

        $this->assertSame($expected, $TestModel->hasOne);
        $this->assertSame($expected, $TestFakeModel->hasOne);

        $this->assertEquals('Featured', $TestModel->Featured->name);
        $this->assertEquals('Featured', $TestFakeModel->Featured->name);

        $expected = [
            'Comment' => [
                'className' => 'Comment',
                'dependent' => true,
                'foreignKey' => 'article_featured_id',
                'conditions' => '',
                'fields' => '',
                'order' => '',
                'limit' => '',
                'offset' => '',
                'exclusive' => '',
                'finderQuery' => '',
                'counterQuery' => '',
            ]];

        $this->assertSame($expected, $TestModel->hasMany);
        $this->assertSame($expected, $TestFakeModel->hasMany);

        $this->assertEquals('Comment', $TestModel->Comment->name);
        $this->assertEquals('Comment', $TestFakeModel->Comment->name);

        $expected = [
            'Tag' => [
                'className' => 'Tag',
                'joinTable' => 'article_featureds_tags',
                'with' => 'ArticleFeaturedsTag',
                'dynamicWith' => true,
                'foreignKey' => 'article_featured_id',
                'associationForeignKey' => 'tag_id',
                'conditions' => '',
                'fields' => '',
                'order' => '',
                'limit' => '',
                'offset' => '',
                'unique' => true,
                'finderQuery' => '',
            ]];

        $this->assertSame($expected, $TestModel->hasAndBelongsToMany);
        $this->assertSame($expected, $TestFakeModel->hasAndBelongsToMany);

        $this->assertEquals('Tag', $TestModel->Tag->name);
        $this->assertEquals('Tag', $TestFakeModel->Tag->name);
    }

    /**
     * test creating associations with plugins. Ensure a double alias isn't created
     *
     * @return void
     */
    public function testAutoConstructPluginAssociations(): void
    {
        $Comment = ClassRegistry::init('IntegrationTestPluginComment');

        $this->assertEquals(3, count($Comment->belongsTo), 'Too many associations');
        $this->assertFalse(isset($Comment->belongsTo['TestLegacyPlugin.User']));
        $this->assertFalse(isset($Comment->belongsTo['TestLegacyPlugin.Source']));
        $this->assertTrue(isset($Comment->belongsTo['User']), 'Missing association');
        $this->assertTrue(isset($Comment->belongsTo['TestPluginArticle']), 'Missing association');
        $this->assertTrue(isset($Comment->belongsTo['Source']), 'Missing association');
    }

    /**
     * test Model::__construct
     *
     * ensure that $actsAS and $findMethods are merged.
     *
     * @return void
     */
    public function testConstruct(): void
    {
        $this->loadFixtures('Post');

        $TestModel = ClassRegistry::init('MergeVarPluginPost');
        $this->assertEquals(['Containable' => null, 'Tree' => null], $TestModel->actsAs);
        $this->assertTrue(isset($TestModel->Behaviors->Containable));
        $this->assertTrue(isset($TestModel->Behaviors->Tree));

        $TestModel = ClassRegistry::init('MergeVarPluginComment');
        $expected = ['Containable' => ['some_settings']];
        $this->assertEquals($expected, $TestModel->actsAs);
        $this->assertTrue(isset($TestModel->Behaviors->Containable));
    }

    /**
     * test Model::__construct
     *
     * ensure that $actsAS and $findMethods are merged.
     *
     * @return void
     */
    public function testConstructWithAlternateDataSource(): void
    {
        $TestModel = ClassRegistry::init([
            'class' => 'DoesntMatter', 'ds' => 'test', 'table' => false,
        ]);
        $this->assertEquals('test', $TestModel->useDbConfig);

        //deprecated but test it anyway
        $NewVoid = new TheVoid(null, false, 'other');
        $this->assertEquals('other', $NewVoid->useDbConfig);
    }

    /**
     * testIdentity method
     *
     * @return void
     */
    public function testIdentity(): void
    {
        $TestModel = new Test();
        $result = $TestModel->alias;
        $expected = 'Test';
        $this->assertEquals($expected, $result);

        $TestModel = new TestAlias();
        $result = $TestModel->alias;
        $expected = 'TestAlias';
        $this->assertEquals($expected, $result);

        $TestModel = new Test(['alias' => 'AnotherTest']);
        $result = $TestModel->alias;
        $expected = 'AnotherTest';
        $this->assertEquals($expected, $result);

        $TestModel = ClassRegistry::init('Test');
        $expected = null;
        $this->assertEquals($expected, $TestModel->plugin);

        $TestModel = ClassRegistry::init('TestLegacyPlugin.TestPluginComment');
        $expected = 'TestLegacyPlugin';
        $this->assertEquals($expected, $TestModel->plugin);
    }

    /**
     * testWithAssociation method
     *
     * @return void
     */
    public function testWithAssociation(): void
    {
        $this->loadFixtures('Something', 'SomethingElse', 'JoinThing');
        $TestModel = new Something();
        $result = $TestModel->SomethingElse->find('all');

        $expected = [
            [
                'SomethingElse' => [
                    'id' => '1',
                    'title' => 'First Post',
                    'body' => 'First Post Body',
                    'published' => 'Y',
                    'created' => '2007-03-18 10:39:23',
                    'updated' => '2007-03-18 10:41:31',
                    'afterFind' => 'Successfully added by AfterFind',
                ],
                'Something' => [
                    [
                        'id' => '3',
                        'title' => 'Third Post',
                        'body' => 'Third Post Body',
                        'published' => 'Y',
                        'created' => '2007-03-18 10:43:23',
                        'updated' => '2007-03-18 10:45:31',
                        'JoinThing' => [
                            'id' => '3',
                            'something_id' => '3',
                            'something_else_id' => '1',
                            'doomed' => true,
                            'created' => '2007-03-18 10:43:23',
                            'updated' => '2007-03-18 10:45:31',
                            'afterFind' => 'Successfully added by AfterFind',
                        ]]]],
            [
                'SomethingElse' => [
                    'id' => '2',
                    'title' => 'Second Post',
                    'body' => 'Second Post Body',
                    'published' => 'Y',
                    'created' => '2007-03-18 10:41:23',
                    'updated' => '2007-03-18 10:43:31',
                    'afterFind' => 'Successfully added by AfterFind',
                ],
                'Something' => [
                    [
                        'id' => '1',
                        'title' => 'First Post',
                        'body' => 'First Post Body',
                        'published' => 'Y',
                        'created' => '2007-03-18 10:39:23',
                        'updated' => '2007-03-18 10:41:31',
                        'JoinThing' => [
                            'id' => '1',
                            'something_id' => '1',
                            'something_else_id' => '2',
                            'doomed' => true,
                            'created' => '2007-03-18 10:39:23',
                            'updated' => '2007-03-18 10:41:31',
                            'afterFind' => 'Successfully added by AfterFind',
                        ]]]],
            [
                'SomethingElse' => [
                    'id' => '3',
                    'title' => 'Third Post',
                    'body' => 'Third Post Body',
                    'published' => 'Y',
                    'created' => '2007-03-18 10:43:23',
                    'updated' => '2007-03-18 10:45:31',
                    'afterFind' => 'Successfully added by AfterFind',
                ],
                'Something' => [
                    [
                        'id' => '2',
                        'title' => 'Second Post',
                        'body' => 'Second Post Body',
                        'published' => 'Y',
                        'created' => '2007-03-18 10:41:23',
                        'updated' => '2007-03-18 10:43:31',
                        'JoinThing' => [
                            'id' => '2',
                            'something_id' => '2',
                            'something_else_id' => '3',
                            'doomed' => false,
                            'created' => '2007-03-18 10:41:23',
                            'updated' => '2007-03-18 10:43:31',
                            'afterFind' => 'Successfully added by AfterFind',
                        ]]]]];
        $this->assertEquals($expected, $result);

        $result = $TestModel->find('all');
        $expected = [
            [
                'Something' => [
                    'id' => '1',
                    'title' => 'First Post',
                    'body' => 'First Post Body',
                    'published' => 'Y',
                    'created' => '2007-03-18 10:39:23',
                    'updated' => '2007-03-18 10:41:31',
                ],
                'SomethingElse' => [
                    [
                        'id' => '2',
                        'title' => 'Second Post',
                        'body' => 'Second Post Body',
                        'published' => 'Y',
                        'created' => '2007-03-18 10:41:23',
                        'updated' => '2007-03-18 10:43:31',
                        'JoinThing' => [
                            'doomed' => true,
                            'something_id' => '1',
                            'something_else_id' => '2',
                            'afterFind' => 'Successfully added by AfterFind',
                        ],
                        'afterFind' => 'Successfully added by AfterFind',
                    ]]],
            [
                'Something' => [
                    'id' => '2',
                    'title' => 'Second Post',
                    'body' => 'Second Post Body',
                    'published' => 'Y',
                    'created' => '2007-03-18 10:41:23',
                    'updated' => '2007-03-18 10:43:31',
                ],
                'SomethingElse' => [
                    [
                        'id' => '3',
                        'title' => 'Third Post',
                        'body' => 'Third Post Body',
                        'published' => 'Y',
                        'created' => '2007-03-18 10:43:23',
                        'updated' => '2007-03-18 10:45:31',
                        'JoinThing' => [
                            'doomed' => false,
                            'something_id' => '2',
                            'something_else_id' => '3',
                            'afterFind' => 'Successfully added by AfterFind',
                        ],
                        'afterFind' => 'Successfully added by AfterFind',
                    ]]],
            [
                'Something' => [
                    'id' => '3',
                    'title' => 'Third Post',
                    'body' => 'Third Post Body',
                    'published' => 'Y',
                    'created' => '2007-03-18 10:43:23',
                    'updated' => '2007-03-18 10:45:31',
                ],
                'SomethingElse' => [
                    [
                        'id' => '1',
                        'title' => 'First Post',
                        'body' => 'First Post Body',
                        'published' => 'Y',
                        'created' => '2007-03-18 10:39:23',
                        'updated' => '2007-03-18 10:41:31',
                        'JoinThing' => [
                            'doomed' => true,
                            'something_id' => '3',
                            'something_else_id' => '1',
                            'afterFind' => 'Successfully added by AfterFind',
                        ],
                        'afterFind' => 'Successfully added by AfterFind',
                    ]]]];
        $this->assertEquals($expected, $result);

        $result = $TestModel->findById(1);
        $expected = [
            'Something' => [
                'id' => '1',
                'title' => 'First Post',
                'body' => 'First Post Body',
                'published' => 'Y',
                'created' => '2007-03-18 10:39:23',
                'updated' => '2007-03-18 10:41:31',
            ],
            'SomethingElse' => [
                [
                    'id' => '2',
                    'title' => 'Second Post',
                    'body' => 'Second Post Body',
                    'published' => 'Y',
                    'created' => '2007-03-18 10:41:23',
                    'updated' => '2007-03-18 10:43:31',
                    'JoinThing' => [
                        'doomed' => true,
                        'something_id' => '1',
                        'something_else_id' => '2',
                        'afterFind' => 'Successfully added by AfterFind',
                    ],
                    'afterFind' => 'Successfully added by AfterFind',
                ]]];
        $this->assertEquals($expected, $result);

        $expected = $TestModel->findById(1);
        $TestModel->set($expected);
        $TestModel->save();
        $result = $TestModel->findById(1);
        $this->assertEquals($expected, $result);

        $TestModel->hasAndBelongsToMany['SomethingElse']['unique'] = false;
        $TestModel->create([
            'Something' => ['id' => 1],
            'SomethingElse' => [3, [
                'something_else_id' => 1,
                'doomed' => true,
            ]]]);

        $TestModel->save();

        $TestModel->hasAndBelongsToMany['SomethingElse']['order'] = 'SomethingElse.id ASC';
        $result = $TestModel->findById(1);
        $expected = [
            'Something' => [
                'id' => '1',
                'title' => 'First Post',
                'body' => 'First Post Body',
                'published' => 'Y',
                'created' => '2007-03-18 10:39:23',
            ],
            'SomethingElse' => [
                [
                    'id' => '1',
                    'title' => 'First Post',
                    'body' => 'First Post Body',
                    'published' => 'Y',
                    'created' => '2007-03-18 10:39:23',
                    'updated' => '2007-03-18 10:41:31',
                    'JoinThing' => [
                        'doomed' => true,
                        'something_id' => '1',
                        'something_else_id' => '1',
                        'afterFind' => 'Successfully added by AfterFind',
                    ],
                    'afterFind' => 'Successfully added by AfterFind',
                ],
                [
                    'id' => '2',
                    'title' => 'Second Post',
                    'body' => 'Second Post Body',
                    'published' => 'Y',
                    'created' => '2007-03-18 10:41:23',
                    'updated' => '2007-03-18 10:43:31',
                    'JoinThing' => [
                        'doomed' => true,
                        'something_id' => '1',
                        'something_else_id' => '2',
                        'afterFind' => 'Successfully added by AfterFind',
                    ],
                    'afterFind' => 'Successfully added by AfterFind',
                ],
                [
                    'id' => '3',
                    'title' => 'Third Post',
                    'body' => 'Third Post Body',
                    'published' => 'Y',
                    'created' => '2007-03-18 10:43:23',
                    'updated' => '2007-03-18 10:45:31',
                    'JoinThing' => [
                        'doomed' => false,
                        'something_id' => '1',
                        'something_else_id' => '3',
                        'afterFind' => 'Successfully added by AfterFind',
                    ],
                    'afterFind' => 'Successfully added by AfterFind',
                ],
            ]];
        $this->assertEquals(static::date(), $result['Something']['updated']);
        unset($result['Something']['updated']);
        $this->assertEquals($expected, $result);
    }

    /**
     * testFindSelfAssociations method
     *
     * @return void
     */
    public function testFindSelfAssociations(): void
    {
        $this->loadFixtures('Person');

        $TestModel = new Person();
        $TestModel->recursive = 2;
        $result = $TestModel->read(null, 1);
        $expected = [
            'Person' => [
                'id' => 1,
                'name' => 'person',
                'mother_id' => 2,
                'father_id' => 3,
            ],
            'Mother' => [
                'id' => 2,
                'name' => 'mother',
                'mother_id' => 4,
                'father_id' => 5,
                'Mother' => [
                    'id' => 4,
                    'name' => 'mother - grand mother',
                    'mother_id' => 0,
                    'father_id' => 0,
                ],
                'Father' => [
                    'id' => 5,
                    'name' => 'mother - grand father',
                    'mother_id' => 0,
                    'father_id' => 0,
                ]],
            'Father' => [
                'id' => 3,
                'name' => 'father',
                'mother_id' => 6,
                'father_id' => 7,
                'Father' => [
                    'id' => 7,
                    'name' => 'father - grand father',
                    'mother_id' => 0,
                    'father_id' => 0,
                ],
                'Mother' => [
                    'id' => 6,
                    'name' => 'father - grand mother',
                    'mother_id' => 0,
                    'father_id' => 0,
                ]]];

        $this->assertEquals($expected, $result);

        $TestModel->recursive = 3;
        $result = $TestModel->read(null, 1);
        $expected = [
            'Person' => [
                'id' => 1,
                'name' => 'person',
                'mother_id' => 2,
                'father_id' => 3,
            ],
            'Mother' => [
                'id' => 2,
                'name' => 'mother',
                'mother_id' => 4,
                'father_id' => 5,
                'Mother' => [
                    'id' => 4,
                    'name' => 'mother - grand mother',
                    'mother_id' => 0,
                    'father_id' => 0,
                    'Mother' => [],
                    'Father' => []],
                'Father' => [
                    'id' => 5,
                    'name' => 'mother - grand father',
                    'mother_id' => 0,
                    'father_id' => 0,
                    'Father' => [],
                    'Mother' => [],
                ]],
            'Father' => [
                'id' => 3,
                'name' => 'father',
                'mother_id' => 6,
                'father_id' => 7,
                'Father' => [
                    'id' => 7,
                    'name' => 'father - grand father',
                    'mother_id' => 0,
                    'father_id' => 0,
                    'Father' => [],
                    'Mother' => [],
                ],
                'Mother' => [
                    'id' => 6,
                    'name' => 'father - grand mother',
                    'mother_id' => 0,
                    'father_id' => 0,
                    'Mother' => [],
                    'Father' => [],
                ]]];

        $this->assertEquals($expected, $result);
    }
}
