<?php
/**
 * JsonViewTest file
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
 * @since         CakePHP(tm) v 2.1.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Test\TestCase\Legacy\View;

use Cake\Controller\Controller;
use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Network\CakeRequest;
use Cake\Network\CakeResponse;
use Cake\TestSuite\CakeTestCase;
use Cake\View\JsonView;

/**
 * JsonViewTest
 *
 * @package       Cake.Test.Case.View
 */
class JsonViewTest extends CakeTestCase
{
    /**
     * setUp method
     *
     * @return void
     **/

    public function setUp(): void
    {
        parent::setUp();
        Configure::write('debug', 0);
    }

    /**
     * Test render with a View file specified.
     *
     * @return void
     */
    public function testRenderWithView(): void
    {
        App::build([
            'View' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'View' . DS],
        ]);
        $Request = new CakeRequest();
        $Response = new CakeResponse();
        $Controller = new Controller($Request, $Response);
        $Controller->name = $Controller->viewPath = 'Posts';

        $data = [
            'User' => [
                'username' => 'fake',
            ],
            'Item' => [
                ['name' => 'item1'],
                ['name' => 'item2'],
            ],
        ];
        $Controller->set('user', $data);
        $View = new JsonView($Controller);
        $output = $View->render('index');

        $expected = json_encode(['user' => 'fake', 'list' => ['item1', 'item2'], 'paging' => null]);
        $this->assertSame($expected, $output);
        $this->assertSame('application/json', $Response->type());
    }

    /**
     * Test render with a View file specified and named parameters.
     *
     * @return void
     */
    public function testRenderWithViewAndNamed(): void
    {
        App::build([
            'View' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'View' . DS],
        ]);
        $Request = new CakeRequest(null, false);
        $Request->params['named'] = ['page' => 2];
        $Response = new CakeResponse();
        $Controller = new Controller($Request, $Response);
        $Controller->name = $Controller->viewPath = 'Posts';

        $data = [
            'User' => [
                'username' => 'fake',
            ],
            'Item' => [
                ['name' => 'item1'],
                ['name' => 'item2'],
            ],
        ];
        $Controller->set('user', $data);
        $Controller->helpers = ['Paginator'];
        $View = new JsonView($Controller);
        $output = $View->render('index');

        $expected = ['user' => 'fake', 'list' => ['item1', 'item2'], 'paging' => ['page' => 2]];
        $this->assertSame(json_encode($expected), $output);
        $this->assertSame('application/json', $Response->type());

        $View->request->query = ['jsonCallback' => 'jfunc'];
        $Controller->set('_jsonp', 'jsonCallback');
        $View = new JsonView($Controller);
        $View->helpers = ['Paginator'];
        $output = $View->render('index');
        $expected['paging']['?']['jsonCallback'] = 'jfunc';
        $expected = 'jfunc(' . json_encode($expected) . ')';
        $this->assertSame($expected, $output);
        $this->assertSame('application/javascript', $Response->type());
    }
}
