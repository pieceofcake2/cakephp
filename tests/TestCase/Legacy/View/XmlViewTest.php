<?php
/**
 * XmlViewTest file
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
use Cake\Utility\Xml;
use Cake\View\XmlView;

/**
 * XmlViewTest
 *
 * @package       Cake.Test.Case.View
 */
class XmlViewTest extends CakeTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        Configure::write('debug', 0);
    }

    /**
     * testRenderWithView method
     *
     * @return void
     */
    public function testRenderWithView(): void
    {
        App::build(['View' => [
            CORE_TESTS . DS . 'test_legacy_app' . DS . 'View' . DS,
        ]]);
        $Request = new CakeRequest();
        $Response = new CakeResponse();
        $Controller = new Controller($Request, $Response);
        $Controller->name = $Controller->viewPath = 'Posts';

        $data = [
            [
                'User' => [
                    'username' => 'user1',
                ],
            ],
            [
                'User' => [
                    'username' => 'user2',
                ],
            ],
        ];
        $Controller->set('users', $data);
        $View = new XmlView($Controller);
        $output = $View->render('index');

        $expected = [
            'users' => ['user' => ['user1', 'user2']],
        ];
        $expected = Xml::build($expected)->asXML();
        $this->assertSame($expected, $output);
        $this->assertSame('application/xml', $Response->type());
        $this->assertInstanceOf('HelperCollection', $View->Helpers);
    }
}
