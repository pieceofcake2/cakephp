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

use PHPUnit\Framework\AssertionFailedError;

App::uses('IntegrationTestTrait', 'TestSuite');

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
            'Plugin' => [CORE_TESTS . DS . 'test_app' . DS . 'Plugin' . DS],
            'Controller' => [CORE_TESTS . DS . 'test_app' . DS . 'Controller' . DS],
            'View' => [CORE_TESTS . DS . 'test_app' . DS . 'View' . DS],
        ], App::RESET);
        Configure::write('debug', 2);
        Configure::write('Exception.renderer', 'ExceptionRenderer');
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
     * Decodes the JSON response of IntegrationTestsController::request_info().
     *
     * @return array
     */
    protected function requestInfo(): array
    {
        return json_decode((string)$this->_response->body(), true);
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
     * Test a routing array URL.
     *
     * @return void
     */
    public function testArrayUrl(): void
    {
        $this->get(['controller' => 'integration_tests', 'action' => 'index', '?' => ['page' => 3]]);

        $this->assertResponseOk();
        $this->assertSame(['page' => '3'], $this->viewVariable('query'));
        $this->assertSame('/integration_tests?page=3', $this->resolveUrl([
            'controller' => 'integration_tests', 'action' => 'index', '?' => ['page' => 3],
        ]));
        $this->assertSame('/foo', $this->resolveUrl('/foo'));
    }

    /**
     * Test the request methods.
     *
     * @return void
     */
    public function testRequestMethods(): void
    {
        $this->get('/integration_tests/request_info');
        $this->assertSame('GET', $this->requestInfo()['method']);

        $this->post('/integration_tests/request_info', ['Article' => ['title' => 'Post']]);
        $this->assertSame('POST', $this->requestInfo()['method']);
        $this->assertSame(['Article' => ['title' => 'Post']], $this->requestInfo()['data']);

        $this->put('/integration_tests/request_info', ['Article' => ['title' => 'Put']]);
        $this->assertSame('PUT', $this->requestInfo()['method']);
        $this->assertSame(['Article' => ['title' => 'Put']], $this->requestInfo()['data']);

        $this->patch('/integration_tests/request_info', ['Article' => ['title' => 'Patch']]);
        $this->assertSame('PATCH', $this->requestInfo()['method']);
        $this->assertSame(['Article' => ['title' => 'Patch']], $this->requestInfo()['data']);

        $this->delete('/integration_tests/request_info');
        $this->assertSame('DELETE', $this->requestInfo()['method']);

        $this->head('/integration_tests/request_info');
        $this->assertSame('HEAD', $this->requestInfo()['method']);

        $this->options('/integration_tests/request_info');
        $this->assertSame('OPTIONS', $this->requestInfo()['method']);
    }

    /**
     * Test that the POST data is cast to strings, except uploaded files.
     *
     * @return void
     */
    public function testPostDataIsCastToString(): void
    {
        $file = ['name' => 'a.txt', 'type' => 'text/plain', 'tmp_name' => '/tmp/a', 'error' => 0, 'size' => 5];
        $this->post('/integration_tests/request_info', [
            'Article' => ['id' => 1, 'published' => true, 'draft' => false, 'rating' => 1.5, 'file' => $file],
            'data' => ['Comment' => ['body' => 'Prefixed']],
        ]);

        $this->assertSame([
            'Article' => ['id' => '1', 'published' => '1', 'draft' => '0', 'rating' => '1.5', 'file' => $file],
            'Comment' => ['body' => 'Prefixed'],
        ], $this->requestInfo()['data']);
    }

    /**
     * Test a string body and requestAsJson().
     *
     * @return void
     */
    public function testStringBodyAndRequestAsJson(): void
    {
        $this->requestAsJson();
        $this->post('/integration_tests/request_info', '{"title":"JSON"}');

        $info = $this->requestInfo();
        $this->assertSame('{"title":"JSON"}', $info['input']);
        $this->assertSame('application/json', $info['accept']);
        $this->assertSame(['title' => 'JSON'], $info['data'], 'RequestHandler parses the JSON body');

        $this->configRequest(['input' => 'raw=body']);
        $this->put('/integration_tests/request_info');
        $this->assertSame('raw=body', $this->requestInfo()['input']);
    }

    /**
     * Test configRequest() with headers, environment and files, and a full URL.
     *
     * @return void
     */
    public function testConfigRequest(): void
    {
        $files = ['upload' => ['name' => 'a.txt', 'type' => 'text/plain', 'tmp_name' => '/tmp/a', 'error' => 0, 'size' => 5]];
        $this->configRequest([
            'headers' => ['Accept' => 'text/plain'],
            'environment' => ['HTTP_REFERER' => '/from/here'],
            'files' => $files,
        ]);
        $this->configRequest(['cookies' => ['configured' => 'cookie']]);
        $this->get('https://example.com:8443/integration_tests/request_info?q=1');

        $info = $this->requestInfo();
        $this->assertSame('text/plain', $info['accept']);
        $this->assertSame('/from/here', $info['referer']);
        $this->assertSame('example.com:8443', $info['host']);
        $this->assertTrue($info['https']);
        $this->assertSame(['q' => '1'], $info['query']);
        $this->assertSame($files, $info['files']);
        $this->assertSame('cookie', $info['cookies']['configured']);

        $this->get('/integration_tests/request_info');
        $info = $this->requestInfo();
        $this->assertEmpty($info['accept'], 'Headers are used for one request only');
        $this->assertSame('/from/here', $info['referer'], 'The environment is kept');
        $this->assertFalse($info['https']);

        $this->replaceRequest([]);
        $this->get('/integration_tests/request_info');
        $this->assertEmpty($this->requestInfo()['referer']);
    }

    /**
     * Test that the superglobals are restored after a request.
     *
     * @return void
     */
    public function testGlobalsAreRestored(): void
    {
        $get = $_GET;
        $post = $_POST;
        $cookie = $_COOKIE;
        $files = $_FILES;
        $method = $_SERVER['REQUEST_METHOD'] ?? null;

        $this->cookie('name', 'value');
        $this->post('/integration_tests/request_info?x=1', ['a' => 'b']);
        $this->assertResponseOk();

        $this->assertSame($get, $_GET);
        $this->assertSame($post, $_POST);
        $this->assertSame($cookie, $_COOKIE);
        $this->assertSame($files, $_FILES);
        $this->assertSame($method, $_SERVER['REQUEST_METHOD'] ?? null);
        $this->assertNull(Router::getRequest());
    }

    /**
     * Test redirects and flash messages.
     *
     * @return void
     */
    public function testRedirectAndFlash(): void
    {
        $this->get('/integration_tests/flash_redirect');

        $this->assertResponseCode(302);
        $this->assertResponseSuccess();
        $this->assertRedirect();
        $this->assertRedirect('/integration_tests');
        $this->assertRedirect(['controller' => 'integration_tests', 'action' => 'index']);
        $this->assertRedirect(Router::url('/integration_tests', true));
        $this->assertRedirectEquals('/integration_tests');
        $this->assertRedirectContains('/integration_tests');
        $this->assertRedirectNotContains('/elsewhere');
        $this->assertHeader('Location', Router::url('/integration_tests', true));

        $this->assertFlashMessage('Saved!');
        $this->assertFlashMessage('Second message');
        $this->assertFlashMessageAt(0, 'Saved!');
        $this->assertFlashMessageAt(1, 'Second message');
        $this->assertFlashMessageContains('Save');
        $this->assertFlashMessageContains('second', 'flash', '', true);
        $this->assertFlashMessageContainsAt(1, 'Second');
        $this->assertFlashMessageContainsAt(0, 'SAVED', 'flash', '', true);
        $this->assertFlashElement('Flash/success');
        $this->assertFlashElementAt(1, 'Flash/error');
        $this->assertSession('Saved!', 'Message.flash.0.message');
        $this->assertSession(['class' => 'warn'], 'Message.flash.1.params');
    }

    /**
     * Test that a redirect stops the action like exit would.
     *
     * @return void
     */
    public function testRedirectStopsTheRequest(): void
    {
        $this->get('/integration_tests/redirect_status');

        $this->assertResponseCode(301);
        $this->assertRedirect('/integration_tests/index');
        $this->assertSessionNotHasKey('Integration.afterRedirect');
    }

    /**
     * Test assertRedirectBackToReferer().
     *
     * @return void
     */
    public function testAssertRedirectBackToReferer(): void
    {
        $this->configRequest(['environment' => ['HTTP_REFERER' => Router::url('/integration_tests/index', true)]]);
        $this->get('/integration_tests/redirect_back');

        $this->assertRedirectBackToReferer();
        $this->assertRedirectBackToReferer(302);
        $this->assertAssertionFails(fn() => $this->assertRedirectBackToReferer(301));

        $this->configRequest(['environment' => ['HTTP_REFERER' => Router::url('/elsewhere', true)]]);
        $this->get('/integration_tests/flash_redirect');
        $this->assertAssertionFails(fn() => $this->assertRedirectBackToReferer(), 'Location');

        $this->replaceRequest([]);
        $this->get('/integration_tests/flash_redirect');
        $this->assertAssertionFails(fn() => $this->assertRedirectBackToReferer(), 'No `HTTP_REFERER`');
    }

    /**
     * Test Router::redirect() routes.
     *
     * @return void
     */
    public function testRedirectRoute(): void
    {
        Router::redirect('/old_index', '/integration_tests/index', ['status' => 302]);
        Router::connect('/:controller/:action/*');

        $this->get('/old_index');

        $this->assertResponseCode(302);
        $this->assertRedirect('/integration_tests/index');
        $this->assertNull($this->_controller);
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

    /**
     * Test enableRetainFlashMessages().
     *
     * @return void
     */
    public function testEnableRetainFlashMessages(): void
    {
        $this->enableRetainFlashMessages();
        $this->get('/integration_tests/flash_render');

        $this->assertResponseContains('Rendered flash');
        $this->assertFlashMessage('Rendered flash');
        $this->assertFlashElement('Flash/default');
        $this->assertFlashMessage('Auth message', 'auth');
        $this->assertFlashMessageAt(0, 'Auth message', 'auth');
        $this->assertFlashElement('default', 'auth');
        $this->assertSessionHasKey('Message.flash');
    }

    /**
     * Test the legacy single message format of the Message session key.
     *
     * @return void
     */
    public function testFlashSingleMessageFormat(): void
    {
        $this->get('/integration_tests/index');
        $_SESSION['Message']['legacy'] = ['message' => 'Old style', 'element' => 'default', 'params' => []];

        $this->assertFlashMessage('Old style', 'legacy');
        $this->assertFlashMessageAt(0, 'Old style', 'legacy');
        $this->assertFlashElement('default', 'legacy');
        $this->assertAssertionFails(fn() => $this->assertFlashMessageAt(1, 'Old style', 'legacy'));
    }

    /**
     * Test the failures of the flash assertions.
     *
     * @return void
     */
    public function testFlashAssertionFailures(): void
    {
        $this->assertAssertionFails(fn() => $this->assertFlashMessage('Saved!'), 'There is no stored session data');

        $this->get('/integration_tests/flash_redirect');
        $this->assertAssertionFails(fn() => $this->assertFlashMessage('Nope'), "Failed asserting that 'Nope' is in 'flash' message.");
        $this->assertAssertionFails(fn() => $this->assertFlashMessage('Saved!', 'other'));
        $this->assertAssertionFails(fn() => $this->assertFlashMessageAt(1, 'Saved!'), "is in 'flash' message #1");
        $this->assertAssertionFails(fn() => $this->assertFlashMessageAt(5, 'Saved!'));
        $this->assertAssertionFails(fn() => $this->assertFlashMessageContains('saved'), "contains in 'flash' message");
        $this->assertAssertionFails(fn() => $this->assertFlashMessageContainsAt(0, 'Second'), "contains in 'flash' message #0");
        $this->assertAssertionFails(fn() => $this->assertFlashElement('Flash/default'), "is in 'flash' element");
        $this->assertAssertionFails(fn() => $this->assertFlashElementAt(0, 'Flash/error'), "is in 'flash' element #0");
    }

    /**
     * Test session().
     *
     * @return void
     */
    public function testSession(): void
    {
        $this->session(['Auth' => ['User' => ['name' => 'mark']]]);
        $this->session(['Other' => 'value']);
        $this->get('/integration_tests/session_data');

        $this->assertResponseEquals('mark');
        $this->assertSession('stored', 'Integration.written');
        $this->assertSession('value', 'Other');
        $this->assertSession(['User' => ['name' => 'mark']], 'Auth');
        $this->assertSessionHasKey('Integration.written');
        $this->assertSessionNotHasKey('Integration.missing');

        $this->assertAssertionFails(fn() => $this->assertSession('other', 'Integration.written'), "is in session path 'Integration.written'");
        $this->assertAssertionFails(fn() => $this->assertSessionHasKey('Integration.missing'), 'is a path present in the session');
        $this->assertAssertionFails(fn() => $this->assertSessionNotHasKey('Integration.written'), 'is not a path present in the session');
    }

    /**
     * Test that the session is kept between requests of a test.
     *
     * @return void
     */
    public function testSessionIsKeptBetweenRequests(): void
    {
        $this->get('/integration_tests/session_data');
        $this->assertResponseEquals('');
        $_SESSION['Auth']['User']['name'] = 'written by the test';

        $this->get('/integration_tests/session_data');
        $this->assertResponseEquals('written by the test');
    }

    /**
     * Test cleanup().
     *
     * @return void
     */
    public function testCleanup(): void
    {
        $this->enableSecurityToken();
        $this->enableCsrfToken();
        $this->enableRetainFlashMessages();
        $this->disableErrorHandlerMiddleware();
        $this->setUnlockedFields(['a']);
        $this->session(['A' => 'b']);
        $this->cookie('a', 'b');
        $this->configRequest(['environment' => ['HTTP_REFERER' => '/']]);
        $this->get('/integration_tests/index');

        $this->cleanup();

        $this->assertNull($this->_response);
        $this->assertNull($this->_controller);
        $this->assertNull($this->_viewName);
        $this->assertNull($this->_layoutName);
        $this->assertSame([], $this->_request);
        $this->assertSame([], $this->_session);
        $this->assertSame([], $this->_cookie);
        $this->assertSame([], $this->_unlockedFields);
        $this->assertFalse($this->_securityToken);
        $this->assertFalse($this->_csrfToken);
        $this->assertFalse($this->_retainFlashMessages);
        $this->assertFalse($this->_disableErrorHandler);
        $this->assertSame([], $_SESSION);
    }

    /**
     * Test the cookie assertions.
     *
     * @return void
     */
    public function testResponseCookies(): void
    {
        $this->get('/integration_tests/cookies');

        $this->assertCookie('raw value', 'raw');
        $this->assertCookieIsSet('raw');
        $this->assertCookieIsSet('CakeCookie[remember]');
        $this->assertCookieNotSet('empty');
        $this->assertCookieNotSet('missing');
        $this->assertCookie('not encrypted', 'CakeCookie[plain]');
        $this->assertCookieEncrypted('yes', 'CakeCookie[remember]');
        $this->assertCookieEncrypted(['id' => 1, 'name' => 'mark'], 'CakeCookie[user]');
        $this->assertCookieEncrypted('yes', 'CakeCookie[remember]', 'cipher', Configure::read('Security.salt'));
        $this->assertCookieEncrypted('aes value', 'CakeCookie[secret]', 'aes');
        $this->assertCookieEncrypted('not encrypted', 'CakeCookie[plain]');

        $this->assertAssertionFails(fn() => $this->assertCookie('other', 'raw'), "is in cookie 'raw'");
        $this->assertAssertionFails(fn() => $this->assertCookie('raw value', 'missing'), 'cookie is set');
        $this->assertAssertionFails(fn() => $this->assertCookieIsSet('empty'), 'cookie is set');
        $this->assertAssertionFails(fn() => $this->assertCookieNotSet('raw'), 'cookie is not set');
        $this->assertAssertionFails(fn() => $this->assertCookieEncrypted('no', 'CakeCookie[remember]'), "is encrypted in cookie 'CakeCookie[remember]'");
        $this->assertAssertionFails(fn() => $this->assertCookieEncrypted('yes', 'CakeCookie[missing]'), 'cookie is set');
        $this->assertAssertionFails(fn() => $this->assertCookieEncrypted('yes', 'CakeCookie[remember]', 'cipher', 'wrong key wrong key wrong key wrong'));
        $this->assertAssertionFails(fn() => $this->assertCookieEncrypted('aes value', 'CakeCookie[secret]', 'cipher'));

        $this->expectException(InvalidArgumentException::class);
        $this->assertCookieEncrypted('yes', 'CakeCookie[remember]', 'rot13');
    }

    /**
     * Test the request cookies.
     *
     * @return void
     */
    public function testRequestCookies(): void
    {
        $this->cookie('plain', 'value');
        $this->cookie('Nested[key]', 'nested value');
        $this->cookieEncrypted('CakeCookie[remember]', 'yes');
        $this->cookieEncrypted('CakeCookie[user]', ['id' => 2]);
        $this->get('/integration_tests/request_info');

        $info = $this->requestInfo();
        $this->assertSame('value', $info['cookies']['plain']);
        $this->assertSame(['key' => 'nested value'], $info['cookies']['Nested']);
        $this->assertStringStartsWith('Q2FrZQ==.', $info['cookies']['CakeCookie']['remember']);
        $this->assertSame('yes', $info['remember']);
        $this->assertSame(['id' => 2], $info['user']);

        $this->cookieEncrypted('CakeCookie[remember]', 'plain text', false);
        $this->get('/integration_tests/request_info');
        $this->assertSame('plain text', $this->requestInfo()['remember']);

        $this->cookieEncrypted('CakeCookie[remember]', 'wrong key', 'cipher', 'another key another key another key');
        $this->get('/integration_tests/request_info');
        $this->assertNotSame('wrong key', $this->requestInfo()['remember']);
    }

    /**
     * Test aes and rijndael encrypted request cookies.
     *
     * @return void
     */
    public function testRequestCookiesOtherCiphers(): void
    {
        $key = str_repeat('a', 32);
        foreach (['aes', 'rijndael'] as $type) {
            if ($type === 'rijndael' && !function_exists('mcrypt_encrypt')) {
                continue;
            }
            $this->_cookieEncryptionKey = null;
            $this->cookieEncrypted('CakeCookie[value]', 'secret ' . $type, $type, $key);
            $encrypted = $this->_cookie['CakeCookie[value]'];
            $this->assertSame('secret ' . $type, $this->_decrypt($encrypted, $type, $key));
        }
    }

    /**
     * Test a JSON response.
     *
     * @return void
     */
    public function testJsonResponse(): void
    {
        Configure::write('debug', 0);
        $this->get('/integration_tests/json');

        $this->assertResponseOk();
        $this->assertContentType('json');
        $this->assertContentType('application/json');
        $this->assertHeader('Content-Type', 'application/json; charset=UTF-8');
        $this->assertHeaderContains('Content-Type', 'application/json');
        $this->assertResponseEquals('{"article":{"id":1,"title":"First"}}');
        $this->assertResponseNotEquals('{}');
        $this->assertSame(['id' => 1, 'title' => 'First'], $this->viewVariable('article'));

        $this->assertAssertionFails(fn() => $this->assertContentType('html'), 'is set as the Content-Type (`application/json`)');
        $this->assertAssertionFails(fn() => $this->assertContentType('text/html'), 'is set as the Content-Type');
        $this->assertAssertionFails(fn() => $this->assertResponseEquals('{}'), 'matches response body');
        $this->assertAssertionFails(fn() => $this->assertResponseNotEquals('{"article":{"id":1,"title":"First"}}'), 'does not match response body');
    }

    /**
     * Test the header assertions.
     *
     * @return void
     */
    public function testHeaders(): void
    {
        $this->get('/integration_tests/headers');

        $this->assertResponseCode(201);
        $this->assertResponseOk();
        $this->assertHeader('X-Custom', 'custom value');
        $this->assertHeader('x-custom', 'custom value');
        $this->assertHeader('X-Multiple', 'one, two');
        $this->assertHeaderContains('X-Custom', 'custom');
        $this->assertHeaderNotContains('X-Custom', 'other');
        $this->assertNoRedirect();

        $this->assertAssertionFails(fn() => $this->assertHeader('X-Custom', 'other'), "equals content in header 'X-Custom' (`custom value`)");
        $this->assertAssertionFails(fn() => $this->assertHeader('X-Missing', 'value'), "response has header 'X-Missing'");
        $this->assertAssertionFails(fn() => $this->assertHeaderContains('X-Custom', 'other'), "is in header 'X-Custom' (`custom value`)");
        $this->assertAssertionFails(fn() => $this->assertHeaderContains('X-Missing', 'value'), "response has header 'X-Missing'");
        $this->assertAssertionFails(fn() => $this->assertHeaderNotContains('X-Custom', 'custom'), "is not in header 'X-Custom' (`custom value`)");
        $this->assertAssertionFails(fn() => $this->assertHeaderNotContains('X-Missing', 'value'), "response has header 'X-Missing'");
        $this->assertAssertionFails(fn() => $this->assertRedirect(), "response has header 'Location'");
        $this->assertAssertionFails(fn() => $this->assertRedirectEquals('/'), "response has header 'Location'");
        $this->assertAssertionFails(fn() => $this->assertRedirectContains('/'), "response has header 'Location'");
        $this->assertAssertionFails(fn() => $this->assertRedirectNotContains('/'), "response has header 'Location'");
    }

    /**
     * Test the failures of the redirect assertions.
     *
     * @return void
     */
    public function testRedirectAssertionFailures(): void
    {
        $this->get('/integration_tests/flash_redirect');

        $this->assertAssertionFails(fn() => $this->assertRedirect('/elsewhere'), "equals content in header 'Location'");
        $this->assertAssertionFails(fn() => $this->assertRedirect(['controller' => 'other']), "equals content in header 'Location'");
        $this->assertAssertionFails(fn() => $this->assertRedirectEquals('/elsewhere'), "equals content in header 'Location'");
        $this->assertAssertionFails(fn() => $this->assertRedirectContains('/elsewhere'), "is in header 'Location'");
        $this->assertAssertionFails(fn() => $this->assertRedirectNotContains('/integration'), "is not in header 'Location'");
        $this->assertAssertionFails(fn() => $this->assertNoRedirect(), 'did not have header `Location`');
    }

    /**
     * Test the status code assertions and their failures.
     *
     * @return void
     */
    public function testStatusCodeAssertions(): void
    {
        $this->get('/integration_tests/index');
        $this->assertAssertionFails(fn() => $this->assertResponseCode(404), '`404` matches response status code `200`');
        $this->assertAssertionFails(fn() => $this->assertResponseError(), '200 is between 400 and 429');
        $this->assertAssertionFails(fn() => $this->assertResponseFailure(), '200 is between 500 and 505');

        $this->get('/integration_tests/flash_redirect');
        $this->assertAssertionFails(fn() => $this->assertResponseOk(), '302 is between 200 and 204');

        $this->get('/integration_tests/not_found');
        $this->assertResponseError();
        $this->assertResponseCode(404);
        $this->assertAssertionFails(fn() => $this->assertResponseSuccess(), '404 is between 200 and 308');
        $this->assertAssertionFails(fn() => $this->assertResponseFailure(), '404 is between 500 and 505');

        $this->get('/integration_tests/fail');
        $this->assertResponseFailure();
        $this->assertResponseCode(500);
        $this->assertAssertionFails(fn() => $this->assertResponseError(), '500 is between 400 and 429');
    }

    /**
     * Test the body assertions failures.
     *
     * @return void
     */
    public function testBodyAssertionFailures(): void
    {
        $this->get('/integration_tests/index');

        $this->assertResponseRegExp('/<h2>Integration \w+<\/h2>/');
        $this->assertResponseNotRegExp('/Missing/');
        $this->assertAssertionFails(fn() => $this->assertResponseContains('Missing'), "'Missing' is in response body");
        $this->assertAssertionFails(fn() => $this->assertResponseNotContains('Integration index'), 'is not in response body');
        $this->assertAssertionFails(fn() => $this->assertResponseRegExp('/Missing/'), '`/Missing/` PCRE pattern found in response body');
        $this->assertAssertionFails(fn() => $this->assertResponseNotRegExp('/Integration/'), '`/Integration/` PCRE pattern not found in response body');
        $this->assertAssertionFails(fn() => $this->assertResponseEmpty(), 'response body is empty');

        $this->get('/integration_tests/nothing');
        $this->assertResponseEmpty();
        $this->assertResponseEquals('');
        $this->assertAssertionFails(fn() => $this->assertResponseNotEmpty(), 'response body is not empty');
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
     * Test the file assertions.
     *
     * @return void
     */
    public function testAssertFileResponse(): void
    {
        $file = CORE_TESTS . DS . 'test_app' . DS . 'Controller' . DS . 'IntegrationTestsController.php';
        $this->get('/integration_tests/file');

        $this->assertResponseOk();
        $this->assertFileResponse($file);
        $this->assertAssertionFails(fn() => $this->assertFileResponse(__FILE__), 'file was sent');

        $this->get('/integration_tests/index');
        $this->assertAssertionFails(fn() => $this->assertFileResponse($file), 'file was sent');
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
     * Test that the exception is added to the failure messages.
     *
     * @return void
     */
    public function testExceptionInFailureMessage(): void
    {
        $this->get('/integration_tests/fail');

        $this->assertAssertionFails(fn() => $this->assertResponseOk(), 'Possibly related to `RuntimeException`: "Integration failure"');
        $this->assertAssertionFails(fn() => $this->assertResponseOk('Custom'), 'Caused by `LogicException`: "Root cause"');
        $this->assertAssertionFails(fn() => $this->assertResponseContains('Nope', 'Custom message'), 'Custom message');
    }

    /**
     * Test disableErrorHandlerMiddleware().
     *
     * @return void
     */
    public function testDisableErrorHandlerMiddleware(): void
    {
        $this->disableErrorHandlerMiddleware();

        try {
            $this->get('/integration_tests/not_found');
            $this->fail('No exception thrown');
        } catch (NotFoundException $e) {
            $this->assertSame('Missing integration record', $e->getMessage());
        }
        $this->assertSame($e, $this->_exception);
        $this->assertNull($_SERVER['QUERY_STRING'] ?? null, 'Globals are restored');
        $this->assertNull(Router::getRequest());
    }

    /**
     * Test that PHPUnit exceptions are not rendered.
     *
     * @return void
     */
    public function testAssertionsInRequestsAreNotCaught(): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Thrown in the dispatcher');

        Configure::write('Dispatcher.filters', [[
            'callable' => function (): void {
                throw new AssertionFailedError('Thrown in the dispatcher');
            },
            'on' => 'before',
        ]]);
        $this->get('/integration_tests/index');
    }

    /**
     * Test that dispatcher filters run, and that a response they send is captured.
     *
     * @return void
     */
    public function testDispatcherFilterResponse(): void
    {
        Configure::write('Dispatcher.filters', [[
            'callable' => function (CakeEvent $event): CakeResponse {
                $event->stopPropagation();
                $event->data['response']->body('From the filter');

                return $event->data['response'];
            },
            'on' => 'before',
        ]]);
        $this->get('/integration_tests/index');

        $this->assertResponseEquals('From the filter');
        $this->assertNull($this->_controller);
    }

    /**
     * Test that a filter calling send() is stopped.
     *
     * @return void
     */
    public function testDispatcherFilterSendingTheResponse(): void
    {
        Configure::write('Dispatcher.filters', [[
            'callable' => function (CakeEvent $event): void {
                $event->data['response']->body('Sent by the filter');
                $event->data['response']->send();
                throw new LogicException('Not reached');
            },
            'on' => 'before',
        ]]);
        $this->get('/integration_tests/index');

        $this->assertResponseEquals('Sent by the filter');
        $this->assertNull($this->_exception);
    }

    /**
     * Test the assertions without a request.
     *
     * @return void
     */
    public function testAssertionsWithoutResponse(): void
    {
        $noResponse = 'No response set';
        $this->assertAssertionFails(fn() => $this->assertResponseOk(), $noResponse);
        $this->assertAssertionFails(fn() => $this->assertResponseSuccess(), $noResponse);
        $this->assertAssertionFails(fn() => $this->assertResponseError(), $noResponse);
        $this->assertAssertionFails(fn() => $this->assertResponseFailure(), $noResponse);
        $this->assertAssertionFails(fn() => $this->assertResponseCode(200), $noResponse);
        $this->assertAssertionFails(fn() => $this->assertResponseContains('a'), $noResponse);
        $this->assertAssertionFails(fn() => $this->assertResponseNotContains('a'), $noResponse);
        $this->assertAssertionFails(fn() => $this->assertResponseEquals('a'), $noResponse);
        $this->assertAssertionFails(fn() => $this->assertResponseNotEquals('a'), $noResponse);
        $this->assertAssertionFails(fn() => $this->assertResponseRegExp('/a/'), $noResponse);
        $this->assertAssertionFails(fn() => $this->assertResponseNotRegExp('/a/'), $noResponse);
        $this->assertAssertionFails(fn() => $this->assertResponseEmpty(), $noResponse);
        $this->assertAssertionFails(fn() => $this->assertResponseNotEmpty(), $noResponse);
        $this->assertAssertionFails(fn() => $this->assertRedirect(), $noResponse);
        $this->assertAssertionFails(fn() => $this->assertRedirectEquals(), $noResponse);
        $this->assertAssertionFails(fn() => $this->assertRedirectContains('a'), $noResponse);
        $this->assertAssertionFails(fn() => $this->assertRedirectNotContains('a'), $noResponse);
        $this->assertAssertionFails(fn() => $this->assertRedirectBackToReferer(), $noResponse);
        $this->assertAssertionFails(fn() => $this->assertNoRedirect(), $noResponse);
        $this->assertAssertionFails(fn() => $this->assertHeader('a', 'b'), $noResponse);
        $this->assertAssertionFails(fn() => $this->assertHeaderContains('a', 'b'), $noResponse);
        $this->assertAssertionFails(fn() => $this->assertHeaderNotContains('a', 'b'), $noResponse);
        $this->assertAssertionFails(fn() => $this->assertContentType('html'), $noResponse);
        $this->assertAssertionFails(fn() => $this->assertCookie('a', 'b'), $noResponse);
        $this->assertAssertionFails(fn() => $this->assertCookieIsSet('a'), $noResponse);
        $this->assertAssertionFails(fn() => $this->assertCookieNotSet('a'), $noResponse);
        $this->assertAssertionFails(fn() => $this->assertCookieEncrypted('a', 'b'), $noResponse);
        $this->assertAssertionFails(fn() => $this->assertFileResponse('a'), $noResponse);
        $this->assertAssertionFails(fn() => $this->_getBodyAsString(), $noResponse);
        $this->assertAssertionFails(fn() => $this->assertTemplate('a'), 'equals template file');
        $this->assertAssertionFails(fn() => $this->assertFlashMessageAt(0, 'a'), 'There is no stored session data');
        $this->assertAssertionFails(fn() => $this->assertFlashMessageContains('a'), 'There is no stored session data');
        $this->assertAssertionFails(fn() => $this->assertFlashMessageContainsAt(0, 'a'), 'There is no stored session data');
        $this->assertAssertionFails(fn() => $this->assertFlashElement('a'), 'There is no stored session data');
        $this->assertAssertionFails(fn() => $this->assertFlashElementAt(0, 'a'), 'There is no stored session data');
        $this->assertNull($this->viewVariable('a'));
    }

    /**
     * Test enableSecurityToken() with a SecurityComponent protected action.
     *
     * @return void
     */
    public function testEnableSecurityToken(): void
    {
        $this->get('/integration_security/add');
        $this->assertResponseOk();
        $this->assertResponseContains('name="data[_Token][key]"');

        $this->enableSecurityToken();
        $data = ['Article' => ['title' => 'Secured', 'tags' => ['a', 'b'], 'meta' => ['x' => 'y']]];
        $this->post('/integration_security/add', $data);
        $this->assertResponseOk();
        $this->assertResponseEquals(json_encode($data));

        $this->post('/integration_security/add?redirect=1', $data);
        $this->assertResponseOk('The query string is part of the hashed URL');

        $this->put('/integration_security/add', $data);
        $this->assertResponseOk();
        $this->assertResponseContains('<form');

        Configure::write('debug', 0);
        $this->post('/integration_security/add', $data);
        $this->assertResponseOk('Works without the debug token');
    }

    /**
     * Test that SecurityComponent black-holes requests without tokens.
     *
     * @return void
     */
    public function testSecurityComponentWithoutToken(): void
    {
        $this->post('/integration_security/add', ['Article' => ['title' => 'Insecure']]);
        $this->assertResponseCode(400);
        $this->assertInstanceOf(AuthSecurityException::class, $this->_exception);

        $this->enableCsrfToken();
        $this->post('/integration_security/add', ['Article' => ['title' => 'Insecure']]);
        $this->assertResponseCode(400);
        $this->assertSame("'_Token.fields' was not found in request data.", $this->_exception->getMessage());
    }

    /**
     * Test enableSecurityToken() with unlocked fields.
     *
     * @return void
     */
    public function testSecurityTokenUnlockedFields(): void
    {
        $data = ['Article' => ['title' => 'Unlocked', 'preview' => '1']];

        $this->enableSecurityToken();
        $this->post('/integration_security/unlocked', $data);
        $this->assertResponseCode(400, 'The field is unlocked in the controller only');

        $this->setUnlockedFields(['Article.preview']);
        $this->post('/integration_security/unlocked', $data);
        $this->assertResponseOk();
        $this->assertResponseEquals(json_encode($data));
    }

    /**
     * Test enableCsrfToken().
     *
     * @return void
     */
    public function testEnableCsrfToken(): void
    {
        $this->post('/integration_security/csrf_only', ['Article' => ['title' => 'No token']]);
        $this->assertResponseCode(400);
        $this->assertSame('Missing CSRF token', $this->_exception->getMessage());

        $this->enableCsrfToken();
        $this->post('/integration_security/csrf_only', ['Article' => ['title' => 'Token']]);
        $this->assertResponseOk();
        $this->assertResponseEquals(json_encode(['Article' => ['title' => 'Token']]));

        $this->post('/integration_security/csrf_only', ['Article' => ['title' => 'Again'], '_Token' => ['key' => 'forged']]);
        $this->assertResponseOk('An explicit key is added to the session');

        $this->get('/integration_security/csrf_only');
        $this->assertResponseOk();
    }

    /**
     * Test that the security tokens are not added to GET requests without data.
     *
     * @return void
     */
    public function testTokensNotAddedToGetRequests(): void
    {
        $this->enableSecurityToken();
        $this->get('/integration_tests/request_info');
        $this->assertSame([], $this->requestInfo()['data']);

        $this->post('/integration_tests/request_info');
        $this->assertSame(['_Token'], array_keys($this->requestInfo()['data']));

        $this->post('/integration_tests/request_info', '{"a":1}');
        $this->assertSame([], $this->requestInfo()['data']);
    }

    /**
     * The default cookie encryption key follows CookieComponent: Cookie.key, else Security.salt.
     *
     * @return void
     */
    public function testCookieEncryptionKeyDefault()
    {
        Configure::write('Security.salt', 'the-salt-of-this-test');
        Configure::delete('Cookie.key');
        $this->assertSame('the-salt-of-this-test', $this->_getCookieEncryptionKey());

        Configure::write('Cookie.key', '');
        $this->assertSame('the-salt-of-this-test', $this->_getCookieEncryptionKey());

        Configure::write('Cookie.key', str_repeat('k', 32));
        $this->assertSame(str_repeat('k', 32), $this->_getCookieEncryptionKey());
        Configure::delete('Cookie.key');
    }
}
