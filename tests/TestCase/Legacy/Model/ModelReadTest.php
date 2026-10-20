<?php
/**
 * ModelReadTest file
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

use Article;
use Cake\Core\App;
use CustomArticle;
use DeviceType;
use Featured;
use User;

App::uses('Article', 'Model');
App::uses('CustomArticle', 'Model');
App::uses('DeviceType', 'Model');
App::uses('Featured', 'Model');
App::uses('User', 'Model');

/**
 * ModelReadTest
 *
 * @package       Cake.Test.Case.Model
 */
class ModelReadTest extends BaseModelTest
{
    /**
     * testBindUnbind method
     *
     * @return void
     */
    public function testBindUnbind(): void
    {
        $this->loadFixtures(
            'User',
            'Comment',
            'FeatureSet',
            'DeviceType',
            'DeviceTypeCategory',
            'ExteriorTypeCategory',
            'Device',
            'Document',
            'DocumentDirectory',
        );
        $TestModel = new User();

        $result = $TestModel->hasMany;
        $expected = [];
        $this->assertEquals($expected, $result);

        $result = $TestModel->bindModel(['hasMany' => ['Comment']]);
        $this->assertTrue($result);

        $result = $TestModel->find('all', [
            'fields' => 'User.id, User.user',
            'order' => ['User.id' => 'ASC'],
        ]);
        $expected = [
            [
                'User' => [
                    'id' => '1',
                    'user' => 'mariano',
                ],
                'Comment' => [
                    [
                        'id' => '3',
                        'article_id' => '1',
                        'user_id' => '1',
                        'comment' => 'Third Comment for First Article',
                        'published' => 'Y',
                        'created' => '2007-03-18 10:49:23',
                        'updated' => '2007-03-18 10:51:31',
                    ],
                    [
                        'id' => '4',
                        'article_id' => '1',
                        'user_id' => '1',
                        'comment' => 'Fourth Comment for First Article',
                        'published' => 'N',
                        'created' => '2007-03-18 10:51:23',
                        'updated' => '2007-03-18 10:53:31',
                    ],
                    [
                        'id' => '5',
                        'article_id' => '2',
                        'user_id' => '1',
                        'comment' => 'First Comment for Second Article',
                        'published' => 'Y',
                        'created' => '2007-03-18 10:53:23',
                        'updated' => '2007-03-18 10:55:31',
                    ]]],
            [
                'User' => [
                    'id' => '2',
                    'user' => 'nate',
                ],
                'Comment' => [
                    [
                        'id' => '1',
                        'article_id' => '1',
                        'user_id' => '2',
                        'comment' => 'First Comment for First Article',
                        'published' => 'Y',
                        'created' => '2007-03-18 10:45:23',
                        'updated' => '2007-03-18 10:47:31',
                    ],
                    [
                        'id' => '6',
                        'article_id' => '2',
                        'user_id' => '2',
                        'comment' => 'Second Comment for Second Article',
                        'published' => 'Y',
                        'created' => '2007-03-18 10:55:23',
                        'updated' => '2007-03-18 10:57:31',
                    ]]],
            [
                'User' => [
                    'id' => '3',
                    'user' => 'larry',
                ],
                'Comment' => [],
            ],
            [
                'User' => [
                    'id' => '4',
                    'user' => 'garrett',
                ],
                'Comment' => [
                    [
                        'id' => '2',
                        'article_id' => '1',
                        'user_id' => '4',
                        'comment' => 'Second Comment for First Article',
                        'published' => 'Y',
                        'created' => '2007-03-18 10:47:23',
                        'updated' => '2007-03-18 10:49:31',
                    ]]]];

        $this->assertEquals($expected, $result);

        $TestModel->resetAssociations();
        $result = $TestModel->hasMany;
        $this->assertSame([], $result);

        $result = $TestModel->bindModel(['hasMany' => ['Comment']], false);
        $this->assertTrue($result);

        $result = $TestModel->find('all', [
            'fields' => 'User.id, User.user',
            'order' => ['User.id' => 'ASC'],
        ]);

        $expected = [
            [
                'User' => [
                    'id' => '1',
                    'user' => 'mariano',
                ],
                'Comment' => [
                    [
                        'id' => '3',
                        'article_id' => '1',
                        'user_id' => '1',
                        'comment' => 'Third Comment for First Article',
                        'published' => 'Y',
                        'created' => '2007-03-18 10:49:23',
                        'updated' => '2007-03-18 10:51:31',
                    ],
                    [
                        'id' => '4',
                        'article_id' => '1',
                        'user_id' => '1',
                        'comment' => 'Fourth Comment for First Article',
                        'published' => 'N',
                        'created' => '2007-03-18 10:51:23',
                        'updated' => '2007-03-18 10:53:31',
                    ],
                    [
                        'id' => '5',
                        'article_id' => '2',
                        'user_id' => '1',
                        'comment' => 'First Comment for Second Article',
                        'published' => 'Y',
                        'created' => '2007-03-18 10:53:23',
                        'updated' => '2007-03-18 10:55:31',
                    ]]],
            [
                'User' => [
                    'id' => '2',
                    'user' => 'nate',
                ],
                'Comment' => [
                    [
                        'id' => '1',
                        'article_id' => '1',
                        'user_id' => '2',
                        'comment' => 'First Comment for First Article',
                        'published' => 'Y',
                        'created' => '2007-03-18 10:45:23',
                        'updated' => '2007-03-18 10:47:31',
                    ],
                    [
                        'id' => '6',
                        'article_id' => '2',
                        'user_id' => '2',
                        'comment' => 'Second Comment for Second Article',
                        'published' => 'Y',
                        'created' => '2007-03-18 10:55:23',
                        'updated' => '2007-03-18 10:57:31',
                    ]]],
            [
                'User' => [
                    'id' => '3',
                    'user' => 'larry',
                ],
                'Comment' => [],
            ],
            [
                'User' => [
                    'id' => '4',
                    'user' => 'garrett',
                ],
                'Comment' => [
                    [
                        'id' => '2',
                        'article_id' => '1',
                        'user_id' => '4',
                        'comment' => 'Second Comment for First Article',
                        'published' => 'Y',
                        'created' => '2007-03-18 10:47:23',
                        'updated' => '2007-03-18 10:49:31',
                    ]]]];

        $this->assertEquals($expected, $result);

        $result = $TestModel->hasMany;
        $expected = [
            'Comment' => [
                'className' => 'Comment',
                'foreignKey' => 'user_id',
                'conditions' => null,
                'fields' => null,
                'order' => null,
                'limit' => null,
                'offset' => null,
                'dependent' => null,
                'exclusive' => null,
                'finderQuery' => null,
                'counterQuery' => null,
            ]];
        $this->assertEquals($expected, $result);

        $result = $TestModel->unbindModel(['hasMany' => ['Comment']]);
        $this->assertTrue($result);

        $result = $TestModel->hasMany;
        $expected = [];
        $this->assertEquals($expected, $result);

        $result = $TestModel->find('all', [
            'fields' => 'User.id, User.user',
            'order' => ['User.id' => 'ASC'],
        ]);
        $expected = [
            ['User' => ['id' => '1', 'user' => 'mariano']],
            ['User' => ['id' => '2', 'user' => 'nate']],
            ['User' => ['id' => '3', 'user' => 'larry']],
            ['User' => ['id' => '4', 'user' => 'garrett']]];
        $this->assertEquals($expected, $result);

        $result = $TestModel->find('all', [
            'fields' => 'User.id, User.user',
            'order' => ['User.id' => 'ASC'],
        ]);
        $expected = [
            [
                'User' => [
                    'id' => '1',
                    'user' => 'mariano',
                ],
                'Comment' => [
                    [
                        'id' => '3',
                        'article_id' => '1',
                        'user_id' => '1',
                        'comment' => 'Third Comment for First Article',
                        'published' => 'Y',
                        'created' => '2007-03-18 10:49:23',
                        'updated' => '2007-03-18 10:51:31',
                    ],
                    [
                        'id' => '4',
                        'article_id' => '1',
                        'user_id' => '1',
                        'comment' => 'Fourth Comment for First Article',
                        'published' => 'N',
                        'created' => '2007-03-18 10:51:23',
                        'updated' => '2007-03-18 10:53:31',
                    ],
                    [
                        'id' => '5',
                        'article_id' => '2',
                        'user_id' => '1',
                        'comment' => 'First Comment for Second Article',
                        'published' => 'Y',
                        'created' => '2007-03-18 10:53:23',
                        'updated' => '2007-03-18 10:55:31',
                    ]]],
            [
                'User' => [
                    'id' => '2',
                    'user' => 'nate',
                ],
                'Comment' => [
                    [
                        'id' => '1',
                        'article_id' => '1',
                        'user_id' => '2',
                        'comment' => 'First Comment for First Article',
                        'published' => 'Y',
                        'created' => '2007-03-18 10:45:23',
                        'updated' => '2007-03-18 10:47:31',
                    ],
                    [
                        'id' => '6',
                        'article_id' => '2',
                        'user_id' => '2',
                        'comment' => 'Second Comment for Second Article',
                        'published' => 'Y',
                        'created' => '2007-03-18 10:55:23',
                        'updated' => '2007-03-18 10:57:31',
                    ]]],
            [
                'User' => [
                    'id' => '3',
                    'user' => 'larry',
                ],
                'Comment' => [],
            ],
            [
                'User' => [
                    'id' => '4',
                    'user' => 'garrett',
                ],
                'Comment' => [
                    [
                        'id' => '2',
                        'article_id' => '1',
                        'user_id' => '4',
                        'comment' =>
                        'Second Comment for First Article',
                        'published' => 'Y',
                        'created' => '2007-03-18 10:47:23',
                        'updated' => '2007-03-18 10:49:31',
                    ]]]];
        $this->assertEquals($expected, $result);

        $result = $TestModel->unbindModel(['hasMany' => ['Comment']], false);
        $this->assertTrue($result);

        $result = $TestModel->find('all', [
            'fields' => 'User.id, User.user',
            'order' => ['User.id' => 'ASC'],
        ]);
        $expected = [
            ['User' => ['id' => '1', 'user' => 'mariano']],
            ['User' => ['id' => '2', 'user' => 'nate']],
            ['User' => ['id' => '3', 'user' => 'larry']],
            ['User' => ['id' => '4', 'user' => 'garrett']]];
        $this->assertEquals($expected, $result);

        $result = $TestModel->hasMany;
        $expected = [];
        $this->assertEquals($expected, $result);

        $result = $TestModel->bindModel(['hasMany' => [
            'Comment' => ['className' => 'Comment', 'conditions' => 'Comment.published = \'Y\''],
        ]]);
        $this->assertTrue($result);

        $result = $TestModel->find('all', [
            'fields' => 'User.id, User.user',
            'order' => ['User.id' => 'ASC'],
        ]);
        $expected = [
            [
                'User' => [
                    'id' => '1',
                    'user' => 'mariano',
                ],
                'Comment' => [
                    [
                        'id' => '3',
                        'article_id' => '1',
                        'user_id' => '1',
                        'comment' => 'Third Comment for First Article',
                        'published' => 'Y',
                        'created' => '2007-03-18 10:49:23',
                        'updated' => '2007-03-18 10:51:31',
                    ],
                    [
                        'id' => '5',
                        'article_id' => '2',
                        'user_id' => '1',
                        'comment' => 'First Comment for Second Article',
                        'published' => 'Y',
                        'created' => '2007-03-18 10:53:23',
                        'updated' => '2007-03-18 10:55:31',
                    ]]],
            [
                'User' => [
                    'id' => '2',
                    'user' => 'nate',
                ],
                'Comment' => [
                    [
                        'id' => '1',
                        'article_id' => '1',
                        'user_id' => '2',
                        'comment' => 'First Comment for First Article',
                        'published' => 'Y',
                        'created' => '2007-03-18 10:45:23',
                        'updated' => '2007-03-18 10:47:31',
                    ],
                    [
                        'id' => '6',
                        'article_id' => '2',
                        'user_id' => '2',
                        'comment' => 'Second Comment for Second Article',
                        'published' => 'Y',
                        'created' => '2007-03-18 10:55:23',
                        'updated' => '2007-03-18 10:57:31',
                    ]]],
            [
                'User' => [
                    'id' => '3',
                    'user' => 'larry',
                ],
                'Comment' => [],
            ],
            [
                'User' => [
                    'id' => '4',
                    'user' => 'garrett',
                ],
                'Comment' => [
                    [
                        'id' => '2',
                        'article_id' => '1',
                        'user_id' => '4',
                        'comment' => 'Second Comment for First Article',
                        'published' => 'Y',
                        'created' => '2007-03-18 10:47:23',
                        'updated' => '2007-03-18 10:49:31',
                    ]]]];

        $this->assertEquals($expected, $result);

        $TestModel2 = new DeviceType();

        $expected = [
            'className' => 'FeatureSet',
            'foreignKey' => 'feature_set_id',
            'conditions' => '',
            'fields' => '',
            'order' => '',
            'counterCache' => '',
        ];
        $this->assertEquals($expected, $TestModel2->belongsTo['FeatureSet']);

        $TestModel2->bindModel([
            'belongsTo' => [
                'FeatureSet' => [
                    'className' => 'FeatureSet',
                    'conditions' => ['active' => true],
                ],
            ],
        ]);
        $expected['conditions'] = ['active' => true];
        $this->assertEquals($expected, $TestModel2->belongsTo['FeatureSet']);

        $TestModel2->bindModel([
            'belongsTo' => [
                'FeatureSet' => [
                    'className' => 'FeatureSet',
                    'foreignKey' => false,
                    'conditions' => ['Feature.name' => 'DeviceType.name'],
                ],
            ],
        ]);
        $expected['conditions'] = ['Feature.name' => 'DeviceType.name'];
        $expected['foreignKey'] = false;
        $this->assertEquals($expected, $TestModel2->belongsTo['FeatureSet']);

        $TestModel2->bindModel([
            'hasMany' => [
                'NewFeatureSet' => [
                    'className' => 'FeatureSet',
                    'conditions' => ['active' => true],
                ],
            ],
        ]);

        $expected = [
            'className' => 'FeatureSet',
            'conditions' => ['active' => true],
            'foreignKey' => 'device_type_id',
            'fields' => '',
            'order' => '',
            'limit' => '',
            'offset' => '',
            'dependent' => '',
            'exclusive' => '',
            'finderQuery' => '',
            'counterQuery' => '',
        ];
        $this->assertEquals($expected, $TestModel2->hasMany['NewFeatureSet']);
        $this->assertTrue(is_object($TestModel2->NewFeatureSet));
    }

    /**
     * testMultipleBelongsToWithSameClass method
     *
     * @return void
     */
    public function testMultipleBelongsToWithSameClass(): void
    {
        $this->loadFixtures(
            'DeviceType',
            'DeviceTypeCategory',
            'FeatureSet',
            'ExteriorTypeCategory',
            'Document',
            'Device',
            'DocumentDirectory',
        );

        $DeviceType = new DeviceType();

        $DeviceType->recursive = 2;
        $result = $DeviceType->read(null, 1);

        $expected = [
            'DeviceType' => [
                'id' => 1,
                'device_type_category_id' => 1,
                'feature_set_id' => 1,
                'exterior_type_category_id' => 1,
                'image_id' => 1,
                'extra1_id' => 1,
                'extra2_id' => 1,
                'name' => 'DeviceType 1',
                'order' => 0,
            ],
            'Image' => [
                'id' => 1,
                'document_directory_id' => 1,
                'name' => 'Document 1',
                'DocumentDirectory' => [
                    'id' => 1,
                    'name' => 'DocumentDirectory 1',
                ]],
            'Extra1' => [
                'id' => 1,
                'document_directory_id' => 1,
                'name' => 'Document 1',
                'DocumentDirectory' => [
                    'id' => 1,
                    'name' => 'DocumentDirectory 1',
                ]],
            'Extra2' => [
                'id' => 1,
                'document_directory_id' => 1,
                'name' => 'Document 1',
                'DocumentDirectory' => [
                    'id' => 1,
                    'name' => 'DocumentDirectory 1',
                ]],
            'DeviceTypeCategory' => [
                'id' => 1,
                'name' => 'DeviceTypeCategory 1',
            ],
            'FeatureSet' => [
                'id' => 1,
                'name' => 'FeatureSet 1',
            ],
            'ExteriorTypeCategory' => [
                'id' => 1,
                'image_id' => 1,
                'name' => 'ExteriorTypeCategory 1',
                'Image' => [
                    'id' => 1,
                    'device_type_id' => 1,
                    'name' => 'Device 1',
                    'typ' => 1,
                ]],
            'Device' => [
                [
                    'id' => 1,
                    'device_type_id' => 1,
                    'name' => 'Device 1',
                    'typ' => 1,
                ],
                [
                    'id' => 2,
                    'device_type_id' => 1,
                    'name' => 'Device 2',
                    'typ' => 1,
                ],
                [
                    'id' => 3,
                    'device_type_id' => 1,
                    'name' => 'Device 3',
                    'typ' => 2,
                ]]];

        $this->assertEquals($expected, $result);
    }

    public function testRecursiveFindAll(): void
    {
        $this->loadFixtures(
            'User',
            'Article',
            'Comment',
            'Tag',
            'ArticlesTag',
            'Attachment',
            'ArticleFeatured',
            'ArticleFeaturedsTags',
            'Featured',
            'Category',
        );
        $TestModel = new Article();

        $result = $TestModel->find('all', ['conditions' => ['Article.user_id' => 1]]);
        $expected = [
            [
                'Article' => [
                    'id' => '1',
                    'user_id' => '1',
                    'title' => 'First Article',
                    'body' => 'First Article Body',
                    'published' => 'Y',
                    'created' => '2007-03-18 10:39:23',
                    'updated' => '2007-03-18 10:41:31',
                ],
                'User' => [
                    'id' => '1',
                    'user' => 'mariano',
                    'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
                    'created' => '2007-03-17 01:16:23',
                    'updated' => '2007-03-17 01:18:31',
                ],
                'Comment' => [
                    [
                        'id' => '1',
                        'article_id' => '1',
                        'user_id' => '2',
                        'comment' => 'First Comment for First Article',
                        'published' => 'Y',
                        'created' => '2007-03-18 10:45:23',
                        'updated' => '2007-03-18 10:47:31',
                    ],
                    [
                        'id' => '2',
                        'article_id' => '1',
                        'user_id' => '4',
                        'comment' => 'Second Comment for First Article',
                        'published' => 'Y',
                        'created' => '2007-03-18 10:47:23',
                        'updated' => '2007-03-18 10:49:31',
                    ],
                    [
                        'id' => '3',
                        'article_id' => '1',
                        'user_id' => '1',
                        'comment' => 'Third Comment for First Article',
                        'published' => 'Y',
                        'created' => '2007-03-18 10:49:23',
                        'updated' => '2007-03-18 10:51:31',
                    ],
                    [
                        'id' => '4',
                        'article_id' => '1',
                        'user_id' => '1',
                        'comment' => 'Fourth Comment for First Article',
                        'published' => 'N',
                        'created' => '2007-03-18 10:51:23',
                        'updated' => '2007-03-18 10:53:31',
                    ],
                ],
                'Tag' => [
                    [
                        'id' => '1',
                        'tag' => 'tag1',
                        'created' => '2007-03-18 12:22:23',
                        'updated' => '2007-03-18 12:24:31',
                    ],
                    [
                        'id' => '2',
                        'tag' => 'tag2',
                        'created' => '2007-03-18 12:24:23',
                        'updated' => '2007-03-18 12:26:31',
                    ]]],
            [
                'Article' => [
                    'id' => '3',
                    'user_id' => '1',
                    'title' => 'Third Article',
                    'body' => 'Third Article Body',
                    'published' => 'Y',
                    'created' => '2007-03-18 10:43:23',
                    'updated' => '2007-03-18 10:45:31',
                ],
                'User' => [
                    'id' => '1',
                    'user' => 'mariano',
                    'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
                    'created' => '2007-03-17 01:16:23',
                    'updated' => '2007-03-17 01:18:31',
                ],
                'Comment' => [],
                'Tag' => [],
            ],
        ];
        $this->assertEquals($expected, $result);

        $result = $TestModel->find('all', [
            'conditions' => ['Article.user_id' => 3],
            'limit' => 1,
            'recursive' => 2,
        ]);

        $expected = [
            [
                'Article' => [
                    'id' => '2',
                    'user_id' => '3',
                    'title' => 'Second Article',
                    'body' => 'Second Article Body',
                    'published' => 'Y',
                    'created' => '2007-03-18 10:41:23',
                    'updated' => '2007-03-18 10:43:31',
                ],
                'User' => [
                    'id' => '3',
                    'user' => 'larry',
                    'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
                    'created' => '2007-03-17 01:20:23',
                    'updated' => '2007-03-17 01:22:31',
                ],
                'Comment' => [
                    [
                        'id' => '5',
                        'article_id' => '2',
                        'user_id' => '1',
                        'comment' => 'First Comment for Second Article',
                        'published' => 'Y',
                        'created' => '2007-03-18 10:53:23',
                        'updated' => '2007-03-18 10:55:31',
                        'Article' => [
                            'id' => '2',
                            'user_id' => '3',
                            'title' => 'Second Article',
                            'body' => 'Second Article Body',
                            'published' => 'Y',
                            'created' => '2007-03-18 10:41:23',
                            'updated' => '2007-03-18 10:43:31',
                        ],
                        'User' => [
                            'id' => '1',
                            'user' => 'mariano',
                            'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
                            'created' => '2007-03-17 01:16:23',
                            'updated' => '2007-03-17 01:18:31',
                        ],
                        'Attachment' => [
                            'id' => '1',
                            'comment_id' => 5,
                            'attachment' => 'attachment.zip',
                            'created' => '2007-03-18 10:51:23',
                            'updated' => '2007-03-18 10:53:31',
                        ],
                    ],
                    [
                        'id' => '6',
                        'article_id' => '2',
                        'user_id' => '2',
                        'comment' => 'Second Comment for Second Article',
                        'published' => 'Y',
                        'created' => '2007-03-18 10:55:23',
                        'updated' => '2007-03-18 10:57:31',
                        'Article' => [
                            'id' => '2',
                            'user_id' => '3',
                            'title' => 'Second Article',
                            'body' => 'Second Article Body',
                            'published' => 'Y',
                            'created' => '2007-03-18 10:41:23',
                            'updated' => '2007-03-18 10:43:31',
                        ],
                        'User' => [
                            'id' => '2',
                            'user' => 'nate',
                            'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
                            'created' => '2007-03-17 01:18:23',
                            'updated' => '2007-03-17 01:20:31',
                        ],
                        'Attachment' => [],
                    ],
                ],
                'Tag' => [
                    [
                        'id' => '1',
                        'tag' => 'tag1',
                        'created' => '2007-03-18 12:22:23',
                        'updated' => '2007-03-18 12:24:31',
                    ],
                    [
                        'id' => '3',
                        'tag' => 'tag3',
                        'created' => '2007-03-18 12:26:23',
                        'updated' => '2007-03-18 12:28:31',
                    ]]]];

        $this->assertEquals($expected, $result);

        $Featured = new Featured();

        $Featured->recursive = 2;
        $Featured->bindModel([
            'belongsTo' => [
                'ArticleFeatured' => [
                    'conditions' => "ArticleFeatured.published = 'Y'",
                    'fields' => 'id, title, user_id, published',
                ],
            ],
        ]);

        $Featured->ArticleFeatured->unbindModel([
            'hasMany' => ['Attachment', 'Comment'],
            'hasAndBelongsToMany' => ['Tag']],);

        $orderBy = 'ArticleFeatured.id ASC';
        $result = $Featured->find('all', [
            'order' => $orderBy, 'limit' => 3,
        ]);

        $expected = [
            [
                'Featured' => [
                    'id' => '1',
                    'article_featured_id' => '1',
                    'category_id' => '1',
                    'published_date' => '2007-03-31 10:39:23',
                    'end_date' => '2007-05-15 10:39:23',
                    'created' => '2007-03-18 10:39:23',
                    'updated' => '2007-03-18 10:41:31',
                ],
                'ArticleFeatured' => [
                    'id' => '1',
                    'title' => 'First Article',
                    'user_id' => '1',
                    'published' => 'Y',
                    'User' => [
                        'id' => '1',
                        'user' => 'mariano',
                        'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
                        'created' => '2007-03-17 01:16:23',
                        'updated' => '2007-03-17 01:18:31',
                    ],
                    'Category' => [],
                    'Featured' => [
                        'id' => '1',
                        'article_featured_id' => '1',
                        'category_id' => '1',
                        'published_date' => '2007-03-31 10:39:23',
                        'end_date' => '2007-05-15 10:39:23',
                        'created' => '2007-03-18 10:39:23',
                        'updated' => '2007-03-18 10:41:31',
                    ]],
                'Category' => [
                    'id' => '1',
                    'parent_id' => '0',
                    'name' => 'Category 1',
                    'created' => '2007-03-18 15:30:23',
                    'updated' => '2007-03-18 15:32:31',
                ]],
            [
                'Featured' => [
                    'id' => '2',
                    'article_featured_id' => '2',
                    'category_id' => '1',
                    'published_date' => '2007-03-31 10:39:23',
                    'end_date' => '2007-05-15 10:39:23',
                    'created' => '2007-03-18 10:39:23',
                    'updated' => '2007-03-18 10:41:31',
                ],
                'ArticleFeatured' => [
                    'id' => '2',
                    'title' => 'Second Article',
                    'user_id' => '3',
                    'published' => 'Y',
                    'User' => [
                        'id' => '3',
                        'user' => 'larry',
                        'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
                        'created' => '2007-03-17 01:20:23',
                        'updated' => '2007-03-17 01:22:31',
                    ],
                    'Category' => [],
                    'Featured' => [
                        'id' => '2',
                        'article_featured_id' => '2',
                        'category_id' => '1',
                        'published_date' => '2007-03-31 10:39:23',
                        'end_date' => '2007-05-15 10:39:23',
                        'created' => '2007-03-18 10:39:23',
                        'updated' => '2007-03-18 10:41:31',
                    ]],
                'Category' => [
                    'id' => '1',
                    'parent_id' => '0',
                    'name' => 'Category 1',
                    'created' => '2007-03-18 15:30:23',
                    'updated' => '2007-03-18 15:32:31',
                ]]];
        $this->assertEquals($expected, $result);
    }

    /**
     * test custom find method
     *
     * @return void
     */
    public function testfindCustom(): void
    {
        $this->loadFixtures('Article', 'User');
        $Article = new CustomArticle();
        $data = ['user_id' => 3, 'title' => 'Fourth Article', 'body' => 'Article Body, unpublished', 'published' => 'N'];
        $Article->create($data);
        $Article->save(null, false);
        $this->assertEquals(4, $Article->id);

        $result = $Article->find('published');
        $this->assertEquals(3, count($result));

        $result = $Article->find('unPublished');
        $this->assertEquals(1, count($result));
    }
}
