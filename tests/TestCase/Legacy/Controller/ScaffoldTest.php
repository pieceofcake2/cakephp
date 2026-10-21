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

namespace Cake\Test\TestCase\Legacy\Controller;

use Cake\Controller\Controller;
use Cake\Controller\Scaffold;
use Cake\Core\App;
use Cake\Core\CakePlugin;
use Cake\Core\Configure;
use Cake\Model\Datasource\CakeSession;
use Cake\Network\CakeRequest;
use Cake\Routing\Router;
use Cake\TestSuite\CakeTestCase;

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
     * @var mixed
     */
    public $scaffold;
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
     * @var CakeRequest
     */
    protected $_params;

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
     * @return unknown
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
     * @var SecurityTestController
     */
    public $Controller;

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
        App::build([
            'Model' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Model' . DS],
        ]);
        Configure::write('Config.language', 'eng');
        $request = new CakeRequest(null, false);
        $this->Controller = new ScaffoldMockController($request);
        $this->Controller->response = $this->getMock('CakeResponse', ['_sendHeader']);
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
}
