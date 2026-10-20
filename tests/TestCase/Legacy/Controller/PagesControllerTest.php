<?php
/**
 * PagesControllerTest file
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

use Cake\Core\App;
use Cake\Network\CakeRequest;
use Cake\Network\CakeResponse;
use Cake\TestSuite\CakeTestCase;
use PagesController;

App::uses('PagesController', 'Controller');

/**
 * PagesControllerTest class
 *
 * @package       Cake.Test.Case.Controller
 */
class PagesControllerTest extends CakeTestCase
{
    /**
     * testDisplay method
     *
     * @return void
     */
    public function testDisplay(): void
    {
        App::build([
            'Controller' => [
                CORE_TESTS . DS . 'test_legacy_app' . DS . 'Controller' . DS,
            ],
            'View' => [
                CORE_TESTS . DS . 'test_legacy_app' . DS . 'View' . DS,
            ],
        ]);
        $Pages = new PagesController(new CakeRequest(null, false), new CakeResponse());

        $Pages->viewPath = 'Posts';
        $Pages->display('index');
        $this->assertMatchesRegularExpression('/posts index/', $Pages->response->body());
        $this->assertEquals('index', $Pages->viewVars['page']);

        $Pages->viewPath = 'Themed';
        $Pages->display('TestTheme', 'Posts', 'index');
        $this->assertMatchesRegularExpression('/posts index themed view/', $Pages->response->body());
        $this->assertEquals('TestTheme', $Pages->viewVars['page']);
        $this->assertEquals('Posts', $Pages->viewVars['subpage']);
    }
}
