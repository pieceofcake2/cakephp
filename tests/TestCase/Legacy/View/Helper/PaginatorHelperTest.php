<?php
/**
 * PaginatorHelperTest file
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
 * @package       Cake.Test.Case.View.Helper
 * @since         CakePHP(tm) v 1.2.0.4206
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Test\TestCase\Legacy\View\Helper;

use Cake\Core\Configure;
use Cake\Error\CakeException;
use Cake\Network\CakeRequest;
use Cake\Routing\Router;
use Cake\TestSuite\CakeTestCase;
use Cake\View\Helper\HtmlHelper;
use Cake\View\Helper\PaginatorHelper;
use Cake\View\View;

/**
 * PaginatorHelperTest class
 *
 * @package       Cake.Test.Case.View.Helper
 */
class PaginatorHelperTest extends CakeTestCase
{
    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        Configure::write('Config.language', 'eng');
        $controller = null;
        $this->View = new View($controller);
        $this->Paginator = new PaginatorHelper($this->View);
        $this->Paginator->Js = $this->getMock('PaginatorHelper', [], [$this->View]);
        $this->Paginator->request = new CakeRequest(null, false);
        $this->Paginator->request->addParams([
            'paging' => [
                'Article' => [
                    'page' => 2,
                    'current' => 9,
                    'count' => 62,
                    'prevPage' => false,
                    'nextPage' => true,
                    'pageCount' => 7,
                    'order' => null,
                    'limit' => 20,
                    'options' => [
                        'page' => 1,
                        'conditions' => [],
                    ],
                    'paramType' => 'named',
                ],
            ],
        ]);
        $this->Paginator->Html = new HtmlHelper($this->View);

        Configure::write('Routing.prefixes', []);
        Router::reload();
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->View, $this->Paginator);

        parent::tearDown();
    }

    /**
     * test that mock classes injected into paginatorHelper are called when using link()
     *
     * @return void
     */
    public function testMockAjaxProviderClassInjection(): void
    {
        $this->expectException(CakeException::class);
        $mock = $this->getMock('PaginatorHelper', [], [$this->View], 'PaginatorMockJsHelper');
        $Paginator = new PaginatorHelper($this->View, ['ajax' => 'PaginatorMockJs']);
        $Paginator->request->params['paging'] = [
            'Article' => [
                'current' => 9,
                'count' => 62,
                'prevPage' => false,
                'nextPage' => true,
                'pageCount' => 7,
                'defaults' => [],
                'options' => [],
                'paramType' => 'named',
            ],
        ];
        $Paginator->PaginatorMockJs = $mock;
        $Paginator->PaginatorMockJs->expects($this->once())->method('link');
        $Paginator->link('Page 2', ['page' => 2], ['update' => '#content']);

        new PaginatorHelper($this->View, ['ajax' => 'Form']);
    }
}
