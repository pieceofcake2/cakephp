<?php
/**
 * HttpSocketTest file
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
 * @package       Cake.Test.Case.Network.Http
 * @since         CakePHP(tm) v 1.2.0.4206
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Test\TestCase\Legacy\Network\Http;

use Cake\Core\App;
use Cake\Network\Http\HttpSocket;
use Cake\TestSuite\CakeTestCase;

/**
 * TestHttpSocket
 */
class TestHttpSocket extends HttpSocket
{
    /**
     * Convenience method for testing protected method
     *
     * @param array|string $uri URI (see {@link _parseUri()})
     * @return array Current configuration settings
     */
    public function configUri($uri = null): mixed
    {
        return parent::_configUri($uri);
    }

    /**
     * Convenience method for testing protected method
     *
     * @param array|string $uri URI to parse
     * @param array|bool $base If true use default URI config, otherwise indexed array to set 'scheme', 'host', 'port', etc.
     * @return array Parsed URI
     */
    public function parseUri($uri = null, $base = []): mixed
    {
        return parent::_parseUri($uri, $base);
    }

    /**
     * Convenience method for testing protected method
     *
     * @param array $uri A $uri array, or uses $this->config if left empty
     * @param string $uriTemplate The Uri template/format to use
     * @return string A fully qualified URL formatted according to $uriTemplate
     */
    public function buildUri($uri = [], $uriTemplate = '%scheme://%user:%pass@%host:%port/%path?%query#%fragment'): mixed
    {
        return parent::_buildUri($uri, $uriTemplate);
    }

    /**
     * Convenience method for testing protected method
     *
     * @param array $header Header to build
     * @return string Header built from array
     */
    public function buildHeader($header, $mode = 'standard'): mixed
    {
        return parent::_buildHeader($header, $mode);
    }

    /**
     * Convenience method for testing protected method
     *
     * @param array|string $query A query string to parse into an array or an array to return directly "as is"
     * @return array The $query parsed into a possibly multi-level array. If an empty $query is given, an empty array is returned.
     */
    public function parseQuery($query): mixed
    {
        return parent::_parseQuery($query);
    }

    /**
     * Convenience method for testing protected method
     *
     * @param array $request Needs to contain a 'uri' key. Should also contain a 'method' key, otherwise defaults to GET.
     * @return string Request line
     */
    public function buildRequestLine($request = []): mixed
    {
        return parent::_buildRequestLine($request);
    }

    /**
     * Convenience method for testing protected method
     *
     * @param bool $hex true to get them as HEX values, false otherwise
     * @return array Escape chars
     */
    public function tokenEscapeChars($hex = true, $chars = null): mixed
    {
        return parent::_tokenEscapeChars($hex, $chars);
    }

    /**
     * Convenience method for testing protected method
     *
     * @param string $token Token to escape
     * @return string Escaped token
     */
    public function escapeToken($token, $chars = null): mixed
    {
        return parent::_escapeToken($token, $chars);
    }
}

/**
 * HttpSocketTest class
 *
 * @package       Cake.Test.Case.Network.Http
 */
class HttpSocketTest extends CakeTestCase
{
    /**
     * Socket property
     *
     * @var mixed
     */
    public $Socket = null;

    /**
     * RequestSocket property
     *
     * @var mixed
     */
    public $RequestSocket = null;

    /**
     * This function sets up a TestHttpSocket instance we are going to use for testing
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->Socket = $this->getMock(TestHttpSocket::class, ['read', 'write', 'connect']);
        $this->RequestSocket = $this->getMock(TestHttpSocket::class, ['read', 'write', 'connect', 'request']);
        App::build([
            'Lib' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Lib' . DS],
        ]);
    }

    /**
     * We use this function to clean up after the test case was executed
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->Socket, $this->RequestSocket);

        parent::tearDown();
    }

    /**
     * testRequestCustomResponse
     *
     * @return void
     */
    public function testRequestCustomResponse(): void
    {
        $this->Socket->connected = true;
        $serverResponse = "HTTP/1.x 200 OK\r\nDate: Mon, 16 Apr 2007 04:14:16 GMT\r\nServer: CakeHttp Server\r\nContent-Type: text/html\r\n\r\n<h1>This is a test!</h1>";
        $this->Socket->expects($this->any())
            ->method('read')
            ->will($this->onConsecutiveCalls($serverResponse, false));

        $this->Socket->responseClass = 'CustomResponse';
        $response = $this->Socket->request('http://www.cakephp.org/');
        $this->assertInstanceOf('CustomResponse', $response);
        $this->assertEquals('HTTP/1.x 2', $response->first10);
    }

    /**
     * testProxy method
     *
     * @return void
     */
    public function testProxy(): void
    {
        $this->Socket->reset();
        $this->Socket->expects($this->any())->method('connect')->will($this->returnValue(true));
        $this->Socket->expects($this->any())->method('read')->will($this->returnValue(false));

        $this->Socket->configProxy('proxy.server', 123);
        $expected = "GET http://www.cakephp.org/ HTTP/1.1\r\nHost: www.cakephp.org\r\nConnection: close\r\nUser-Agent: CakePHP\r\n\r\n";
        $this->Socket->request('http://www.cakephp.org/');
        $this->assertEquals($expected, $this->Socket->request['raw']);
        $this->assertEquals('proxy.server', $this->Socket->config['host']);
        $this->assertEquals(123, $this->Socket->config['port']);
        $expected = [
            'host' => 'proxy.server',
            'port' => 123,
            'method' => null,
            'user' => null,
            'pass' => null,
        ];
        $this->assertEquals($expected, $this->Socket->request['proxy']);

        $expected = "GET http://www.cakephp.org/bakery HTTP/1.1\r\nHost: www.cakephp.org\r\nConnection: close\r\nUser-Agent: CakePHP\r\n\r\n";
        $this->Socket->request('/bakery');
        $this->assertEquals($expected, $this->Socket->request['raw']);
        $this->assertEquals('proxy.server', $this->Socket->config['host']);
        $this->assertEquals(123, $this->Socket->config['port']);
        $expected = [
            'host' => 'proxy.server',
            'port' => 123,
            'method' => null,
            'user' => null,
            'pass' => null,
        ];
        $this->assertEquals($expected, $this->Socket->request['proxy']);

        $expected = "GET http://www.cakephp.org/ HTTP/1.1\r\nHost: www.cakephp.org\r\nConnection: close\r\nUser-Agent: CakePHP\r\nProxy-Authorization: Test mark.secret\r\n\r\n";
        $this->Socket->configProxy('proxy.server', 123, 'Test', 'mark', 'secret');
        $this->Socket->request('http://www.cakephp.org/');
        $this->assertEquals($expected, $this->Socket->request['raw']);
        $this->assertEquals('proxy.server', $this->Socket->config['host']);
        $this->assertEquals(123, $this->Socket->config['port']);
        $expected = [
            'host' => 'proxy.server',
            'port' => 123,
            'method' => 'Test',
            'user' => 'mark',
            'pass' => 'secret',
        ];
        $this->assertEquals($expected, $this->Socket->request['proxy']);

        $this->Socket->configAuth('Test', 'login', 'passwd');
        $expected = "GET http://www.cakephp.org/ HTTP/1.1\r\nHost: www.cakephp.org\r\nConnection: close\r\nUser-Agent: CakePHP\r\nProxy-Authorization: Test mark.secret\r\nAuthorization: Test login.passwd\r\n\r\n";
        $this->Socket->request('http://www.cakephp.org/');
        $this->assertEquals($expected, $this->Socket->request['raw']);
        $expected = [
            'host' => 'proxy.server',
            'port' => 123,
            'method' => 'Test',
            'user' => 'mark',
            'pass' => 'secret',
        ];
        $this->assertEquals($expected, $this->Socket->request['proxy']);
        $expected = [
            'Test' => [
                'user' => 'login',
                'pass' => 'passwd',
            ],
        ];
        $this->assertEquals($expected, $this->Socket->request['auth']);
    }

    /**
     * Test authentication
     *
     * @return void
     */
    public function testAuth(): void
    {
        $this->Socket->expects($this->any())
            ->method('read')->will($this->returnValue(false));

        $this->Socket->get('http://mark:secret@example.com/test');
        $this->assertTrue(str_contains($this->Socket->request['header'], 'Authorization: Basic bWFyazpzZWNyZXQ='));

        $this->Socket->configAuth(false);
        $this->Socket->get('http://example.com/test');
        $this->assertFalse(strpos($this->Socket->request['header'], 'Authorization:'));

        $this->Socket->configAuth('Test', 'mark', 'passwd');
        $this->Socket->get('http://example.com/test');
        $this->assertTrue(str_contains($this->Socket->request['header'], 'Authorization: Test mark.passwd'));

        $this->Socket->configAuth(false);
        $this->Socket->request([
            'method' => 'GET',
            'uri' => 'http://example.com/test',
            'auth' => [
                'method' => 'Basic',
                'user' => 'joel',
                'pass' => 'hunter2',
            ],
        ]);
        $this->assertEquals($this->Socket->request['auth'], ['Basic' => ['user' => 'joel', 'pass' => 'hunter2']]);
        $this->assertTrue(str_contains($this->Socket->request['header'], 'Authorization: Basic am9lbDpodW50ZXIy'));

        $this->Socket->configAuth('Basic', 'mark', 'password');
        $this->Socket->request([
            'method' => 'GET',
            'uri' => 'http://example.com/test',
            'header' => [
                'Authorization' => 'OtherAuth Hi.There',
            ],
        ]);
        $this->assertPattern('/Authorization: OtherAuth Hi\.There/m', $this->Socket->request['header']);
    }
}
