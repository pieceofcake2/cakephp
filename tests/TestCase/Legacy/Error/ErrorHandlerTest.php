<?php
/**
 * ErrorHandlerTest file
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
 * @package       Cake.Test.Case.Error
 * @since         CakePHP(tm) v 1.2.0.5432
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Test\TestCase\Legacy\Error;

use Cake\Core\App;
use Cake\Core\CakePlugin;
use Cake\Core\Configure;
use Cake\Error\ErrorHandler;
use Cake\Error\FatalErrorException;
use Cake\Error\NotFoundException;
use Cake\Log\CakeLog;
use Cake\Network\CakeRequest;
use Cake\Routing\Router;
use Cake\TestSuite\CakeTestCase;
use Exception;

/**
 * ErrorHandlerTest class
 *
 * @package       Cake.Test.Case.Error
 */
class ErrorHandlerTest extends CakeTestCase
{
    protected $_restoreError = false;

    /**
     * setup create a request object to get out of router later.
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        App::build([
            'View' => [
                CORE_TESTS . DS . 'test_legacy_app' . DS . 'View' . DS,
            ],
        ], App::RESET);
        Router::reload();

        $request = new CakeRequest(null, false);
        $request->base = '';
        Router::setRequestInfo($request);
        Configure::write('debug', 2);

        if (CakeLog::stream('stdout') !== false) {
            CakeLog::disable('stdout');
        }
        if (CakeLog::stream('stderr') !== false) {
            CakeLog::disable('stderr');
        }
    }

    /**
     * tearDown
     *
     * @return void
     */
    public function tearDown(): void
    {
        if ($this->_restoreError) {
            restore_error_handler();
        }

        if (CakeLog::stream('stdout') !== false) {
            CakeLog::enable('stdout');
        }
        if (CakeLog::stream('stderr') !== false) {
            CakeLog::enable('stderr');
        }

        parent::tearDown();
    }

    /**
     * test handleException generating a page.
     *
     * @return void
     */
    public function testHandleException(): void
    {
        $error = new NotFoundException('Kaboom!');
        ob_start();
        ErrorHandler::handleException($error);
        $result = ob_get_clean();
        $this->assertMatchesRegularExpression('/Kaboom!/', $result, 'message missing.');
    }

    /**
     * tests it is possible to load a plugin exception renderer
     *
     * @return void
     */
    public function testLoadPluginHandler(): void
    {
        App::build([
            'Plugin' => [
                CORE_TESTS . DS . 'test_legacy_app' . DS . 'Plugin' . DS,
            ],
        ], App::RESET);
        CakePlugin::load('TestLegacyPlugin');
        Configure::write('Exception.renderer', 'TestLegacyPlugin.TestPluginExceptionRenderer');
        $error = new NotFoundException('Kaboom!');
        ob_start();
        ErrorHandler::handleException($error);
        $result = ob_get_clean();
        $this->assertEquals('Rendered by test plugin', $result);
        CakePlugin::unload();
    }

    /**
     * testExceptionRendererNestingDebug method
     *
     * @return void
     */
    public function testExceptionRendererNestingDebug(): void
    {
        App::build([
            'Lib' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Lib' . DS],
        ]);
        Configure::write('debug', 2);
        Configure::write('Exception.renderer', 'FaultyExceptionRenderer');

        $result = false;
        try {
            ob_start();
            ob_start();
            ErrorHandler::handleFatalError(E_USER_ERROR, 'Initial error', __FILE__, __LINE__);
        } catch (Exception $e) {
            $result = $e instanceof FatalErrorException;
        }

        restore_error_handler();
        $this->assertTrue($result);
    }
}
