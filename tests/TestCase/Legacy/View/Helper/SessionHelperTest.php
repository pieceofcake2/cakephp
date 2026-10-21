<?php
/**
 * SessionHelperTest file
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

use Cake\Core\App;
use Cake\Core\CakePlugin;
use Cake\Model\Datasource\CakeSession;
use Cake\TestSuite\CakeTestCase;
use Cake\View\Helper\SessionHelper;
use Cake\View\View;

/**
 * SessionHelperTest class
 *
 * @package       Cake.Test.Case.View.Helper
 */
class SessionHelperTest extends CakeTestCase
{
    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $controller = null;
        $this->View = new View($controller);
        $this->Session = new SessionHelper($this->View);
        CakeSession::start();

        if (!CakeSession::started()) {
            CakeSession::start();
        }

        $_SESSION = [
            'test' => 'info',
            'Message' => [
                'flash' => [
                    [
                        'element' => 'default',
                        'params' => [],
                        'message' => 'This is a calling',
                    ],
                ],
                'notification' => [
                    [
                        'element' => 'session_helper',
                        'params' => ['title' => 'Notice!', 'name' => 'Alert!'],
                        'message' => 'This is a test of the emergency broadcasting system',
                    ],
                ],
                'classy' => [
                    [
                        'element' => 'default',
                        'params' => ['class' => 'positive'],
                        'message' => 'Recorded',
                    ],
                ],
                'bare' => [
                    [
                        'element' => null,
                        'message' => 'Bare message',
                        'params' => [],
                    ],
                ],
            ],
            'Deeply' => ['nested' => ['key' => 'value']],
        ];
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        $_SESSION = [];
        unset($this->View, $this->Session);
        CakeSession::destroy();
        CakePlugin::unload();

        parent::tearDown();
    }

    /**
     * test setting the element from the attrs.
     *
     * @return void
     */
    public function testFlashElementInAttrs(): void
    {
        App::build([
            'View' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'View' . DS],
        ]);
        $result = $this->Session->flash('flash', [
            'element' => 'session_helper',
            'params' => ['title' => 'Notice!', 'name' => 'Alert!'],
        ]);
        $expected = "<div id=\"notificationLayout\">\n\t<h1>Alert!</h1>\n\t<h3>Notice!</h3>\n\t<p>This is a calling</p>\n</div>";
        $this->assertTextEquals($expected, $result);
    }

    /**
     * test using elements in plugins.
     *
     * @return void
     */
    public function testFlashWithPluginElement(): void
    {
        App::build([
            'Plugin' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Plugin' . DS],
        ]);
        CakePlugin::load('TestLegacyPlugin');

        $result = $this->Session->flash('flash', [
            'element' => 'plugin_element',
            'params' => ['plugin' => 'TestLegacyPlugin'],
        ]);
        $expected = 'this is the plugin element using params[plugin]';
        $this->assertEquals($expected, $result);
    }
}
