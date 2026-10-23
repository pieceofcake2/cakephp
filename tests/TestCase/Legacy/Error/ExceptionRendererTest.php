<?php
/**
 * ExceptionRendererTest file
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
 * @since         CakePHP(tm) v 2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Test\TestCase\Legacy\Error;

use Cake\Core\App;
use Cake\Core\CakePlugin;
use Cake\Core\Configure;
use Cake\Error\ExceptionRenderer;
use Cake\Error\MissingActionException;
use Cake\Error\MissingComponentException;
use Cake\Error\MissingControllerException;
use Cake\Error\MissingHelperException;
use Cake\Error\MissingLayoutException;
use Cake\Error\MissingPluginException;
use Cake\Error\MissingViewException;
use Cake\Error\NotFoundException;
use Cake\Error\SocketException;
use Cake\Model\Datasource\CakeSession;
use Cake\Network\CakeRequest;
use Cake\Routing\Router;
use Cake\TestSuite\CakeTestCase;
use TestAppsExceptionRenderer;

/**
 * ExceptionRendererTest class
 *
 * @package       Cake.Test.Case.Error
 */
class ExceptionRendererTest extends CakeTestCase
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
        Configure::write('Config.language', 'eng');
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
        if (CakeSession::started()) {
            session_write_close();
        }

        parent::tearDown();
    }

    /**
     * Mocks out the response on the ExceptionRenderer object so headers aren't modified.
     *
     * @return void
     */
    protected function _mockResponse($error): mixed
    {
        $error->controller->response = $this->getMock('CakeResponse', ['_sendHeader']);

        return $error;
    }

    /**
     * test that helpers in custom CakeErrorController are not lost
     *
     * @return void
     */
    public function testCakeErrorHelpersNotLost(): void
    {
        $testApp = CORE_TESTS . DS . 'test_legacy_app' . DS;
        App::build([
            'Controller' => [
                $testApp . 'Controller' . DS,
            ],
            'View/Helper' => [
                $testApp . 'View' . DS . 'Helper' . DS,
            ],
            'View/Layouts' => [
                $testApp . 'View' . DS . 'Layouts' . DS,
            ],
            'Error' => [
                $testApp . 'Error' . DS,
            ],
        ], App::RESET);

        $exception = new SocketException('socket exception');
        App::uses('TestAppsExceptionRenderer', 'Error');
        $renderer = new TestAppsExceptionRenderer($exception);

        ob_start();
        $renderer->render();
        $result = ob_get_clean();
        $this->assertStringContainsString('<b>peeled</b>', $result);
    }

    /**
     * Test that missing plugin doesn't disable Controller::$plugin if the two aren't the same plugin.
     *
     * @return void
     */
    public function testMissingPluginRenderSafeWithPlugin(): void
    {
        App::build([
            'Plugin' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Plugin' . DS],
        ], App::RESET);
        CakePlugin::load('TestLegacyPlugin');
        $exception = new NotFoundException();
        $ExceptionRenderer = new ExceptionRenderer($exception);

        $ExceptionRenderer->controller = $this->getMock('Controller', ['render']);
        $ExceptionRenderer->controller->plugin = 'TestLegacyPlugin';
        $ExceptionRenderer->controller->request = $this->getMock('CakeRequest');

        $exception = new MissingPluginException(['plugin' => 'TestTwoLegacyPlugin']);
        $ExceptionRenderer->controller->expects($this->once())
            ->method('render')
            ->with('error400')
            ->will($this->throwException($exception));

        $response = $this->getMock('CakeResponse');
        $response->expects($this->once())
            ->method('body')
            ->with($this->logicalAnd(
                $this->stringContains('test plugin error500'),
                $this->stringContains('Not Found'),
            ));

        $ExceptionRenderer->controller->response = $response;
        $ExceptionRenderer->render();
        CakePlugin::unload();
    }

    /**
     * Renders an exception and returns the output.
     *
     * @param Throwable $exception The exception to render.
     * @return string
     */
    protected function _render($exception): mixed
    {
        $ExceptionRenderer = $this->_mockResponse(new ExceptionRenderer($exception));

        ob_start();
        $ExceptionRenderer->render();

        return ob_get_clean();
    }

    /**
     * Returns the suggested code of an error page as text.
     *
     * @param string $result The rendered error page.
     * @return string
     */
    protected function _suggestedCode($result): string
    {
        $this->assertSame(1, preg_match('/<div class="code-dump">(.*?)<\/div>/s', $result, $match));
        // highlight_string() uses <br /> and &nbsp; before PHP 8.3
        $text = html_entity_decode(strip_tags(str_replace('<br />', "\n", $match[1])), ENT_QUOTES, 'UTF-8');

        return str_replace("\u{a0}", ' ', $text);
    }

    /**
     * Test that the missing template pages suggest the files of the View/ directory in the CakePHP 2 layout.
     *
     * @return void
     */
    public function testDevErrorSuggestedTemplateFile(): void
    {
        $view = CORE_TESTS . DS . 'test_legacy_app' . DS . 'View' . DS;

        $result = $this->_render(new MissingViewException(['file' => 'Pages' . DS . 'nope.ctp']));
        $this->assertStringContainsString('Confirm you have created the file: "Pages' . DS . 'nope.ctp"', $result);
        $this->assertStringContainsString('<li>' . h($view . 'Pages' . DS . 'nope.ctp') . '</li>', $result);
        $this->assertStringNotContainsString('nope.php</li>', $result);
        $this->assertStringContainsString('create <em>' . h($view . 'Errors' . DS . 'missing_view.ctp') . '</em>', $result);

        $result = $this->_render(new MissingLayoutException(['file' => 'Layouts' . DS . 'nope.ctp']));
        $this->assertStringContainsString('Confirm you have created the file: Layouts' . DS . 'nope.ctp', $result);
        $this->assertStringContainsString('<li>' . h($view . 'Layouts' . DS . 'nope.ctp') . '</li>', $result);
        $this->assertStringContainsString('create <em>' . h($view . 'Errors' . DS . 'missing_layout.ctp') . '</em>', $result);
    }

    /**
     * Test that the missing class pages suggest global classes without App.namespace.
     *
     * @return void
     */
    public function testDevErrorSuggestedGlobalCode(): void
    {
        $result = $this->_render(new MissingControllerException(['class' => 'NopeController']));
        $this->assertSame("<?php\n\nclass NopeController extends AppController\n{\n}", trim($this->_suggestedCode($result)));
        $this->assertStringContainsString('below in file: ' . h(substr(App::path('Controller')[0], strlen(ROOT . DS)) . 'NopeController.php'), $result);

        $result = $this->_render(new MissingControllerException(['class' => 'NopeController', 'plugin' => 'Blog']));
        $this->assertSame("<?php\n\nclass NopeController extends BlogAppController\n{\n}", trim($this->_suggestedCode($result)));
        $pluginDir = substr(App::path('Plugin')[0], strlen(ROOT . DS));
        $this->assertStringContainsString('below in file: ' . h($pluginDir . 'Blog' . DS . 'Controller' . DS . 'NopeController.php'), $result);
        $this->assertStringContainsString('Make sure your plugin <em>Blog</em> is in the ' . h($pluginDir) . ' directory', $result);

        $result = $this->_render(new MissingActionException(['controller' => 'PostsController', 'action' => 'nope']));
        $this->assertSame("<?php\n\nclass PostsController extends AppController\n{\n    public function nope()\n    {\n    }\n}", trim($this->_suggestedCode($result)));

        $result = $this->_render(new MissingComponentException(['class' => 'NopeComponent', 'plugin' => null]));
        $this->assertSame("<?php\n\nclass NopeComponent extends Component\n{\n}", trim($this->_suggestedCode($result)));

        $result = $this->_render(new MissingHelperException(['class' => 'NopeHelper', 'plugin' => null]));
        $this->assertSame("<?php\n\nclass NopeHelper extends AppHelper\n{\n}", trim($this->_suggestedCode($result)));
    }

    /**
     * Test that error templates of the application keep the layout of the application.
     *
     * @return void
     */
    public function testAppErrorTemplateKeepsLayout(): void
    {
        $result = $this->_render(new NotFoundException('Custom message'));
        $this->assertStringContainsString('<h2>Custom message</h2>', $result);
        $this->assertStringContainsString('CakePHP: the rapid development php framework', $result);
        $this->assertStringContainsString('class="cake-stack-trace"', $result);
        $this->assertStringNotContainsString('header-title', $result);
    }

    /**
     * Test that a dev_error layout in the theme overrides the core one.
     *
     * @return void
     */
    public function testThemeDevErrorLayoutOverride(): void
    {
        $ExceptionRenderer = $this->_mockResponse(new ExceptionRenderer(new MissingControllerException(['class' => 'PostsController'])));
        $ExceptionRenderer->controller->theme = 'TestTheme';

        ob_start();
        $ExceptionRenderer->render();
        $result = ob_get_clean();

        $this->assertStringContainsString('test_theme dev_error layout', $result);
        $this->assertStringContainsString('Missing Controller', $result);
        $this->assertStringNotContainsString('header-title', $result);
    }
}
