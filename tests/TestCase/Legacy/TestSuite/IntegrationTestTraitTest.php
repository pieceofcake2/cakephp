<?php
/**
 * CakePHP(tm) Tests <https://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.Test.Case.TestSuite
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Test\TestCase\Legacy\TestSuite;

use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Error\MissingControllerException;
use Cake\Error\NotFoundException;
use Cake\TestSuite\CakeTestCase;
use Cake\TestSuite\IntegrationTestTrait;
use PHPUnit\Framework\AssertionFailedError;

/**
 * IntegrationTestTraitTest
 *
 * @package       Cake.Test.Case.TestSuite
 */
class IntegrationTestTraitTest extends CakeTestCase
{
    use IntegrationTestTrait;

    /**
     * Set up the test_app paths.
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        App::build([
            'Plugin' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Plugin' . DS],
            'Controller' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Controller' . DS],
            'View' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'View' . DS],
        ], App::RESET);
        Configure::write('debug', 2);
        Configure::write('Exception.renderer', 'ExceptionRenderer');
        // Test the core cookie defaults, not the Cookie.type and Cookie.key of the application.
        Configure::delete('Cookie.key');
        Configure::delete('Cookie.type');
    }

    /**
     * Asserts that a callable fails with an assertion failure.
     *
     * @param callable $callable The assertion to run.
     * @param string|null $contains A string the failure message must contain.
     * @return void
     */
    protected function assertAssertionFails(callable $callable, ?string $contains = null): void
    {
        try {
            $callable();
        } catch (AssertionFailedError $e) {
            if ($contains !== null) {
                $this->assertStringContainsString($contains, $e->getMessage());
            } else {
                $this->addToAssertionCount(1);
            }

            return;
        }
        $this->fail('The assertion did not fail.');
    }

    /**
     * Test a GET request rendering a view and a layout.
     *
     * @return void
     */
    public function testGetRendersViewAndLayout(): void
    {
        $this->get('/integration_tests/index?page=2&sort=title');

        $this->assertResponseOk();
        $this->assertResponseSuccess();
        $this->assertResponseCode(200);
        $this->assertNoRedirect();
        $this->assertResponseContains('<h2>Integration index</h2>');
        $this->assertResponseContains('integration INDEX', '', true);
        $this->assertResponseNotContains('Missing');
        $this->assertResponseNotContains('integration INDEX');
        $this->assertResponseNotEmpty();
        $this->assertTemplate('IntegrationTests' . DS . 'index');
        $this->assertLayout('Layouts' . DS . 'integration');
        $this->assertContentType('html');
        $this->assertContentType('text/html');
        $this->assertHeader('Content-Type', 'text/html; charset=UTF-8');
        $this->assertSame('Integration index', $this->viewVariable('title'));
        $this->assertSame(['page' => '2', 'sort' => 'title'], $this->viewVariable('query'));
        $this->assertNull($this->viewVariable('missing'));
        $this->assertInstanceOf('IntegrationTestsController', $this->_controller);
        $this->assertInstanceOf('IntegrationTestResponse', $this->_response);
        $this->assertNull($this->_exception);
    }

    /**
     * Test the template and layout assertions.
     *
     * @return void
     */
    public function testTemplateAndLayoutAssertions(): void
    {
        $this->get('/integration_tests/flash_render');
        $this->assertTemplate('index');
        $this->assertLayout('integration');

        $this->assertAssertionFails(fn() => $this->assertTemplate('add'), "Failed asserting that 'add' equals template file");
        $this->assertAssertionFails(fn() => $this->assertLayout('default'), "Failed asserting that 'default' equals layout file");

        $this->get('/integration_tests/nothing');
        $this->assertAssertionFails(fn() => $this->assertTemplate('index'), 'equals template file ``');
        $this->assertAssertionFails(fn() => $this->assertLayout('integration'), 'equals layout file ``');
    }

    /**
     * Test that errors are rendered with the exception renderer.
     *
     * @return void
     */
    public function testErrorsAreRendered(): void
    {
        $this->get('/integration_tests/not_found');

        $this->assertResponseCode(404);
        $this->assertResponseContains('Missing integration record');
        $this->assertInstanceOf(NotFoundException::class, $this->_exception);
        $this->assertInstanceOf('IntegrationTestsController', $this->_controller);

        $this->get('/no_such_controller/index');
        $this->assertResponseCode(404);
        $this->assertInstanceOf(MissingControllerException::class, $this->_exception);
        $this->assertNull($this->_controller);

        Configure::write('debug', 0);
        $this->get('/integration_tests/fail');
        $this->assertResponseCode(500);
        $this->assertResponseContains('An Internal Error Has Occurred');
        $this->assertResponseNotContains('Integration failure');
    }

    /**
     * Test that rendered flash messages are removed unless retained.
     *
     * @return void
     */
    public function testRenderedFlashMessages(): void
    {
        $this->get('/integration_tests/flash_render');

        $this->assertResponseContains('Rendered flash');
        $this->assertResponseContains('Auth message');
        $this->assertSessionNotHasKey('Message.flash');
        $this->assertAssertionFails(fn() => $this->assertFlashMessage('Rendered flash'), "is in 'flash' message");
    }
}
