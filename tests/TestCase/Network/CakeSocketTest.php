<?php
/**
 * SocketTest file
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
 * @package       Cake.Test.Case.Network
 * @since         CakePHP(tm) v 1.2.0.4206
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Test\TestCase\Network;

use Cake\Error\SocketException;
use Cake\Network\CakeSocket;
use Cake\TestSuite\CakeTestCase;
use Composer\CaBundle\CaBundle;
use InvalidArgumentException;

/**
 * SocketTest class
 *
 * @package       Cake.Test.Case.Network
 */
class CakeSocketTest extends CakeTestCase
{
    /**
     * Local listening socket opened by _listen()
     *
     * @var resource|null
     */
    protected $_server = null;

    /**
     * Connections accepted on the local listening socket
     *
     * @var array
     */
    protected $_peers = [];

    /**
     * Child PHP process started by _startTlsServer()
     *
     * @var resource|null
     */
    protected $_process = null;

    /**
     * Pipes of the child PHP process
     *
     * @var array
     */
    protected $_pipes = [];

    /**
     * Self-signed certificate and key written by _startTlsServer()
     *
     * @var string|null
     */
    protected $_pemFile = null;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->Socket = new CakeSocket(['timeout' => 1]);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->Socket);
        foreach ($this->_peers as $peer) {
            if (is_resource($peer)) {
                fclose($peer);
            }
        }
        $this->_peers = [];
        if (is_resource($this->_server)) {
            fclose($this->_server);
        }
        $this->_server = null;
        if ($this->_process !== null) {
            foreach ($this->_pipes as $pipe) {
                if (is_resource($pipe)) {
                    fclose($pipe);
                }
            }
            proc_terminate($this->_process);
            proc_close($this->_process);
            $this->_process = null;
            $this->_pipes = [];
        }
        if ($this->_pemFile !== null && file_exists($this->_pemFile)) {
            unlink($this->_pemFile);
        }
        $this->_pemFile = null;

        parent::tearDown();
    }

    /**
     * testConstruct method
     *
     * @return void
     */
    public function testConstruct(): void
    {
        $this->Socket = new CakeSocket();
        $config = $this->Socket->config;
        $this->assertSame($config, [
            'persistent' => false,
            'host' => 'localhost',
            'protocol' => 'tcp',
            'port' => 80,
            'timeout' => 30,
            'cryptoType' => 'tls',
        ]);

        $this->Socket->reset();
        $this->Socket->__construct(['host' => 'foo-bar']);
        $config['host'] = 'foo-bar';
        $this->assertSame($this->Socket->config, $config);

        $this->Socket = new CakeSocket(['host' => 'www.cakephp.org', 'port' => 23, 'protocol' => 'udp']);
        $config = $this->Socket->config;

        $config['host'] = 'www.cakephp.org';
        $config['port'] = 23;
        $config['protocol'] = 'udp';

        $this->assertSame($this->Socket->config, $config);
    }

    /**
     * testSocketConnection method
     *
     * @return void
     */
    public function testSocketConnection(): void
    {
        $this->assertFalse($this->Socket->connected);
        $this->Socket->disconnect();
        $this->assertFalse($this->Socket->connected);
        try {
            $this->Socket->connect();
            $this->assertTrue($this->Socket->connected);
            $this->Socket->connect();
            $this->assertTrue($this->Socket->connected);

            $this->Socket->disconnect();
            $config = ['persistent' => true];
            $this->Socket = new CakeSocket($config);
            $this->Socket->connect();
            $this->assertTrue($this->Socket->connected);
        } catch (SocketException) {
            $this->markTestSkipped('Cannot test network, skipping.');
        }
    }

    /**
     * data provider function for testInvalidConnection
     *
     * @return array
     */
    public static function invalidConnections(): mixed
    {
        return [
            [['host' => 'invalid.host', 'port' => 9999, 'timeout' => 1]],
            [['host' => '127.0.0.1', 'port' => '70000', 'timeout' => 1]],
        ];
    }

    /**
     * testInvalidConnection method
     *
     * @dataProvider invalidConnections
     * @return void
     */
    public function testInvalidConnection($data): void
    {
        $this->expectException(SocketException::class);
        $this->Socket->config = array_merge($this->Socket->config, $data);
        $this->Socket->connect();
    }

    /**
     * testSocketHost method
     *
     * @return void
     */
    public function testSocketHost(): void
    {
        try {
            $this->Socket = new CakeSocket();
            $this->Socket->connect();
            $this->assertEquals('127.0.0.1', $this->Socket->address());
            $this->assertEquals(gethostbyaddr('127.0.0.1'), $this->Socket->host());
            $this->assertEquals(null, $this->Socket->lastError());
            $this->assertTrue(in_array('127.0.0.1', $this->Socket->addresses()));

            $this->Socket = new CakeSocket(['host' => '127.0.0.1']);
            $this->Socket->connect();
            $this->assertEquals('127.0.0.1', $this->Socket->address());
            $this->assertEquals(gethostbyaddr('127.0.0.1'), $this->Socket->host());
            $this->assertEquals(null, $this->Socket->lastError());
            $this->assertTrue(in_array('127.0.0.1', $this->Socket->addresses()));
        } catch (SocketException) {
            $this->markTestSkipped('Cannot test network, skipping.');
        }
    }

    /**
     * testSocketWriting method
     *
     * @return void
     */
    public function testSocketWriting(): void
    {
        try {
            $request = "GET / HTTP/1.1\r\nConnection: close\r\n\r\n";
            $this->assertTrue((bool)$this->Socket->write($request));
        } catch (SocketException) {
            $this->markTestSkipped('Cannot test network, skipping.');
        }
    }

    /**
     * testSocketReading method
     *
     * @return void
     */
    public function testSocketReading(): void
    {
        $this->Socket = new CakeSocket(['timeout' => 5]);
        try {
            $this->Socket->connect();
            $this->assertEquals(null, $this->Socket->read(26));

            $config = ['host' => 'google.com', 'port' => 80, 'timeout' => 1];
            $this->Socket = new CakeSocket($config);
            $this->assertTrue($this->Socket->connect());
            $this->assertEquals(null, $this->Socket->read(26));
            $this->assertEquals('2: ' . __d('cake_dev', 'Connection timed out'), $this->Socket->lastError());
        } catch (SocketException) {
            $this->markTestSkipped('Cannot test network, skipping.');
        }
    }

    /**
     * testTimeOutConnection method
     *
     * @return void
     */
    public function testTimeOutConnection(): void
    {
        $config = ['host' => '127.0.0.1', 'timeout' => 0.5];
        $this->Socket = new CakeSocket($config);
        try {
            $this->assertTrue($this->Socket->connect());

            $config = ['host' => '127.0.0.1', 'timeout' => 0.00001];
            $this->Socket = new CakeSocket($config);
            $this->assertFalse($this->Socket->read(1024 * 1024));
            $this->assertEquals('2: ' . __d('cake_dev', 'Connection timed out'), $this->Socket->lastError());
        } catch (SocketException) {
            $this->markTestSkipped('Cannot test network, skipping.');
        }
    }

    /**
     * testLastError method
     *
     * @return void
     */
    public function testLastError(): void
    {
        $this->Socket = new CakeSocket();
        $this->Socket->setLastError(4, 'some error here');
        $this->assertEquals('4: some error here', $this->Socket->lastError());
    }

    /**
     * testReset method
     *
     * @return void
     */
    public function testReset(): void
    {
        $config = [
            'persistent' => true,
            'host' => '127.0.0.1',
            'protocol' => 'udp',
            'port' => 80,
            'timeout' => 20,
        ];
        $anotherSocket = new CakeSocket($config);
        $anotherSocket->reset();
        $this->assertEquals([], $anotherSocket->config);
    }

    /**
     * testEncrypt
     *
     * @return void
     */
    public function testEnableCryptoSocketExceptionNoSsl(): void
    {
        $this->expectException(SocketException::class);
        $this->skipIf(!extension_loaded('openssl'), 'OpenSSL is not enabled cannot test SSL.');
        $configNoSslOrTls = ['host' => 'localhost', 'port' => 80, 'timeout' => 0.1];

        // testing exception on no ssl socket server for ssl and tls methods
        $this->Socket = new CakeSocket($configNoSslOrTls);
        $this->Socket->connect();
        $this->Socket->enableCrypto('sslv3', 'client');
    }

    /**
     * testEnableCryptoSocketExceptionNoTls
     *
     * @return void
     */
    public function testEnableCryptoSocketExceptionNoTls(): void
    {
        $this->expectException(SocketException::class);
        $configNoSslOrTls = ['host' => 'localhost', 'port' => 80, 'timeout' => 0.1];

        // testing exception on no ssl socket server for ssl and tls methods
        $this->Socket = new CakeSocket($configNoSslOrTls);
        $this->Socket->connect();
        $this->Socket->enableCrypto('tls', 'client');
    }

    /**
     * Test that protocol in the host doesn't cause cert errors.
     *
     * @return void
     */
    public function testConnectProtocolInHost(): void
    {
        $this->skipIf(!extension_loaded('openssl'), 'OpenSSL is not enabled cannot test SSL.');
        $configSslTls = ['host' => 'ssl://smtp.gmail.com', 'port' => 465, 'timeout' => 5];
        $socket = new CakeSocket($configSslTls);
        try {
            $socket->connect();
            $this->assertEquals('smtp.gmail.com', $socket->config['host']);
            $this->assertEquals('ssl', $socket->config['protocol']);
        } catch (SocketException) {
            $this->markTestSkipped('Cannot test network, skipping.');
        }
    }

    /**
     * _connectSocketToSslTls
     *
     * @return void
     */
    protected function _connectSocketToSslTls(): void
    {
        $this->skipIf(!extension_loaded('openssl'), 'OpenSSL is not enabled cannot test SSL.');
        $configSslTls = ['host' => 'smtp.gmail.com', 'port' => 465, 'timeout' => 5];
        $this->Socket = new CakeSocket($configSslTls);
        try {
            $this->Socket->connect();
        } catch (SocketException) {
            $this->markTestSkipped('Cannot test network, skipping.');
        }
    }

    /**
     * testEnableCryptoBadMode
     *
     * @return void
     */
    public function testEnableCryptoBadMode(): void
    {
        $this->expectException(InvalidArgumentException::class);
        // testing wrong encryption mode
        $this->_connectSocketToSslTls();
        $this->Socket->enableCrypto('doesntExistMode', 'server');
        $this->Socket->disconnect();
    }

    /**
     * testEnableCrypto
     *
     * @return void
     */
    public function testEnableCrypto(): void
    {
        // testing on tls server
        $this->_connectSocketToSslTls();
        $this->assertTrue($this->Socket->enableCrypto('tls', 'client'));
        $this->Socket->disconnect();
    }

    /**
     * testEnableCrypto tlsv1_1
     *
     * @return void
     */
    public function testEnableCryptoTlsV12(): void
    {
        $this->skipIf(!defined('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT'), 'TLS1.2 is not supported on this system');

        // testing on tls server
        $this->_connectSocketToSslTls();
        $this->assertTrue($this->Socket->enableCrypto('tlsv1_2', 'client'));
        $this->Socket->disconnect();
    }

    /**
     * testEnableCryptoExceptionEnableTwice
     *
     * @return void
     */
    public function testEnableCryptoExceptionEnableTwice(): void
    {
        $this->expectException(SocketException::class);
        // testing on tls server
        $this->_connectSocketToSslTls();
        $this->Socket->enableCrypto('tls', 'client');
        $this->Socket->enableCrypto('tls', 'client');
    }

    /**
     * testEnableCryptoExceptionDisableTwice
     *
     * @return void
     */
    public function testEnableCryptoExceptionDisableTwice(): void
    {
        $this->expectException(SocketException::class);
        // testing on tls server
        $this->_connectSocketToSslTls();
        $this->Socket->enableCrypto('tls', 'client', false);
    }

    /**
     * testEnableCryptoEnableStatus
     *
     * @return void
     */
    public function testEnableCryptoEnableStatus(): void
    {
        // testing on tls server
        $this->_connectSocketToSslTls();
        $this->assertFalse($this->Socket->encrypted);
        $this->Socket->enableCrypto('tls', 'client', true);
        $this->assertTrue($this->Socket->encrypted);
    }

    /**
     * test getting the context for a socket.
     *
     * @return void
     */
    public function testGetContext(): void
    {
        $this->skipIf(!extension_loaded('openssl'), 'OpenSSL is not enabled cannot test SSL.');
        $config = [
            'host' => 'smtp.gmail.com',
            'port' => 465,
            'timeout' => 5,
            'context' => [
                'ssl' => ['capture_peer' => true],
            ],
        ];
        $this->Socket = new CakeSocket($config);
        $this->Socket->connect();
        $result = $this->Socket->context();
        $this->assertSame($config['context']['ssl']['capture_peer'], $result['ssl']['capture_peer']);
    }

    /**
     * test configuring the context from the flat keys.
     *
     * @return void
     */
    public function testConfigContext(): void
    {
        $this->skipIf(!extension_loaded('openssl'), 'OpenSSL is not enabled cannot test SSL.');
        $config = [
            'host' => 'smtp.gmail.com',
            'port' => 465,
            'timeout' => 5,
            'ssl_verify_peer' => true,
            'ssl_allow_self_signed' => false,
            'ssl_verify_depth' => 5,
            'ssl_verify_host' => true,
        ];
        $this->Socket = new CakeSocket($config);

        $this->Socket->connect();
        $result = $this->Socket->context();

        $this->assertTrue($result['ssl']['verify_peer']);
        $this->assertFalse($result['ssl']['allow_self_signed']);
        $this->assertEquals(5, $result['ssl']['verify_depth']);
        $this->assertEquals('smtp.gmail.com', $result['ssl']['CN_match']);
        $this->assertArrayNotHasKey('ssl_verify_peer', $this->Socket->config);
        $this->assertArrayNotHasKey('ssl_allow_self_signed', $this->Socket->config);
        $this->assertArrayNotHasKey('ssl_verify_host', $this->Socket->config);
        $this->assertArrayNotHasKey('ssl_verify_depth', $this->Socket->config);
    }

    /**
     * Opens a listening TCP socket on a free port of 127.0.0.1.
     *
     * @return int The port of the listening socket
     */
    protected function _listen(): int
    {
        $this->_server = stream_socket_server('tcp://127.0.0.1:0', $errNum, $errStr);
        if (!$this->_server) {
            $this->markTestSkipped('Cannot open a local server socket: ' . $errStr);
        }
        $name = stream_socket_get_name($this->_server, false);

        return (int)substr($name, strrpos($name, ':') + 1);
    }

    /**
     * Accepts the pending connection on the local listening socket.
     *
     * @return resource The server side of the connection
     */
    protected function _accept(): mixed
    {
        $peer = stream_socket_accept($this->_server, 5);
        $this->assertNotFalse($peer, 'The client did not connect to the local server.');
        $this->_peers[] = $peer;

        return $peer;
    }

    /**
     * Starts a TLS server in a child PHP process, as the TLS handshake needs
     * both ends to run at the same time.
     *
     * In the `proxy` mode the server first acts as an HTTP proxy: it reads the
     * CONNECT request, prints it JSON encoded on its STDOUT and answers 200 before
     * the TLS handshake. In the `refuse` mode it reads and prints the request too,
     * but answers 407 and closes the connection. After the handshake it echoes one
     * line in upper case.
     *
     * @param string $mode `direct`, `proxy` or `refuse`
     * @return int The port of the server
     */
    protected function _startTlsServer(string $mode): int
    {
        $this->skipIf(!extension_loaded('openssl'), 'OpenSSL is not enabled cannot test SSL.');
        $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        $this->skipIf($key === false, 'Cannot create a private key.');
        $csr = openssl_csr_new(['commonName' => 'localhost'], $key, ['digest_alg' => 'sha256']);
        $cert = openssl_csr_sign($csr, null, $key, 1, ['digest_alg' => 'sha256']);
        openssl_x509_export($cert, $certPem);
        openssl_pkey_export($key, $keyPem);
        $this->_pemFile = TMP . 'cake_socket_test_' . getmypid() . '.pem';
        file_put_contents($this->_pemFile, $certPem . $keyPem);

        $code = <<<'PHP'
            $context = stream_context_create(['ssl' => ['local_cert' => $argv[1]]]);
            $server = stream_socket_server('tcp://127.0.0.1:0', $errNum, $errStr, STREAM_SERVER_BIND | STREAM_SERVER_LISTEN, $context);
            $name = stream_socket_get_name($server, false);
            echo substr($name, strrpos($name, ':') + 1), "\n";
            $client = stream_socket_accept($server, 5);
            if (!$client) {
                exit(1);
            }
            if ($argv[2] !== 'direct') {
                $request = '';
                while (!str_ends_with($request, "\r\n\r\n") && ($line = fgets($client)) !== false) {
                    $request .= $line;
                }
                echo json_encode($request), "\n";
                if ($argv[2] === 'refuse') {
                    fwrite($client, "HTTP/1.1 407 Proxy Authentication Required\r\n\r\n");
                    fclose($client);
                    exit(0);
                }
                fwrite($client, "HTTP/1.1 200 Connection established\r\n\r\n");
            }
            if (!stream_socket_enable_crypto($client, true, STREAM_CRYPTO_METHOD_TLS_SERVER)) {
                exit(2);
            }
            fwrite($client, strtoupper(fgets($client)));
            fclose($client);
            PHP;
        $this->_process = proc_open(
            [PHP_BINARY, '-r', $code, '--', $this->_pemFile, $mode],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $this->_pipes,
        );
        $this->skipIf(!is_resource($this->_process), 'Cannot start a PHP process.');
        $port = (int)$this->_readServerLine();
        if ($port === 0) {
            $this->fail('The TLS server did not start: ' . stream_get_contents($this->_pipes[2]));
        }

        return $port;
    }

    /**
     * Reads a line that the TLS server prints on its STDOUT, waiting 10 seconds at most.
     *
     * stream_set_timeout() does not work on pipes, so the wait is a stream_select().
     *
     * @return string The line, or an empty string when the server printed nothing in time
     */
    protected function _readServerLine(): string
    {
        $read = [$this->_pipes[1]];
        $write = $except = null;
        if (!stream_select($read, $write, $except, 10)) {
            return '';
        }

        return (string)fgets($this->_pipes[1]);
    }

    /**
     * Collects the warnings raised while the callback runs, without turning them
     * into exceptions, as the error handler of an application in production does not.
     *
     * @param callable $callback The callback.
     * @param array<string> $warnings The warnings raised, filled in by the handler.
     * @return void
     */
    protected function _withoutThrowingWarnings(callable $callback, array &$warnings): void
    {
        set_error_handler(function ($code, $message) use (&$warnings) {
            $warnings[] = $message;

            return true;
        }, E_WARNING);
        try {
            $callback();
        } finally {
            restore_error_handler();
        }
    }

    /**
     * Test that data written to the socket arrives at the peer unchanged, that
     * the data sent by the peer is read back, and that read() returns false
     * once the peer has closed the connection.
     *
     * @return void
     */
    public function testWriteAndReadWithLocalServer(): void
    {
        $port = $this->_listen();
        $socket = new CakeSocket(['host' => '127.0.0.1', 'port' => $port, 'timeout' => 5]);
        $this->assertTrue($socket->connect());
        $this->assertTrue($socket->connected);
        $peer = $this->_accept();

        $request = "GET / HTTP/1.1\r\nHost: 127.0.0.1\r\n\r\n";
        $this->assertSame(strlen($request), $socket->write($request));
        $this->assertSame($request, fread($peer, 1024));

        fwrite($peer, 'hello world');
        fclose($peer);
        $this->assertSame('hello world', $socket->read(1024));
        $this->assertFalse($socket->read(1024));
        $this->assertNull($socket->lastError());

        $this->assertTrue($socket->disconnect());
        $this->assertFalse($socket->connected);
        $this->assertNull($socket->connection);
    }

    /**
     * Test that write() and read() connect first when the socket is not connected.
     *
     * The read() waits for the timeout, as the peer can only send data after
     * the connection is made inside read().
     *
     * @return void
     */
    public function testWriteAndReadConnectWhenNotConnected(): void
    {
        $port = $this->_listen();
        $socket = new CakeSocket(['host' => '127.0.0.1', 'port' => $port, 'timeout' => 1]);
        $this->assertFalse($socket->connected);
        $this->assertSame(4, $socket->write('ping'));
        $this->assertTrue($socket->connected);
        $this->assertSame('ping', fread($this->_accept(), 1024));

        $socket->disconnect();
        $this->assertFalse($socket->connected);
        $this->assertFalse($socket->read(4));
        $this->assertTrue($socket->connected);
        $this->assertSame(E_WARNING . ': ' . __d('cake_dev', 'Connection timed out'), $socket->lastError());
        $peer = $this->_accept();
        fwrite($peer, 'pong');
        $this->assertSame('pong', $socket->read(4));
    }

    /**
     * Test that connect() closes the current connection before it opens a new one.
     *
     * @return void
     */
    public function testConnectClosesThePreviousConnection(): void
    {
        $port = $this->_listen();
        $socket = new CakeSocket(['host' => '127.0.0.1', 'port' => $port, 'timeout' => 5]);
        $socket->connect();
        $first = $socket->connection;
        $firstPeer = $this->_accept();

        $this->assertTrue($socket->connect());
        $this->assertFalse(is_resource($first), 'The first connection is still open.');
        $this->assertNotSame($first, $socket->connection);
        $this->assertSame('', fread($firstPeer, 1024));
        $this->assertTrue(feof($firstPeer));
    }

    /**
     * Test that a protocol in the host is moved to the protocol setting.
     *
     * @return void
     */
    public function testConnectWithProtocolInHost(): void
    {
        $port = $this->_listen();
        $socket = new CakeSocket(['host' => 'tcp://127.0.0.1', 'protocol' => 'udp', 'port' => $port, 'timeout' => 5]);
        $this->assertTrue($socket->connect());
        $this->assertSame('tcp', $socket->config['protocol']);
        $this->assertSame('127.0.0.1', $socket->config['host']);
        $this->_accept();
    }

    /**
     * Test that a refused connection throws an exception with the system
     * error and stores it as the last error.
     *
     * The wording of the system error depends on the platform, so only its form
     * is checked.
     *
     * @return void
     */
    public function testConnectRefused(): void
    {
        $port = $this->_listen();
        fclose($this->_server);
        $socket = new CakeSocket(['host' => '127.0.0.1', 'port' => $port, 'timeout' => 1]);
        try {
            $socket->connect();
            $this->fail('No exception was thrown.');
        } catch (SocketException $e) {
            $this->assertNotSame('', $e->getMessage());
            $this->assertGreaterThan(0, $e->getCode());
            $this->assertSame($e->getCode() . ': ' . $e->getMessage(), $socket->lastError());
        }
        $this->assertFalse($socket->connected);
    }

    /**
     * Test that a failure that stream_socket_client() does not report in its
     * error arguments, here a TLS handshake that times out, throws the
     * warnings that were raised while connecting.
     *
     * The wording of the warnings depends on PHP and OpenSSL, so only that they
     * are reported, one per line, is checked.
     *
     * @return void
     */
    public function testConnectThrowsTheConnectionWarnings(): void
    {
        $this->skipIf(!extension_loaded('openssl'), 'OpenSSL is not enabled cannot test SSL.');
        $port = $this->_listen();
        $socket = new CakeSocket(['host' => 'ssl://127.0.0.1', 'port' => $port, 'timeout' => 1]);
        try {
            $socket->connect();
            $this->fail('No exception was thrown.');
        } catch (SocketException $e) {
            $this->assertSame(E_WARNING, $e->getCode());
            $this->assertNotSame('', $e->getMessage());
            foreach (explode("\n", $e->getMessage()) as $line) {
                $this->assertNotSame('', trim($line), 'Every line is a warning: ' . $e->getMessage());
            }
        }
        $this->assertFalse($socket->connected);
    }

    /**
     * Test that a failed connect() reports only its own warnings, not also the
     * warnings of an earlier connect() of the same socket.
     *
     * @return void
     */
    public function testConnectReportsOnlyTheWarningsOfThisAttempt(): void
    {
        $this->skipIf(!extension_loaded('openssl'), 'OpenSSL is not enabled cannot test SSL.');
        $port = $this->_listen();
        $socket = new CakeSocket(['host' => 'ssl://127.0.0.1', 'port' => $port, 'timeout' => 1]);
        $messages = [];
        for ($i = 0; $i < 2; $i++) {
            try {
                $socket->connect();
                $this->fail('No exception was thrown.');
            } catch (SocketException $e) {
                $messages[] = $e->getMessage();
            }
            $this->_accept();
        }
        $this->assertSame($messages[0], $messages[1]);
    }

    /**
     * Test that read() returns false and sets the last error when the peer
     * sends nothing within the timeout.
     *
     * @return void
     */
    public function testReadTimesOut(): void
    {
        $port = $this->_listen();
        $socket = new CakeSocket(['host' => '127.0.0.1', 'port' => $port, 'timeout' => 1]);
        $socket->connect();
        $this->_accept();

        $this->assertFalse($socket->read(1024));
        $this->assertSame(E_WARNING . ': ' . __d('cake_dev', 'Connection timed out'), $socket->lastError());
        $this->assertTrue($socket->connected);
    }

    /**
     * Test that a fractional timeout is used for the reads.
     *
     * @return void
     */
    public function testConnectWithFractionalTimeout(): void
    {
        $port = $this->_listen();
        $socket = new CakeSocket(['host' => '127.0.0.1', 'port' => $port, 'timeout' => 0.5]);
        $this->assertTrue($socket->connect());
        $this->_accept();
        $this->assertFalse($socket->read(1024));
        $this->assertSame(E_WARNING . ': ' . __d('cake_dev', 'Connection timed out'), $socket->lastError());
    }

    /**
     * Test that context() is null without a connection, and that the flat
     * ssl_* settings and the defaults end up in the context of the stream.
     *
     * @return void
     */
    public function testContextOptions(): void
    {
        $port = $this->_listen();
        $socket = new CakeSocket([
            'host' => '127.0.0.1',
            'port' => $port,
            'timeout' => 5,
            'ssl_verify_peer' => true,
            'ssl_verify_depth' => 3,
            'ssl_verify_host' => true,
            'ssl_allow_self_signed' => true,
            'context' => [
                'socket' => ['tcp_nodelay' => true],
            ],
        ]);
        $this->assertNull($socket->context());

        $socket->connect();
        $this->_accept();
        $result = $socket->context();

        $caPathOrFile = CaBundle::getSystemCaRootBundlePath();
        $caKey = is_dir($caPathOrFile) ? 'capath' : 'cafile';
        $expected = [
            'allow_self_signed' => true,
            'verify_peer' => true,
            'verify_depth' => 3,
            'SNI_enabled' => true,
            'peer_name' => '127.0.0.1',
            $caKey => $caPathOrFile,
            'CN_match' => '127.0.0.1',
        ];
        ksort($expected);
        ksort($result['ssl']);
        $this->assertSame($expected, $result['ssl']);
        $this->assertSame(['tcp_nodelay' => true], $result['socket']);
        $this->assertSame([], preg_grep('/^ssl_/', array_keys($socket->config)));
    }

    /**
     * Test that a flat ssl_* setting fills in an SSL context option that is empty,
     * also when the context gives it as false.
     *
     * @return void
     */
    public function testContextOptionsFlatSettingFillsAnEmptyOption(): void
    {
        $port = $this->_listen();
        $socket = new CakeSocket([
            'host' => '127.0.0.1',
            'port' => $port,
            'timeout' => 5,
            'ssl_allow_self_signed' => true,
            'context' => ['ssl' => ['allow_self_signed' => false]],
        ]);
        $socket->connect();
        $this->_accept();

        $this->assertTrue($socket->context()['ssl']['allow_self_signed']);
    }

    /**
     * Test that the explicit SSL context options are not replaced by the
     * defaults, and that the host of the request is the peer name.
     *
     * @return void
     */
    public function testContextOptionsKeepExplicitValues(): void
    {
        $port = $this->_listen();
        $socket = new CakeSocket([
            'host' => '127.0.0.1',
            'port' => $port,
            'timeout' => 5,
            'request' => ['uri' => ['host' => 'example.com', 'scheme' => 'http']],
            'context' => [
                'ssl' => [
                    'SNI_enabled' => false,
                    'cafile' => '/path/to/ca.pem',
                    'verify_host' => false,
                ],
            ],
        ]);
        $socket->connect();
        $this->_accept();
        $result = $socket->context();

        $this->assertSame(
            ['SNI_enabled' => false, 'cafile' => '/path/to/ca.pem', 'peer_name' => 'example.com'],
            $result['ssl'],
        );
    }

    /**
     * Test that the address lookups of an IP address return the address itself,
     * without a DNS lookup.
     *
     * @return void
     */
    public function testAddressLookupsOfAnIpAddress(): void
    {
        $socket = new CakeSocket(['host' => '127.0.0.1']);
        $this->assertSame('127.0.0.1', $socket->address());
        $this->assertSame(['127.0.0.1'], $socket->addresses());

        $socket = new CakeSocket(['host' => '::1']);
        $this->assertSame('::1', $socket->address());
        $this->assertSame(['::1'], $socket->addresses());
    }

    /**
     * Test that reset() sets the given properties, and without a state
     * restores the default values of all the properties.
     *
     * @return void
     */
    public function testResetWithState(): void
    {
        $socket = new CakeSocket(['host' => '127.0.0.1']);
        $this->assertTrue($socket->reset(['description' => 'changed', 'encrypted' => true]));
        $this->assertSame('changed', $socket->description);
        $this->assertTrue($socket->encrypted);
        $this->assertSame('127.0.0.1', $socket->config['host']);

        $socket->setLastError(1, 'error');
        $this->assertTrue($socket->reset());
        $this->assertSame('Remote DataSource Network Socket Interface', $socket->description);
        $this->assertFalse($socket->encrypted);
        // reset() without a state restores the state before __construct(), so the config
        // is cleared to an empty array, not set to the base config.
        $this->assertSame([], $socket->config);
        $this->assertSame([], $socket->lastError);
        $this->assertNull($socket->lastError());
    }

    /**
     * Test that reset() closes an open connection instead of dropping it, and that
     * a state that does not replace the connection keeps it open.
     *
     * @return void
     */
    public function testResetClosesTheConnection(): void
    {
        $port = $this->_listen();
        $socket = new CakeSocket(['host' => '127.0.0.1', 'port' => $port, 'timeout' => 1]);
        $this->assertTrue($socket->connect());
        $peer = $this->_accept();
        $connection = $socket->connection;

        $this->assertTrue($socket->reset(['description' => 'changed']));
        $this->assertTrue($socket->connected);
        $this->assertSame($connection, $socket->connection);
        $this->assertTrue(is_resource($connection));

        $this->assertTrue($socket->reset());
        $this->assertFalse($socket->connected);
        $this->assertNull($socket->connection);
        $this->assertFalse(is_resource($connection), 'The connection is closed.');
        $this->assertSame('', fread($peer, 1024));
        $this->assertTrue(feof($peer), 'The peer sees the connection closed.');
    }

    /**
     * Test that an unknown encryption scheme is rejected before the stream is used.
     *
     * @return void
     */
    public function testEnableCryptoInvalidScheme(): void
    {
        $socket = new CakeSocket();
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid encryption scheme chosen');
        $socket->enableCrypto('tls', 'peer');
    }

    /**
     * Test that a failed crypto operation throws an exception and stores it
     * as the last error.
     *
     * @return void
     */
    public function testEnableCryptoFailure(): void
    {
        $port = $this->_listen();
        $socket = new CakeSocket(['host' => '127.0.0.1', 'port' => $port, 'timeout' => 5]);
        $socket->connect();
        $this->_accept();
        try {
            $socket->enableCrypto('tls', 'client', false);
            $this->fail('No exception was thrown.');
        } catch (SocketException $e) {
            $message = __d('cake_dev', 'Unable to perform enableCrypto operation on CakeSocket');
            $this->assertSame($message, $e->getMessage());
            $this->assertSame($message, $socket->lastError['str']);
        }
        $this->assertFalse($socket->encrypted);
    }

    /**
     * Test that a TLS handshake with a peer that does not speak TLS throws a
     * SocketException and stores it as the last error.
     *
     * The warning of stream_socket_enable_crypto() is not turned into an exception
     * here, as it is not in production, so the failed handshake is reported by the
     * false that stream_socket_enable_crypto() returns.
     *
     * @return void
     */
    public function testEnableCryptoErrorFromThePeer(): void
    {
        $this->skipIf(!extension_loaded('openssl'), 'OpenSSL is not enabled cannot test SSL.');
        $port = $this->_listen();
        $socket = new CakeSocket(['host' => '127.0.0.1', 'port' => $port, 'timeout' => 1]);
        $socket->connect();
        $peer = $this->_accept();
        fwrite($peer, "220 not a TLS server\r\n");
        fclose($peer);
        $warnings = [];
        $exception = null;
        $this->_withoutThrowingWarnings(function () use ($socket, &$exception): void {
            try {
                $socket->enableCrypto('tls', 'client');
            } catch (SocketException $e) {
                $exception = $e;
            }
        }, $warnings);

        $message = __d('cake_dev', 'Unable to perform enableCrypto operation on CakeSocket');
        $this->assertNotNull($exception, 'No SocketException was thrown.');
        $this->assertSame($message, $exception->getMessage());
        $this->assertSame($message, $socket->lastError['str']);
        $this->assertNotEmpty($warnings);
        $this->assertStringStartsWith('stream_socket_enable_crypto(): ', $warnings[0]);
        $this->assertFalse($socket->encrypted);
    }

    /**
     * Test enabling TLS on a connection to a local TLS server, and that the
     * data is exchanged over the encrypted stream.
     *
     * @return void
     */
    public function testEnableCryptoWithLocalTlsServer(): void
    {
        $port = $this->_startTlsServer('direct');
        $socket = new CakeSocket([
            'host' => '127.0.0.1',
            'port' => $port,
            'timeout' => 5,
            'ssl_cafile' => $this->_pemFile,
            'ssl_peer_name' => 'localhost',
        ]);
        $socket->connect();
        $this->assertFalse($socket->encrypted);

        $this->assertTrue($socket->enableCrypto('tls', 'client'));
        $this->assertTrue($socket->encrypted);
        $this->assertSame(6, $socket->write("hello\n"));
        $this->assertSame("HELLO\n", $socket->read(1024));
    }

    /**
     * Creates a socket that connects to the local TLS server as a proxy for a https
     * request to localhost:443.
     *
     * @param int $port The port of the server.
     * @return CakeSocket
     */
    protected function _proxySocket(int $port): CakeSocket
    {
        return new CakeSocket([
            'host' => '127.0.0.1',
            'protocol' => 'ssl',
            'port' => $port,
            'timeout' => 5,
            'proxy' => true,
            'proxyauth' => 'Basic dXNlcjpwYXNz',
            'request' => ['uri' => ['scheme' => 'https', 'host' => 'localhost', 'port' => 443]],
            'ssl_cafile' => $this->_pemFile,
        ]);
    }

    /**
     * Test that a https request through a proxy sends the CONNECT request for the
     * requested host with the proxy authorization to the proxy, and then enables TLS
     * with the peer name of the requested host.
     *
     * @return void
     */
    public function testConnectThroughProxyEnablesCrypto(): void
    {
        $port = $this->_startTlsServer('proxy');
        $socket = $this->_proxySocket($port);
        $this->assertTrue($socket->connect());
        $this->assertTrue($socket->encrypted);

        $lines = explode("\r\n", (string)json_decode($this->_readServerLine()));
        $this->assertSame('CONNECT localhost:443 HTTP/1.1', $lines[0]);
        $this->assertContains('User-Agent: php proxy', $lines);
        $this->assertContains('Proxy-Authorization: Basic dXNlcjpwYXNz', $lines);
        $this->assertSame(['', ''], array_slice($lines, -2), 'The request ends with an empty line.');
        $this->assertSame(5, $socket->write("ping\n"));
        $this->assertSame("PING\n", $socket->read(1024));
    }

    /**
     * Test that the Host header of the CONNECT request is the authority of the
     * requested host, as RFC 9110 (9.3.6) requires, not the proxy.
     *
     * @return void
     */
    public function testConnectThroughProxySendsTheTargetAsHost(): void
    {
        $port = $this->_startTlsServer('proxy');
        $this->_proxySocket($port)->connect();

        $lines = explode("\r\n", (string)json_decode($this->_readServerLine()));
        $this->assertSame('Host: localhost:443', $lines[1]);
    }

    /**
     * Test that a proxy that refuses the CONNECT request with a status other than
     * 2xx is reported as an error that names the status, instead of a TLS handshake
     * with the proxy.
     *
     * The warnings are not turned into exceptions here, as they are not in production.
     *
     * @return void
     */
    public function testConnectThroughProxyRejectsAnErrorStatus(): void
    {
        $port = $this->_startTlsServer('refuse');
        $socket = $this->_proxySocket($port);
        $warnings = [];
        $exception = null;
        $this->_withoutThrowingWarnings(function () use ($socket, &$exception): void {
            try {
                $socket->connect();
            } catch (SocketException $e) {
                $exception = $e;
            }
        }, $warnings);

        $this->assertNotNull($exception, 'No SocketException was thrown.');
        $this->assertStringContainsString('407', $exception->getMessage());
        $this->assertSame(407, $exception->getCode());
        $this->assertSame($exception->getMessage(), $socket->lastError['str']);
        $this->assertFalse($socket->encrypted);
        $this->assertFalse($socket->connected);
        $this->assertNull($socket->connection);
    }

    /**
     * Test that a proxy that does not answer the CONNECT request is reported as
     * an error, instead of a TLS handshake with the proxy.
     *
     * @return void
     */
    public function testConnectThroughProxyWithoutAnswer(): void
    {
        $port = $this->_listen();
        $socket = $this->_proxySocket($port);
        $socket->config['timeout'] = 1;
        try {
            $socket->connect();
            $this->fail('No exception was thrown.');
        } catch (SocketException $e) {
            $message = __d('cake_dev', 'The proxy did not establish the tunnel to %s', 'localhost:443');
            $this->assertSame($message, $e->getMessage());
            $this->assertSame(0, $e->getCode());
            $this->assertSame($message, $socket->lastError['str']);
        }
        $this->assertFalse($socket->encrypted);
        $this->assertFalse($socket->connected);
    }
}
