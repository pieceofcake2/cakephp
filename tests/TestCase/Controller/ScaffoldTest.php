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

use Cake\Controller\Controller;
use Cake\Controller\Scaffold;
use Cake\Core\App;
use Cake\Core\CakePlugin;
use Cake\Core\Configure;
use Cake\Model\Datasource\CakeSession;
use Cake\Network\CakeRequest;
use Cake\Network\CakeResponse;
use Cake\Routing\Router;
use Cake\TestSuite\CakeTestCase;
use Cake\Utility\ClassRegistry;
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
}
