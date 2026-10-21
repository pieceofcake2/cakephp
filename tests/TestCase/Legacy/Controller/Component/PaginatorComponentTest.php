<?php
/**
 * PaginatorComponentTest file
 *
 * Series of tests for paginator component.
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
 * @package       Cake.Test.Case.Controller.Component
 * @since         CakePHP(tm) v 2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Test\TestCase\Legacy\Controller\Component;

use Cake\Controller\Component\PaginatorComponent;
use Cake\Controller\Controller;
use Cake\Core\App;
use Cake\Error\MissingModelException;
use Cake\Network\CakeRequest;
use Cake\TestSuite\CakeTestCase;
use Cake\Utility\Hash;

/**
 * PaginatorTestController class
 *
 * @package       Cake.Test.Case.Controller.Component
 */
class PaginatorTestController extends Controller
{
    /**
     * components property
     *
     * @var array
     */
    public $components = ['Paginator'];
}

class PaginatorComponentTest extends CakeTestCase
{
    /**
     * fixtures property
     *
     * @var array
     */
    public $fixtures = ['core.post', 'core.comment', 'core.author',
            'core.translated_article', 'core.translate_article', 'core.user'];

    /**
     * setup
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        App::build([
            'Model' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Model' . DS],
        ]);
        $this->request = new CakeRequest('controller_posts/index');
        $this->request->params['pass'] = $this->request->params['named'] = [];
        $this->Controller = new Controller($this->request);
        $this->Paginator = new PaginatorComponent($this->getMock('ComponentCollection'), []);
        $this->Paginator->Controller = $this->Controller;
        $this->Controller->Post = $this->getMock('Model');
        $this->Controller->Post->alias = 'Post';
    }

    /**
     * Asserts that posts are sorted by author_id.
     *
     * Posts 1 and 3 have the same author, so their relative order is not defined
     * (e.g. PostgreSQL may return them in either order).
     *
     * @param array $result Paginated PaginatorControllerPost records.
     * @return void
     */
    protected function assertSortedByAuthorId(array $result): void
    {
        $this->assertEquals([1, 1, 3], Hash::extract($result, '{n}.PaginatorControllerPost.author_id'));
        $ids = Hash::extract($result, '{n}.PaginatorControllerPost.id');
        $this->assertEquals(2, $ids[2]);
        $first = array_slice($ids, 0, 2);
        sort($first);
        $this->assertEquals([1, 3], $first);
    }

    /**
     * testPaginate method
     *
     * @return void
     */
    public function testPaginate(): void
    {
        $Controller = new PaginatorTestController($this->request);
        $Controller->uses = ['PaginatorControllerPost', 'PaginatorControllerComment'];
        $Controller->request->params['pass'] = ['1'];
        $Controller->request->query = [];
        $Controller->constructClasses();

        $Controller->PaginatorControllerPost->order = null;

        $Controller->Paginator->settings = [
            'order' => ['PaginatorControllerComment.id' => 'ASC'],
        ];
        $results = Hash::extract($Controller->Paginator->paginate('PaginatorControllerComment'), '{n}.PaginatorControllerComment.id');
        $this->assertEquals([1, 2, 3, 4, 5, 6], $results);

        $Controller->Paginator->settings = [
            'order' => ['PaginatorControllerComment.id DESC'],
        ];
        $results = Hash::extract($Controller->Paginator->paginate('PaginatorControllerComment'), '{n}.PaginatorControllerComment.id');
        $this->assertEquals([6, 5, 4, 3, 2, 1], $results);

        $Controller->Paginator->settings = [
            'order' => ['PaginatorControllerPost.id' => 'ASC'],
        ];
        $results = Hash::extract($Controller->Paginator->paginate('PaginatorControllerPost'), '{n}.PaginatorControllerPost.id');
        $this->assertEquals([1, 2, 3], $results);

        $Controller->modelClass = null;

        $Controller->uses[0] = 'Plugin.PaginatorControllerPost';
        $results = Hash::extract($Controller->Paginator->paginate(), '{n}.PaginatorControllerPost.id');
        $this->assertEquals([1, 2, 3], $results);

        $Controller->request->params['named'] = ['page' => '-1'];
        $results = Hash::extract($Controller->Paginator->paginate('PaginatorControllerPost'), '{n}.PaginatorControllerPost.id');
        $this->assertEquals(1, $Controller->params['paging']['PaginatorControllerPost']['page']);
        $this->assertEquals([1, 2, 3], $results);

        $Controller->request->params['named'] = ['sort' => 'PaginatorControllerPost.id', 'direction' => 'asc'];
        $results = Hash::extract($Controller->Paginator->paginate('PaginatorControllerPost'), '{n}.PaginatorControllerPost.id');
        $this->assertEquals(1, $Controller->params['paging']['PaginatorControllerPost']['page']);
        $this->assertEquals([1, 2, 3], $results);

        $Controller->request->params['named'] = ['sort' => 'PaginatorControllerPost.id', 'direction' => 'desc'];
        $results = Hash::extract($Controller->Paginator->paginate('PaginatorControllerPost'), '{n}.PaginatorControllerPost.id');
        $this->assertEquals(1, $Controller->params['paging']['PaginatorControllerPost']['page']);
        $this->assertEquals([3, 2, 1], $results);

        $Controller->request->params['named'] = ['sort' => 'id', 'direction' => 'desc'];
        $results = Hash::extract($Controller->Paginator->paginate('PaginatorControllerPost'), '{n}.PaginatorControllerPost.id');
        $this->assertEquals(1, $Controller->params['paging']['PaginatorControllerPost']['page']);
        $this->assertEquals([3, 2, 1], $results);

        $Controller->request->params['named'] = ['sort' => 'NotExisting.field', 'direction' => 'desc', 'limit' => 2];
        $Controller->Paginator->paginate('PaginatorControllerPost');
        $this->assertEquals(1, $Controller->params['paging']['PaginatorControllerPost']['page']);
        $this->assertEquals([], $Controller->PaginatorControllerPost->lastQueries[1]['order'], 'no order should be set.');

        $Controller->request->params['named'] = [
            'sort' => 'PaginatorControllerPost.author_id', 'direction' => 'allYourBase',
        ];
        $result = $Controller->Paginator->paginate('PaginatorControllerPost');
        $this->assertEquals(['PaginatorControllerPost.author_id' => 'asc'], $Controller->PaginatorControllerPost->lastQueries[0]['order']);
        $this->assertSortedByAuthorId($result);

        $Controller->request->params['named'] = [];
        $Controller->Paginator->settings = ['limit' => 0, 'maxLimit' => 10, 'paramType' => 'named'];
        $Controller->Paginator->paginate('PaginatorControllerPost');
        $this->assertSame(1, $Controller->params['paging']['PaginatorControllerPost']['page']);
        $this->assertSame($Controller->params['paging']['PaginatorControllerPost']['pageCount'], 3);
        $this->assertFalse($Controller->params['paging']['PaginatorControllerPost']['prevPage']);
        $this->assertTrue($Controller->params['paging']['PaginatorControllerPost']['nextPage']);

        $Controller->request->params['named'] = [];
        $Controller->Paginator->settings = ['limit' => 'garbage!', 'maxLimit' => 10, 'paramType' => 'named'];
        $Controller->Paginator->paginate('PaginatorControllerPost');
        $this->assertSame(1, $Controller->params['paging']['PaginatorControllerPost']['page']);
        $this->assertSame($Controller->params['paging']['PaginatorControllerPost']['pageCount'], 3);
        $this->assertFalse($Controller->params['paging']['PaginatorControllerPost']['prevPage']);
        $this->assertTrue($Controller->params['paging']['PaginatorControllerPost']['nextPage']);

        $Controller->request->params['named'] = [];
        $Controller->Paginator->settings = ['limit' => '-1', 'maxLimit' => 10, 'paramType' => 'named'];
        $Controller->Paginator->paginate('PaginatorControllerPost');

        $this->assertSame($Controller->params['paging']['PaginatorControllerPost']['limit'], 1);
        $this->assertSame(1, $Controller->params['paging']['PaginatorControllerPost']['page']);
        $this->assertSame($Controller->params['paging']['PaginatorControllerPost']['pageCount'], 3);
        $this->assertFalse($Controller->params['paging']['PaginatorControllerPost']['prevPage']);
        $this->assertTrue($Controller->params['paging']['PaginatorControllerPost']['nextPage']);

        $Controller->Paginator->settings = ['conditions' => ['PaginatorAuthor.user' => 'mariano']];
        $Controller->Paginator->paginate('PaginatorControllerPost');

        $this->assertSame(2, $Controller->params['paging']['PaginatorControllerPost']['count']);
    }

    public function testPaginateI18nConditionUser(): void
    {
        $Request = new CakeRequest('articles/index');
        $Controller = new PaginatorTestController($Request);
        $Controller->uses = ['TranslatedArticle'];
        $Controller->constructClasses();
        $Controller->TranslatedArticle->locale = 'eng';
        $Controller->Paginator->settings = [
            'recursive' => 0,
            'conditions' => [
                'User.user' => 'mariano',
            ],
        ];
        $result = $Controller->Paginator->paginate('TranslatedArticle');
        $this->assertEquals('Title (eng) #1', $result[0]['TranslatedArticle']['title']);
        $this->assertEquals('mariano', $result[0]['User']['user']);
        $this->assertEquals('Title (eng) #3', $result[1]['TranslatedArticle']['title']);
        $this->assertEquals('mariano', $result[1]['User']['user']);
        $this->assertCount(2, $result);
    }

    /**
     * Tests for missing models
     *
     * @return void
     */
    public function testPaginateMissingModel(): void
    {
        $this->expectException(MissingModelException::class);
        $Controller = new PaginatorTestController($this->request);
        $Controller->constructClasses();
        $Controller->Paginator->paginate('MissingModel');
    }

    /**
     * test paginate() and custom find, to make sure the correct count is returned.
     *
     * @return void
     */
    public function testPaginateCustomFind(): void
    {
        $Controller = new Controller($this->request);
        $Controller->uses = ['PaginatorCustomPost'];
        $Controller->constructClasses();
        $data = ['author_id' => 3, 'title' => 'Fourth Article', 'body' => 'Article Body, unpublished', 'published' => 'N'];
        $Controller->PaginatorCustomPost->create($data);
        $result = $Controller->PaginatorCustomPost->save();
        $this->assertTrue(!empty($result));

        $result = $Controller->paginate();
        $this->assertEquals([1, 2, 3, 4], Hash::extract($result, '{n}.PaginatorCustomPost.id'));

        $result = $Controller->params['paging']['PaginatorCustomPost'];
        $this->assertEquals(4, $result['current']);
        $this->assertEquals(4, $result['count']);

        $Controller->paginate = ['published'];
        $result = $Controller->paginate();
        $this->assertEquals([1, 2, 3], Hash::extract($result, '{n}.PaginatorCustomPost.id'));

        $result = $Controller->params['paging']['PaginatorCustomPost'];
        $this->assertEquals(3, $result['current']);
        $this->assertEquals(3, $result['count']);

        $Controller->paginate = ['published', 'limit' => 2];
        $result = $Controller->paginate();
        $this->assertEquals([1, 2], Hash::extract($result, '{n}.PaginatorCustomPost.id'));

        $result = $Controller->params['paging']['PaginatorCustomPost'];
        $this->assertEquals(2, $result['current']);
        $this->assertEquals(3, $result['count']);
        $this->assertEquals(2, $result['pageCount']);
        $this->assertTrue($result['nextPage']);
        $this->assertFalse($result['prevPage']);
    }
}
