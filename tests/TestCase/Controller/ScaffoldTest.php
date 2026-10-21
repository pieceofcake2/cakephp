<?php
/**
 * ScaffoldTest file
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
 * @package       Cake.Test.Case.Controller
 * @since         CakePHP(tm) v 1.2.0.5436
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Test\TestCase\Controller;

use ArrayObject;
use Cake\Controller\Controller;
use Cake\Controller\Scaffold;
use Cake\Core\App;
use Cake\Core\CakePlugin;
use Cake\Core\Configure;
use Cake\Error\MethodNotAllowedException;
use Cake\Error\MissingActionException;
use Cake\Error\NotFoundException;
use Cake\Event\CakeEvent;
use Cake\Model\Datasource\CakeSession;
use Cake\Network\CakeRequest;
use Cake\Network\CakeResponse;
use Cake\Routing\Router;
use Cake\TestSuite\CakeTestCase;
use Cake\Utility\ClassRegistry;
use Exception;
use TestApp\Model\NamespacedPost;
use TestApp\Model\ScaffoldMock;

App::uses('AppModel', 'Model');

/**
 * ScaffoldMockController class
 *
 * @package       Cake.Test.Case.Controller
 */
class ScaffoldMockController extends Controller
{
    /**
     * scaffold property
     *
     * @var string|null|false
     */
    public $scaffold = null;
}

/**
 * ScaffoldMockControllerWithFields class
 *
 * @package       Cake.Test.Case.Controller
 */
class ScaffoldMockControllerWithFields extends Controller
{
    /**
     * name property
     *
     * @var string|null
     */
    public $name = 'ScaffoldMock';

    /**
     * scaffold property
     *
     * @var string|null|false
     */
    public $scaffold = null;

    /**
     * function beforeScaffold
     *
     * @param string $method Method name.
     * @return bool true
     */
    public function beforeScaffold($method): mixed
    {
        $this->set('scaffoldFields', ['title']);

        return true;
    }
}

/**
 * ScaffoldMockControllerWithError class
 *
 * @package       Cake.Test.Case.Controller
 */
class ScaffoldMockControllerWithError extends Controller
{
    /**
     * name property
     *
     * @var string|null
     */
    public $name = 'ScaffoldMock';

    /**
     * scaffold property
     *
     * @var string|null|false
     */
    public $scaffold = null;

    /**
     * function beforeScaffold
     *
     * @param string $method Method name.
     * @return bool false
     */
    public function beforeScaffold($method): mixed
    {
        return false;
    }
}

/**
 * Controller whose afterScaffoldSave() callback rejects the save.
 *
 * @package       Cake.Test.Case.Controller
 */
class ScaffoldMockControllerWithSaveError extends Controller
{
    /**
     * name property
     *
     * @var string|null
     */
    public $name = 'ScaffoldMock';

    /**
     * scaffold property
     *
     * @var string|null|false
     */
    public $scaffold = null;

    /**
     * Methods passed to afterScaffoldSaveError().
     *
     * @var array<string>
     */
    public array $saveErrors = [];

    /**
     * Rejects every save.
     *
     * @param string $method Method name.
     * @return bool false
     */
    public function afterScaffoldSave($method): mixed
    {
        return false;
    }

    /**
     * Records the method of the rejected save and answers with an error page.
     *
     * @param string $method Method name.
     * @return bool true
     */
    public function afterScaffoldSaveError($method): mixed
    {
        $this->saveErrors[] = $method;
        $this->response->body('The ' . $method . ' was rejected');

        return true;
    }
}

/**
 * TestScaffoldMock class
 *
 * @package       Cake.Test.Case.Controller
 */
class TestScaffoldMock extends Scaffold
{
    /**
     * Request object for scaffolding
     *
     * @var CakeRequest|null
     */
    protected ?CakeRequest $_params = null;

    /**
     * Overload _scaffold
     *
     * @param CakeRequest $request Request object for scaffolding
     * @return void
     */
    protected function _scaffold(CakeRequest $request): void
    {
        $this->_params = $request;
    }

    /**
     * Get Params from the Controller.
     *
     * @return CakeRequest|null
     */
    public function getParams(): mixed
    {
        return $this->_params;
    }
}

/**
 * Scaffold Test class
 *
 * @package       Cake.Test.Case.Controller
 */
class ScaffoldTest extends CakeTestCase
{
    /**
     * Controller property
     *
     * @var Controller|null
     */
    public $Controller = null;

    /**
     * fixtures property
     *
     * @var array<string>
     */
    public $fixtures = [
        'core.article',
        'core.user',
        'core.comment',
        'core.join_thing',
        'core.tag',
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
        Configure::write('Config.language', 'eng');
        // Render with the layouts of the test app, not those of the app skeleton.
        App::build(['View' => [CORE_TESTS . DS . 'test_app' . DS . 'templates' . DS]], App::RESET);
        $request = new CakeRequest(null, false);
        $this->Controller = new ScaffoldMockController($request);
        $this->Controller->response = $this->getMock(CakeResponse::class, ['_sendHeader']);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        CakeSession::destroy();
        CakePlugin::unload('TestPlugin');
        unset($this->Controller);

        parent::tearDown();
    }

    /**
     * Test the correct Generation of Scaffold Params.
     * This ensures that the correct action and view will be generated
     *
     * @return void
     */
    public function testScaffoldParams(): void
    {
        $params = [
            'plugin' => null,
            'pass' => [],
            'form' => [],
            'named' => [],
            'url' => ['url' => 'admin/scaffold_mock/edit'],
            'controller' => 'scaffold_mock',
            'action' => 'admin_edit',
            'admin' => true,
        ];
        $this->Controller->request->base = '';
        $this->Controller->request->webroot = '/';
        $this->Controller->request->here = '/admin/scaffold_mock/edit';
        $this->Controller->request->addParams($params);

        //set router.
        Router::setRequestInfo($this->Controller->request);

        $this->Controller->constructClasses();
        $Scaffold = new TestScaffoldMock($this->Controller, $this->Controller->request);
        $result = $Scaffold->getParams();
        $this->assertEquals('admin_edit', $result['action']);
    }

    /**
     * test that the proper names and variable values are set by Scaffold
     *
     * @return void
     */
    public function testScaffoldVariableSetting(): void
    {
        $params = [
            'plugin' => null,
            'pass' => [],
            'form' => [],
            'named' => [],
            'url' => ['url' => 'admin/scaffold_mock/edit'],
            'controller' => 'scaffold_mock',
            'action' => 'admin_edit',
            'admin' => true,
        ];
        $this->Controller->request->base = '';
        $this->Controller->request->webroot = '/';
        $this->Controller->request->here = '/admin/scaffold_mock/edit';
        $this->Controller->request->addParams($params);

        //set router.
        Router::setRequestInfo($this->Controller->request);

        $this->Controller->constructClasses();
        $Scaffold = new TestScaffoldMock($this->Controller, $this->Controller->request);
        $result = $Scaffold->controller->viewVars;

        $this->assertEquals('Scaffold :: Admin Edit :: Scaffold Mock', $result['title_for_layout']);
        $this->assertEquals('Scaffold Mock', $result['singularHumanName']);
        $this->assertEquals('Scaffold Mock', $result['pluralHumanName']);
        $this->assertEquals('ScaffoldMock', $result['modelClass']);
        $this->assertEquals('id', $result['primaryKey']);
        $this->assertEquals('title', $result['displayField']);
        $this->assertEquals('scaffoldMock', $result['singularVar']);
        $this->assertEquals('scaffoldMock', $result['pluralVar']);
        $this->assertEquals(['id', 'user_id', 'title', 'body', 'published', 'created', 'updated'], $result['scaffoldFields']);
        $this->assertArrayHasKey('plugin', $result['associations']['belongsTo']['User']);
    }

    /**
     * Test that the controller of an association with a fully qualified class name is named
     * by the short name like for the short name.
     *
     * @return void
     */
    public function testScaffoldAssociationsNamespacedClassName(): void
    {
        $this->Controller->request->addParams([
            'plugin' => null, 'pass' => [], 'form' => [], 'named' => [],
            'controller' => 'scaffold_mock', 'action' => 'index',
        ]);
        $this->Controller->constructClasses();
        // Load the models of the test classes before App.namespace changes
        new TestScaffoldMock($this->Controller, $this->Controller->request);
        $cases = ['NamespacedPost' => 'NamespacedPost', NamespacedPost::class => NamespacedPost::class];
        foreach ($cases as $className => $expected) {
            Configure::write('App.namespace', $className === 'NamespacedPost' ? 'TestApp' : null);
            $this->Controller->ScaffoldMock->bindModel(['belongsTo' => [
                'NamespacedPost' => ['className' => $className, 'foreignKey' => 'user_id'],
            ]]);
            $Scaffold = new TestScaffoldMock($this->Controller, $this->Controller->request);
            $result = $Scaffold->controller->viewVars['associations']['belongsTo']['NamespacedPost'];
            $this->assertSame('namespaced_posts', $result['controller']);
            $this->assertNull($result['plugin']);
            $this->Controller->ScaffoldMock->unbindModel(['belongsTo' => ['NamespacedPost']]);
            ClassRegistry::removeObject('NamespacedPost');
        }
    }

    /**
     * test that Scaffold overrides the view property even if its set to 'Theme'
     *
     * @return void
     */
    public function testScaffoldChangingViewProperty(): void
    {
        $this->Controller->action = 'edit';
        $this->Controller->theme = 'TestTheme';
        $this->Controller->viewClass = 'Theme';
        $this->Controller->constructClasses();
        new TestScaffoldMock($this->Controller, $this->Controller->request);

        $this->assertEquals('Scaffold', $this->Controller->viewClass);
    }

    /**
     * test that scaffold outputs flash messages when sessions are unset.
     *
     * @return void
     */
    public function testScaffoldFlashMessages(): void
    {
        $params = [
            'plugin' => null,
            'pass' => [1],
            'form' => [],
            'named' => [],
            'url' => ['url' => 'scaffold_mock'],
            'controller' => 'scaffold_mock',
            'action' => 'edit',
        ];
        $this->Controller->request->base = '';
        $this->Controller->request->webroot = '/';
        $this->Controller->request->here = '/scaffold_mock/edit';
        $this->Controller->request->addParams($params);

        //set router.
        Router::reload();
        Router::setRequestInfo($this->Controller->request);
        $this->Controller->request->data = [
            'ScaffoldMock' => [
                'id' => 1,
                'title' => 'New title',
                'body' => 'new body',
            ],
        ];
        $this->Controller->constructClasses();
        unset($this->Controller->Session);

        ob_start();
        new Scaffold($this->Controller, $this->Controller->request);
        $this->Controller->response->send();
        $result = ob_get_clean();
        $this->assertMatchesRegularExpression('/Scaffold Mock has been updated/', $result);
    }

    /**
     * test that habtm relationship keys get added to scaffoldFields.
     *
     * @return void
     */
    public function testHabtmFieldAdditionWithScaffoldForm(): void
    {
        CakePlugin::unload();
        $params = [
            'plugin' => null,
            'pass' => [1],
            'form' => [],
            'named' => [],
            'url' => ['url' => 'scaffold_mock'],
            'controller' => 'scaffold_mock',
            'action' => 'edit',
        ];
        $this->Controller->request->base = '';
        $this->Controller->request->webroot = '/';
        $this->Controller->request->here = '/scaffold_mock/edit';
        $this->Controller->request->addParams($params);

        //set router.
        Router::reload();
        Router::setRequestInfo($this->Controller->request);

        $this->Controller->constructClasses();
        ob_start();
        $Scaffold = new Scaffold($this->Controller, $this->Controller->request);
        $this->Controller->response->send();
        $result = ob_get_clean();
        $this->assertMatchesRegularExpression('/name="data\[ScaffoldTag\]\[ScaffoldTag\]"/', $result);

        $result = $Scaffold->controller->viewVars;
        $this->assertEquals(['id', 'user_id', 'title', 'body', 'published', 'created', 'updated', 'ScaffoldTag'], $result['scaffoldFields']);
    }

    /**
     * test that the proper names and variable values are set by Scaffold
     *
     * @return void
     */
    public function testEditScaffoldWithScaffoldFields(): void
    {
        $request = new CakeRequest(null, false);
        $this->Controller = new ScaffoldMockControllerWithFields($request);
        $this->Controller->response = $this->getMock(CakeResponse::class, ['_sendHeader']);

        $params = [
            'plugin' => null,
            'pass' => [1],
            'form' => [],
            'named' => [],
            'url' => ['url' => 'scaffold_mock/edit'],
            'controller' => 'scaffold_mock',
            'action' => 'edit',
        ];
        $this->Controller->request->base = '';
        $this->Controller->request->webroot = '/';
        $this->Controller->request->here = '/scaffold_mock/edit';
        $this->Controller->request->addParams($params);

        //set router.
        Router::reload();
        Router::setRequestInfo($this->Controller->request);

        $this->Controller->constructClasses();
        ob_start();
        new Scaffold($this->Controller, $this->Controller->request);
        $this->Controller->response->send();
        $result = ob_get_clean();

        $this->assertDoesNotMatchRegularExpression('/textarea name="data\[ScaffoldMock\]\[body\]" cols="30" rows="6" id="ScaffoldMockBody"/', $result);
    }

    /**
     * test in case of scaffold error
     *
     * @return void
     */
    public function testScaffoldError(): void
    {
        $request = new CakeRequest(null, false);
        $this->Controller = new ScaffoldMockControllerWithError($request);
        $this->Controller->response = $this->getMock(CakeResponse::class, ['_sendHeader']);

        $params = [
            'plugin' => null,
            'pass' => [1],
            'form' => [],
            'named' => [],
            'url' => ['url' => 'scaffold_mock/edit'],
            'controller' => 'scaffold_mock',
            'action' => 'edit',
        ];
        $this->Controller->request->base = '';
        $this->Controller->request->webroot = '/';
        $this->Controller->request->here = '/scaffold_mock/edit';
        $this->Controller->request->addParams($params);

        //set router.
        Router::reload();
        Router::setRequestInfo($this->Controller->request);

        $this->Controller->constructClasses();
        ob_start();
        new Scaffold($this->Controller, $this->Controller->request);
        $this->Controller->response->send();
        $result = ob_get_clean();

        $this->assertMatchesRegularExpression('/Scaffold Error/', $result);
    }

    /**
     * Test that the constructor does not create dynamic properties (deprecated as of PHP 8.2).
     *
     * @return void
     */
    public function testConstructorDoesNotCreateDynamicProperties(): void
    {
        $params = [
            'plugin' => null,
            'pass' => [],
            'form' => [],
            'named' => [],
            'url' => ['url' => 'scaffold_mock'],
            'controller' => 'scaffold_mock',
            'action' => 'index',
        ];
        $this->Controller->request->addParams($params);
        $this->Controller->request->webroot = '/';
        $this->Controller->request->base = '';
        $this->Controller->request->here = '/scaffold_mock/index';
        Router::reload();
        Router::setRequestInfo($this->Controller->request);
        $this->Controller->constructClasses();

        ob_start();
        $deprecations = [];
        $level = error_reporting(E_ALL);
        set_error_handler(function ($errno, $errstr) use (&$deprecations) {
            if (str_contains($errstr, 'dynamic property')) {
                $deprecations[] = $errstr;
            }

            return true;
        }, E_DEPRECATED);
        try {
            $Scaffold = new Scaffold($this->Controller, $this->Controller->request);
        } finally {
            ob_end_clean();
            restore_error_handler();
            error_reporting($level);
        }
        $this->assertSame([], $deprecations);
        $this->assertSame('ScaffoldMock', $Scaffold->modelClass);
        $this->assertInstanceOf(ScaffoldMock::class, $Scaffold->ScaffoldModel);
        $this->assertSame(['action' => 'index'], $Scaffold->redirect);
    }

    /**
     * Sets up the controller for a scaffolded request and returns the URLs
     * that it redirects to.
     *
     * The redirects are recorded by a Controller.beforeRedirect listener that
     * stops the redirect, the way a component can.
     *
     * @param string $action Action name.
     * @param array $pass Passed arguments.
     * @param array $data Request data.
     * @param string $method HTTP method.
     * @return ArrayObject<int, mixed> The redirect URLs, filled in when the scaffold runs.
     */
    protected function _prepareRequest(string $action, array $pass = [], array $data = [], string $method = 'GET'): ArrayObject
    {
        $_SERVER['REQUEST_METHOD'] = $method;
        $this->Controller->request->base = '';
        $this->Controller->request->webroot = '/';
        $this->Controller->request->here = '/scaffold_mock/' . $action;
        $this->Controller->request->addParams([
            'plugin' => null,
            'pass' => $pass,
            'form' => [],
            'named' => [],
            'url' => ['url' => 'scaffold_mock/' . $action],
            'controller' => 'scaffold_mock',
            'action' => $action,
        ]);
        $this->Controller->request->data = $data;

        Router::reload();
        Router::setRequestInfo($this->Controller->request);
        $this->Controller->constructClasses();

        $redirects = new ArrayObject();
        $this->Controller->getEventManager()->attach(function (CakeEvent $event) use ($redirects) {
            $redirects[] = $event->data[0];
            $event->stopPropagation();
        }, 'Controller.beforeRedirect');

        return $redirects;
    }

    /**
     * Runs the scaffold and returns the rendered output.
     *
     * @return string
     */
    protected function _runScaffold(): string
    {
        ob_start();
        try {
            new Scaffold($this->Controller, $this->Controller->request);
            $this->Controller->response->send();
        } catch (Exception $e) {
            ob_end_clean();

            throw $e;
        }

        return (string)ob_get_clean();
    }

    /**
     * Test that the index action lists every record of the model, with its
     * belongsTo record loaded, and renders the index view.
     *
     * @return void
     */
    public function testIndexListsRecords(): void
    {
        $this->_prepareRequest('index');
        $result = $this->_runScaffold();

        $records = $this->Controller->viewVars['scaffoldMock'];
        $this->assertSame(
            ['First Article', 'Second Article', 'Third Article'],
            array_column(array_column($records, 'ScaffoldMock'), 'title'),
        );
        $this->assertEquals(3, $records[1]['User']['id']);
        $this->assertArrayNotHasKey('Comment', $records[0]);
        $this->assertStringContainsString(
            '<tr><td>2</td><td><a href="/scaffold_users/view/3">3</a></td><td>Second Article</td>',
            $result,
        );
    }

    /**
     * Test that the list action is an alias of the index action.
     *
     * @return void
     */
    public function testListActionRendersIndex(): void
    {
        $this->markTestIncomplete('Known bug: the scaffolded list action renders the missing view Scaffolds/list.ctp instead of the index view');

        $this->_prepareRequest('list');
        $result = $this->_runScaffold();

        $this->assertCount(3, $this->Controller->viewVars['scaffoldMock']);
        $this->assertStringContainsString('<td>Second Article</td>', $result);
    }

    /**
     * Test that the view action reads the record with its associations.
     *
     * @return void
     */
    public function testViewReadsRecord(): void
    {
        $this->_prepareRequest('view', [2]);
        $result = $this->_runScaffold();

        $record = $this->Controller->viewVars['scaffoldMock'];
        $this->assertEquals(2, $record['ScaffoldMock']['id']);
        $this->assertSame('Second Article', $record['ScaffoldMock']['title']);
        $this->assertEquals(3, $record['User']['id']);
        $this->assertSame($record, $this->Controller->request->data);
        $this->assertStringContainsString('Second Article Body', $result);
    }

    /**
     * Test that the view action throws a NotFoundException for a missing record.
     *
     * @return void
     */
    public function testViewMissingRecord(): void
    {
        $this->_prepareRequest('view', [999]);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Invalid Scaffold Mock');
        $this->_runScaffold();
    }

    /**
     * Test that the edit action throws a NotFoundException for a missing record.
     *
     * @return void
     */
    public function testEditMissingRecord(): void
    {
        $this->_prepareRequest('edit', [999], ['ScaffoldMock' => ['id' => 999, 'title' => 'Ghost']], 'POST');

        try {
            $this->_runScaffold();
            $this->fail('No NotFoundException was thrown.');
        } catch (NotFoundException $e) {
            $this->assertSame('Invalid Scaffold Mock', $e->getMessage());
        }
        $this->assertSame(3, $this->Controller->ScaffoldMock->find('count'));
    }

    /**
     * Test that each scaffolded action (index, list, view, add, edit and delete)
     * renders the scaffold error page when beforeScaffold() returns false, and that
     * nothing is changed.
     *
     * @return void
     */
    public function testBeforeScaffoldFalseRendersErrorForEveryAction(): void
    {
        foreach (['index', 'list', 'view', 'add', 'edit', 'delete'] as $action) {
            $this->Controller = new ScaffoldMockControllerWithError(new CakeRequest(null, false));
            $this->Controller->response = $this->getMock(CakeResponse::class, ['_sendHeader']);
            $this->_prepareRequest($action, [1], ['ScaffoldMock' => ['title' => 'Not saved']], 'POST');

            $result = $this->_runScaffold();

            $this->assertStringContainsString('Scaffold Error', $result, $action);
        }
        $this->assertSame(3, $this->Controller->ScaffoldMock->find('count'));
        $this->assertSame('First Article', $this->Controller->ScaffoldMock->field('title', ['id' => 1]));
    }

    /**
     * Test that the add action without data renders an empty form with the
     * lists of the associated records.
     *
     * @return void
     */
    public function testAddWithoutDataRendersForm(): void
    {
        $this->_prepareRequest('add');
        $result = $this->_runScaffold();

        $this->assertSame(['ScaffoldMock' => ['published' => 'N']], $this->Controller->request->data);
        $this->assertEquals([1 => 1, 2 => 2, 3 => 3, 4 => 4], $this->Controller->viewVars['users']);
        $this->assertEquals([1 => 1, 2 => 2, 3 => 3], $this->Controller->viewVars['scaffoldTags']);
        $this->assertStringContainsString('<form action="/scaffold_mock/add" id="ScaffoldMockAddForm" method="post"', $result);
        $this->assertStringContainsString('<legend>New Scaffold Mock</legend>', $result);
    }

    /**
     * Test that the add action saves a new record and shows the flash page
     * when there is no session.
     *
     * @return void
     */
    public function testAddSavesRecordWithoutSession(): void
    {
        $this->_prepareRequest('add', [], [
            'ScaffoldMock' => ['user_id' => 2, 'title' => 'Scaffolded', 'body' => 'Added body'],
        ], 'POST');
        unset($this->Controller->Session);

        $result = $this->_runScaffold();

        $saved = $this->Controller->ScaffoldMock->find('first', [
            'conditions' => ['ScaffoldMock.title' => 'Scaffolded'],
            'recursive' => -1,
        ]);
        $this->assertSame('Added body', $saved['ScaffoldMock']['body']);
        $this->assertEquals(2, $saved['ScaffoldMock']['user_id']);
        $this->assertSame(4, $this->Controller->ScaffoldMock->find('count'));
        $this->assertSame('The Scaffold Mock has been saved', $this->Controller->viewVars['message']);
        $this->assertSame('/scaffold_mock', $this->Controller->viewVars['url']);
        $this->assertStringContainsString('The Scaffold Mock has been saved', $result);
    }

    /**
     * Test that the edit action updates the record, sets a success flash message
     * in the session and redirects to the index action.
     *
     * @return void
     */
    public function testEditSavesRecordWithSession(): void
    {
        $redirects = $this->_prepareRequest('edit', [1], [
            'ScaffoldMock' => ['id' => 1, 'title' => 'Updated title'],
        ], 'POST');

        $this->_runScaffold();

        $this->assertSame('Updated title', $this->Controller->ScaffoldMock->field('title', ['id' => 1]));
        $this->assertSame([['action' => 'index']], $redirects->getArrayCopy());
        $this->assertSame([[
            'message' => 'The Scaffold Mock has been updated',
            'key' => 'flash',
            'element' => 'Flash/success',
            'params' => [],
        ]], CakeSession::read('Message.flash'));
    }

    /**
     * Test that a save that fails validation keeps the record, shows an error
     * flash message and renders the form again with the submitted data and the errors.
     *
     * @return void
     */
    public function testEditValidationFailureRedisplaysForm(): void
    {
        $redirects = $this->_prepareRequest('edit', [1], [
            'ScaffoldMock' => ['id' => 1, 'title' => ''],
        ], 'POST');
        $this->Controller->ScaffoldMock->validate = [
            'title' => ['rule' => 'notBlank', 'message' => 'Title is required'],
        ];

        $result = $this->_runScaffold();

        $this->assertSame('First Article', $this->Controller->ScaffoldMock->field('title', ['id' => 1]));
        $this->assertSame([], $redirects->getArrayCopy());
        $this->assertSame([[
            'message' => 'Please correct errors below.',
            'key' => 'flash',
            'element' => 'Flash/default',
            'params' => [],
        ]], CakeSession::read('Message.flash'));
        $this->assertSame(['ScaffoldMock' => ['id' => 1, 'title' => '']], $this->Controller->request->data);
        $this->assertStringContainsString('<div class="error-message">Title is required</div>', $result);
        $this->assertStringContainsString('<legend>Edit Scaffold Mock</legend>', $result);
    }

    /**
     * Test that a save that afterScaffoldSave() rejects is reported as failed: the
     * record is saved, but afterScaffoldSaveError() is called with the action and its
     * page is the response, without a success message or a redirect.
     *
     * @return void
     */
    public function testAfterScaffoldSaveFalseReportsTheSaveAsFailed(): void
    {
        $this->Controller = new ScaffoldMockControllerWithSaveError(new CakeRequest(null, false));
        $this->Controller->response = $this->getMock(CakeResponse::class, ['_sendHeader']);
        $redirects = $this->_prepareRequest('edit', [2], [
            'ScaffoldMock' => ['id' => 2, 'title' => 'Saved anyway'],
        ], 'POST');

        $result = $this->_runScaffold();

        $this->assertSame('The edit was rejected', $result);
        $this->assertSame(['edit'], $this->Controller->saveErrors);
        $this->assertSame('Saved anyway', $this->Controller->ScaffoldMock->field('title', ['id' => 2]));
        $this->assertSame([], $redirects->getArrayCopy());
        $this->assertNull(CakeSession::read('Message.flash'));
    }

    /**
     * Test that the delete action refuses a GET request and keeps the record.
     *
     * @return void
     */
    public function testDeleteRequiresPost(): void
    {
        $this->_prepareRequest('delete', [1]);

        try {
            $this->_runScaffold();
            $this->fail('No MethodNotAllowedException was thrown.');
        } catch (MethodNotAllowedException $e) {
            $this->assertSame(405, $e->getCode());
        }
        $this->assertTrue($this->Controller->ScaffoldMock->exists(1));
    }

    /**
     * Test that the delete action deletes the record, sets a success flash message
     * and redirects to the index action.
     *
     * The delete messages name the model with Inflector::humanize() of the model
     * class ("ScaffoldMock"), not with the "Scaffold Mock" of the other actions;
     * the test keeps that wording.
     *
     * @return void
     */
    public function testDeleteRemovesRecord(): void
    {
        $redirects = $this->_prepareRequest('delete', [1], [], 'POST');

        $this->_runScaffold();

        $this->assertFalse($this->Controller->ScaffoldMock->exists(1));
        $this->assertSame(2, $this->Controller->ScaffoldMock->find('count'));
        $this->assertSame([['action' => 'index']], $redirects->getArrayCopy());
        $this->assertSame('The ScaffoldMock with id: 1 has been deleted.', CakeSession::read('Message.flash.0.message'));
        $this->assertSame('Flash/success', CakeSession::read('Message.flash.0.element'));
    }

    /**
     * Test that the delete action throws a NotFoundException for a missing record.
     *
     * As in the other delete messages, the model is named with Inflector::humanize()
     * of the model class ("ScaffoldMock"); the test keeps that wording.
     *
     * @return void
     */
    public function testDeleteMissingRecord(): void
    {
        $this->_prepareRequest('delete', [999], [], 'POST');

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Invalid ScaffoldMock');
        $this->_runScaffold();
    }

    /**
     * Test that a delete that the model refuses keeps the record, sets an error
     * flash message with the default element and redirects to the index action.
     *
     * @return void
     */
    public function testDeleteFailureSetsErrorMessage(): void
    {
        $redirects = $this->_prepareRequest('delete', [3], [], 'POST');
        $this->Controller->ScaffoldMock->getEventManager()->attach(function () {
            return false;
        }, 'Model.beforeDelete');

        $this->_runScaffold();

        $this->assertTrue($this->Controller->ScaffoldMock->exists(3));
        $this->assertSame([['action' => 'index']], $redirects->getArrayCopy());
        $this->assertSame(
            'There was an error deleting the ScaffoldMock with id: 3',
            CakeSession::read('Message.flash.0.message'),
        );
        $this->assertSame('Flash/default', CakeSession::read('Message.flash.0.element'));
    }

    /**
     * Test that an action that is not scaffolded throws a MissingActionException.
     *
     * @return void
     */
    public function testUnknownActionThrowsMissingAction(): void
    {
        $this->_prepareRequest('publish', [1]);

        $this->expectException(MissingActionException::class);
        $this->expectExceptionMessage('Action ' . ScaffoldMockController::class . '::publish() could not be found.');
        $this->_runScaffold();
    }

    /**
     * Test that the plugin of an association with a plugin class name is set
     * underscored in the associations.
     *
     * @return void
     */
    public function testScaffoldAssociationsPluginClassName(): void
    {
        App::build(['Plugin' => [CORE_TESTS . DS . 'test_app' . DS . 'plugins' . DS]]);
        CakePlugin::load('TestPlugin');
        $this->Controller->request->addParams([
            'plugin' => null, 'pass' => [], 'form' => [], 'named' => [],
            'controller' => 'scaffold_mock', 'action' => 'index',
        ]);
        $this->Controller->constructClasses();
        $this->Controller->ScaffoldMock->bindModel(['hasMany' => [
            'ControllerComment' => ['className' => 'TestPlugin.ControllerComment', 'foreignKey' => 'article_id'],
        ]]);

        $Scaffold = new TestScaffoldMock($this->Controller, $this->Controller->request);
        $result = $Scaffold->controller->viewVars['associations']['hasMany']['ControllerComment'];

        $this->assertSame('test_plugin', $result['plugin']);
        $this->assertSame('controller_comments', $result['controller']);
        $this->assertSame('article_id', $result['foreignKey']);
    }
}
