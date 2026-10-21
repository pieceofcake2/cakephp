<?php
/**
 * RequestHandlerComponentTest file
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
 * @since         CakePHP(tm) v 1.2.0.5435
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Test\TestCase\Legacy\Controller\Component;

use Cake\Controller\Component\RequestHandlerComponent;
use Cake\Core\App;
use Cake\Network\CakeRequest;
use Cake\Network\CakeResponse;
use Cake\Routing\Router;
use Cake\TestSuite\CakeTestCase;
use RequestHandlerTestController;

App::uses('RequestHandlerTestController', 'Controller');

/**
 * RequestHandlerComponentTest class
 *
 * @package       Cake.Test.Case.Controller.Component
 */
class RequestHandlerComponentTest extends CakeTestCase
{
    /**
     * Controller property
     *
     * @var RequestHandlerTestController
     */
    public $Controller;

    /**
     * RequestHandler property
     *
     * @var RequestHandlerComponent
     */
    public $RequestHandler;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        App::build([
            'Controller' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Controller' . DS],
            'View' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'View' . DS],
        ]);
        $this->_init();
    }

    /**
     * init method
     *
     * @return void
     */
    protected function _init(): void
    {
        $request = new CakeRequest('controller_posts/index');
        $response = new CakeResponse();
        $this->Controller = new RequestHandlerTestController($request, $response);
        $this->Controller->constructClasses();
        $this->RequestHandler = new RequestHandlerComponent($this->Controller->Components);
        $this->_extensions = Router::extensions();
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->RequestHandler, $this->Controller);
        if (!headers_sent()) {
            header('Content-type: text/html'); //reset content type.
        }
        call_user_func_array('Router::parseExtensions', $this->_extensions);

        parent::tearDown();
    }

    /**
     * testViewClassMap method
     *
     * @return void
     */
    public function testViewClassMap(): void
    {
        $this->RequestHandler->settings = ['viewClassMap' => ['json' => 'CustomJson']];
        $this->RequestHandler->initialize($this->Controller);
        $result = $this->RequestHandler->viewClassMap();
        $expected = [
            'json' => 'CustomJson',
            'xml' => 'Xml',
        ];
        $this->assertEquals($expected, $result);

        $result = $this->RequestHandler->viewClassMap('xls', 'Excel.Excel');
        $expected = [
            'json' => 'CustomJson',
            'xml' => 'Xml',
            'xls' => 'Excel.Excel',
        ];
        $this->assertEquals($expected, $result);

        $this->RequestHandler->renderAs($this->Controller, 'json');
        $this->assertEquals('CustomJson', $this->Controller->viewClass);
    }

    /**
     * test that ajax requests involving redirects trigger requestAction instead.
     *
     * @return void
     */
    public function testAjaxRedirectAsRequestAction(): void
    {
        App::build([
            'View' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'View' . DS],
        ], App::RESET);

        $this->Controller->RequestHandler = $this->getMock('RequestHandlerComponent', ['_stop'], [&$this->Controller->Components]);
        $this->Controller->request = $this->getMock('CakeRequest');
        $this->Controller->response = $this->getMock('CakeResponse', ['_sendHeader']);
        $this->Controller->RequestHandler->request = $this->Controller->request;
        $this->Controller->RequestHandler->response = $this->Controller->response;
        $this->Controller->request->expects($this->any())->method('is')->will($this->returnValue(true));
        $this->Controller->RequestHandler->expects($this->once())->method('_stop');

        ob_start();
        $this->Controller->RequestHandler->beforeRedirect(
            $this->Controller,
            ['controller' => 'request_handler_test', 'action' => 'destination'],
        );
        $result = ob_get_clean();
        $this->assertMatchesRegularExpression('/posts index/', $result, 'RequestAction redirect failed.');

        App::build();
    }

    /**
     * test that ajax requests involving redirects don't force no layout
     * this would cause the ajax layout to not be rendered.
     *
     * @return void
     */
    public function testAjaxRedirectAsRequestActionStillRenderingLayout(): void
    {
        App::build([
            'View' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'View' . DS],
        ], App::RESET);

        $this->Controller->RequestHandler = $this->getMock('RequestHandlerComponent', ['_stop'], [&$this->Controller->Components]);
        $this->Controller->request = $this->getMock('CakeRequest');
        $this->Controller->response = $this->getMock('CakeResponse', ['_sendHeader']);
        $this->Controller->RequestHandler->request = $this->Controller->request;
        $this->Controller->RequestHandler->response = $this->Controller->response;
        $this->Controller->request->expects($this->any())->method('is')->will($this->returnValue(true));
        $this->Controller->RequestHandler->expects($this->once())->method('_stop');

        ob_start();
        $this->Controller->RequestHandler->beforeRedirect(
            $this->Controller,
            ['controller' => 'request_handler_test', 'action' => 'ajax2_layout'],
        );
        $result = ob_get_clean();
        $this->assertMatchesRegularExpression('/posts index/', $result, 'RequestAction redirect failed.');
        $this->assertMatchesRegularExpression('/Ajax!/', $result, 'Layout was not rendered.');

        App::build();
    }
}
