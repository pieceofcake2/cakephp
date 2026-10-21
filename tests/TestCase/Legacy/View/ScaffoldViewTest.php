<?php
/**
 * ScaffoldViewTest file
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
 * @package       Cake.Test.Case.View
 * @since         CakePHP(tm) v 2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Test\TestCase\Legacy\View;

use Cake\Controller\Controller;
use Cake\Controller\Scaffold;
use Cake\Core\App;
use Cake\Core\CakePlugin;
use Cake\Core\Configure;
use Cake\Network\CakeRequest;
use Cake\Routing\Router;
use Cake\TestSuite\CakeTestCase;
use Cake\View\ScaffoldView;

/**
 * TestScaffoldView class
 *
 * @package       Cake.Test.Case.Controller
 */
class TestScaffoldView extends ScaffoldView
{
    /**
     * testGetFilename method
     *
     * @param string $action
     * @return void
     */
    public function testGetFilename($action): mixed
    {
        return $this->_getViewFileName($action);
    }
}

/**
 * ScaffoldViewMockController class
 *
 * @package       Cake.Test.Case.Controller
 */
class ScaffoldViewMockController extends Controller
{
    /**
     * name property
     *
     * @var string
     */
    public $name = 'ScaffoldMock';

    /**
     * scaffold property
     *
     * @var mixed
     */
    public $scaffold;
}

/**
 * ScaffoldViewTest class
 *
 * @package       Cake.Test.Case.Controller
 */
class ScaffoldViewTest extends CakeTestCase
{
    /**
     * fixtures property
     *
     * @var array
     */
    public $fixtures = ['core.article', 'core.user', 'core.comment', 'core.join_thing', 'core.tag'];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->request = new CakeRequest(null, false);
        $this->Controller = new ScaffoldViewMockController($this->request);
        $this->Controller->response = $this->getMock('CakeResponse', ['_sendHeader']);

        App::build([
            'Model' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Model' . DS],
            'View' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'View' . DS],
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
        unset($this->Controller, $this->request);

        parent::tearDown();
    }

    /**
     * testGetViewFilename method
     *
     * @return void
     */
    public function testGetViewFilename(): void
    {
        $_admin = Configure::read('Routing.prefixes');
        Configure::write('Routing.prefixes', ['admin']);

        $this->Controller->request->params['action'] = 'index';
        $ScaffoldView = new TestScaffoldView($this->Controller);
        $result = $ScaffoldView->testGetFilename('index');
        $expected = CORE_ROOT . DS . 'templates' . DS . 'Scaffolds' . DS . 'index.ctp';
        $this->assertEquals($expected, $result);

        $result = $ScaffoldView->testGetFilename('edit');
        $expected = CORE_ROOT . DS . 'templates' . DS . 'Scaffolds' . DS . 'form.ctp';
        $this->assertEquals($expected, $result);

        $result = $ScaffoldView->testGetFilename('add');
        $expected = CORE_ROOT . DS . 'templates' . DS . 'Scaffolds' . DS . 'form.ctp';
        $this->assertEquals($expected, $result);

        $result = $ScaffoldView->testGetFilename('view');
        $expected = CORE_ROOT . DS . 'templates' . DS . 'Scaffolds' . DS . 'view.ctp';
        $this->assertEquals($expected, $result);

        $result = $ScaffoldView->testGetFilename('admin_index');
        $expected = CORE_ROOT . DS . 'templates' . DS . 'Scaffolds' . DS . 'index.ctp';
        $this->assertEquals($expected, $result);

        $result = $ScaffoldView->testGetFilename('admin_view');
        $expected = CORE_ROOT . DS . 'templates' . DS . 'Scaffolds' . DS . 'view.ctp';
        $this->assertEquals($expected, $result);

        $result = $ScaffoldView->testGetFilename('admin_edit');
        $expected = CORE_ROOT . DS . 'templates' . DS . 'Scaffolds' . DS . 'form.ctp';
        $this->assertEquals($expected, $result);

        $result = $ScaffoldView->testGetFilename('admin_add');
        $expected = CORE_ROOT . DS . 'templates' . DS . 'Scaffolds' . DS . 'form.ctp';
        $this->assertEquals($expected, $result);

        $result = $ScaffoldView->testGetFilename('error');
        $expected = CORE_ROOT . DS . 'templates' . DS . 'Errors' . DS . 'scaffold_error.ctp';
        $this->assertEquals($expected, $result);

        $Controller = new ScaffoldViewMockController($this->request);
        $Controller->scaffold = 'admin';
        $Controller->viewPath = 'Posts';
        $Controller->request['action'] = 'admin_edit';

        $ScaffoldView = new TestScaffoldView($Controller);
        $result = $ScaffoldView->testGetFilename('admin_edit');
        $expected = CORE_TESTS . DS . 'test_legacy_app' . DS . 'View' . DS . 'Posts' . DS . 'scaffold.form.ctp';
        $this->assertEquals($expected, $result);

        $result = $ScaffoldView->testGetFilename('edit');
        $expected = CORE_TESTS . DS . 'test_legacy_app' . DS . 'View' . DS . 'Posts' . DS . 'scaffold.form.ctp';
        $this->assertEquals($expected, $result);

        $Controller = new ScaffoldViewMockController($this->request);
        $Controller->scaffold = 'admin';
        $Controller->viewPath = 'Tests';
        $Controller->request->addParams([
            'plugin' => 'test_legacy_plugin',
            'action' => 'admin_add',
            'admin' => true,
        ]);
        $Controller->plugin = 'TestLegacyPlugin';

        $ScaffoldView = new TestScaffoldView($Controller);
        $result = $ScaffoldView->testGetFilename('admin_add');
        $expected = CORE_TESTS . DS . 'test_legacy_app' . DS . 'Plugin' .
            DS . 'TestLegacyPlugin' . DS . 'View' . DS . 'Tests' . DS . 'scaffold.form.ctp';
        $this->assertEquals($expected, $result);

        $result = $ScaffoldView->testGetFilename('add');
        $expected = CORE_TESTS . DS . 'test_legacy_app' . DS . 'Plugin' .
            DS . 'TestLegacyPlugin' . DS . 'View' . DS . 'Tests' . DS . 'scaffold.form.ctp';
        $this->assertEquals($expected, $result);

        Configure::write('Routing.prefixes', $_admin);
    }

    /**
     * test getting the view file name for themed scaffolds.
     *
     * @return void
     */
    public function testGetViewFileNameWithTheme(): void
    {
        $this->Controller->request['action'] = 'index';
        $this->Controller->viewPath = 'Posts';
        $this->Controller->theme = 'TestTheme';
        $ScaffoldView = new TestScaffoldView($this->Controller);

        $result = $ScaffoldView->testGetFilename('index');
        $expected = CORE_TESTS . DS . 'test_legacy_app' . DS . 'View' . DS .
            'Themed' . DS . 'TestTheme' . DS . 'Posts' . DS . 'scaffold.index.ctp';
        $this->assertEquals($expected, $result);
    }

    /**
     * test default index scaffold generation
     *
     * @return void
     */
    public function testIndexScaffold(): void
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

        //set router.
        Router::reload();
        Router::setRequestInfo($this->Controller->request);

        $this->Controller->constructClasses();
        ob_start();
        new Scaffold($this->Controller, $this->Controller->request);
        $this->Controller->response->send();
        $result = ob_get_clean();

        $this->assertMatchesRegularExpression('#<h2>Scaffold Mock</h2>#', $result);
        $this->assertMatchesRegularExpression('#<table cellpadding="0" cellspacing="0">#', $result);

        $this->assertMatchesRegularExpression('#<a href="/scaffold_users/view/1">1</a>#', $result); //belongsTo links
        $this->assertMatchesRegularExpression('#<li><a href="/scaffold_mock/add">New Scaffold Mock</a></li>#', $result);
        $this->assertMatchesRegularExpression('#<li><a href="/scaffold_users">List Scaffold Users</a></li>#', $result);
        $this->assertMatchesRegularExpression('#<li><a href="/scaffold_comments/add">New Comment</a></li>#', $result);
    }
}
