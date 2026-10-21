<?php
/**
 * CakePHP(tm) Tests <https://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @since         CakePHP(tm) v 1.2.0.5669
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Test\TestCase\Legacy\Model\Behavior;

use Cake\Core\App;
use Cake\TestSuite\CakeTestCase;
use Tag;
use TranslatedArticle;
use TranslatedItem;
use User;

App::uses('Tag', 'Model');
App::uses('TranslatedArticle', 'Model');
App::uses('TranslatedItem', 'Model');
App::uses('User', 'Model');

/**
 * TranslateBehaviorTest class
 *
 * @package       Cake.Test.Case.Model.Behavior
 */
class TranslateBehaviorTest extends CakeTestCase
{
    /**
     * autoFixtures property
     *
     * @var bool
     */
    public $autoFixtures = false;

    /**
     * fixtures property
     *
     * @var array
     */
    public $fixtures = [
        'core.translated_item', 'core.translate', 'core.translate_table',
        'core.translated_article', 'core.translate_article', 'core.user', 'core.comment', 'core.tag', 'core.articles_tag',
        'core.translate_with_prefix',
    ];

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
    }

    /**
     * testTranslateModel method
     *
     * @return void
     */
    public function testTranslateModel(): void
    {
        $this->loadFixtures('TranslateTable', 'Tag', 'TranslatedItem', 'Translate', 'User', 'TranslatedArticle', 'TranslateArticle');
        $TestModel = new Tag();
        $TestModel->translateTable = 'another_i18n';
        $TestModel->Behaviors->load('Translate', ['title']);
        $translateModel = $TestModel->Behaviors->Translate->translateModel($TestModel);
        $this->assertEquals('I18nModel', $translateModel->name);
        $this->assertEquals('another_i18n', $translateModel->useTable);

        $TestModel = new User();
        $TestModel->Behaviors->load('Translate', ['title']);
        $translateModel = $TestModel->Behaviors->Translate->translateModel($TestModel);
        $this->assertEquals('I18nModel', $translateModel->name);
        $this->assertEquals('i18n', $translateModel->useTable);

        $TestModel = new TranslatedArticle();
        $translateModel = $TestModel->Behaviors->Translate->translateModel($TestModel);
        $this->assertEquals('TranslateArticleModel', $translateModel->name);
        $this->assertEquals('article_i18n', $translateModel->useTable);

        $TestModel = new TranslatedItem();
        $translateModel = $TestModel->Behaviors->Translate->translateModel($TestModel);
        $this->assertEquals('TranslateTestModel', $translateModel->name);
        $this->assertEquals('i18n', $translateModel->useTable);
    }

    /**
     * testLocaleSingle method
     *
     * @return void
     */
    public function testLocaleSingle(): void
    {
        $this->loadFixtures('Translate', 'TranslatedItem');

        $TestModel = new TranslatedItem();
        $TestModel->locale = 'eng';

        $result = $TestModel->read(null, 1);
        $expected = [
            'TranslatedItem' => [
                'id' => 1,
                'slug' => 'first_translated',
                'locale' => 'eng',
                'title' => 'Title #1',
                'content' => 'Content #1',
                'translated_article_id' => 1,
            ],
        ];
        $this->assertEquals($expected, $result);

        $result = $TestModel->find('all');
        $expected = [
            [
                'TranslatedItem' => [
                    'id' => 1,
                    'slug' => 'first_translated',
                    'locale' => 'eng',
                    'title' => 'Title #1',
                    'content' => 'Content #1',
                    'translated_article_id' => 1,
                ],
            ],
            [
                'TranslatedItem' => [
                    'id' => 2,
                    'slug' => 'second_translated',
                    'locale' => 'eng',
                    'title' => 'Title #2',
                    'content' => 'Content #2',
                    'translated_article_id' => 1,
                ],
            ],
            [
                'TranslatedItem' => [
                    'id' => 3,
                    'slug' => 'third_translated',
                    'locale' => 'eng',
                    'title' => 'Title #3',
                    'content' => 'Content #3',
                    'translated_article_id' => 1,
                ],
            ],
        ];
        $this->assertEquals($expected, $result);

        $result = $TestModel->field('title', ['TranslatedItem.id' => 1]);
        $expected = 'Title #1';
        $this->assertEquals($expected, $result);

        $result = $TestModel->read('title', 1);
        $expected = [
            'TranslatedItem' => [
                'id' => 1,
                'slug' => 'first_translated',
                'locale' => 'eng',
                'title' => 'Title #1',
                'translated_article_id' => 1,
            ],
        ];
        $this->assertEquals($expected, $result);

        $result = $TestModel->read('id, title', 1);
        $expected = [
            'TranslatedItem' => [
                'id' => 1,
                'locale' => 'eng',
                'title' => 'Title #1',
            ],
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * testSaveCreate method
     *
     * @return void
     */
    public function testSaveCreate(): void
    {
        $this->loadFixtures('Translate', 'TranslatedItem');

        $TestModel = new TranslatedItem();
        $TestModel->locale = 'spa';
        $data = [
            'slug' => 'fourth_translated',
            'title' => 'Leyenda #4',
            'content' => 'Contenido #4',
            'translated_article_id' => 1,
        ];
        $TestModel->create($data);
        $TestModel->save();
        $result = $TestModel->read();
        $expected = ['TranslatedItem' => array_merge($data, ['id' => $TestModel->id, 'locale' => 'spa'])];
        $this->assertEquals($expected, $result);
    }

    /**
     * testAttachDetach method
     *
     * @return void
     */
    public function testAttachDetach(): void
    {
        $this->loadFixtures('Translate', 'TranslatedItem');

        $TestModel = new TranslatedItem();

        $TestModel->unbindTranslation();
        $translations = ['title' => 'Title', 'content' => 'Content'];
        $TestModel->bindTranslation($translations, false);

        $result = array_keys($TestModel->hasMany);
        $expected = ['Title', 'Content'];
        $this->assertEquals($expected, $result);

        $TestModel->Behaviors->unload('Translate');
        $result = array_keys($TestModel->hasMany);
        $expected = [];
        $this->assertEquals($expected, $result);

        $result = isset($TestModel->Behaviors->Translate);
        $this->assertFalse($result);

        $result = isset($Behavior->settings[$TestModel->alias]);
        $this->assertFalse($result);

        $result = isset($Behavior->runtime[$TestModel->alias]);
        $this->assertFalse($result);

        $TestModel->Behaviors->load('Translate', ['title' => 'Title', 'content' => 'Content']);
        $result = array_keys($TestModel->hasMany);
        $expected = ['Title', 'Content'];
        $this->assertEquals($expected, $result);

        $result = isset($TestModel->Behaviors->Translate);
        $this->assertTrue($result);

        $Behavior = $TestModel->Behaviors->Translate;

        $result = isset($Behavior->settings[$TestModel->alias]);
        $this->assertTrue($result);

        $result = isset($Behavior->runtime[$TestModel->alias]);
        $this->assertTrue($result);
    }
}
