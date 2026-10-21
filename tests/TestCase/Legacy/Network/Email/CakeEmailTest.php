<?php
/**
 * CakeEmailTest file
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
 * @package       Cake.Test.Case.Network.Email
 * @since         CakePHP(tm) v 2.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Test\TestCase\Legacy\Network\Email;

use Cake\Core\App;
use Cake\Core\CakePlugin;
use Cake\Network\Email\CakeEmail;
use Cake\Network\Email\DebugTransport;
use Cake\TestSuite\CakeTestCase;
use Cake\Utility\File;

/**
 * Help to test CakeEmail
 */
class TestCakeEmail extends CakeEmail
{
    /**
     * Config class name.
     *
     * Use a the testing config class in this file.
     *
     * @var string
     */
    protected $_configClass = TestEmailConfig::class;

    /**
     * Config
     */
    protected $_config = [];

    /**
     * Wrap to protected method
     *
     * @return array
     */
    public function formatAddress($address): mixed
    {
        return parent::_formatAddress($address);
    }

    /**
     * Wrap to protected method
     *
     * @return array
     */
    public function wrap($text, $length = CakeEmail::LINE_LENGTH_MUST): mixed
    {
        return parent::_wrap($text, $length);
    }

    /**
     * Get the boundary attribute
     *
     * @return string
     */
    public function getBoundary(): mixed
    {
        return $this->_boundary;
    }

    /**
     * Encode to protected method
     *
     * @return string
     */
    public function encode($text): mixed
    {
        return $this->_encode($text);
    }

    /**
     * Render to protected method
     *
     * @return array
     */
    public function render($content): mixed
    {
        return $this->_render($content);
    }
}

/**
 * EmailConfig class
 */
class TestEmailConfig
{
    /**
     * default config
     *
     * @var array
     */
    public $default = [
        'subject' => 'Default Subject',
    ];

    /**
     * test config
     *
     * @var array
     */
    public $test = [
        'from' => ['some@example.com' => 'My website'],
        'to' => ['test@example.com' => 'Testname'],
        'subject' => 'Test mail subject',
        'transport' => 'Debug',
        'theme' => 'TestTheme',
        'helpers' => ['Html', 'Form'],
    ];

    /**
     * test config 2
     *
     * @var array
     */
    public $test2 = [
        'from' => ['some@example.com' => 'My website'],
        'to' => ['test@example.com' => 'Testname'],
        'subject' => 'Test mail subject',
        'transport' => 'Smtp',
        'host' => 'cakephp.org',
        'timeout' => 60,
    ];

    /**
     * config with a mailer DSN
     *
     * @var array
     */
    public $dsn = [
        'dsn' => 'smtp://user:p%40ss@smtp.example.com:587?timeout=10',
        'transport' => 'Mail',
        'host' => 'ignored.example.com',
        'client' => 'app.example.com',
        'from' => ['some@example.com' => 'My website'],
    ];

    /**
     * config with a mailer DSN of an unknown scheme
     *
     * @var array
     */
    public $dsnBroken = [
        'dsn' => 'ses+smtp://user:secret@default',
    ];

    /**
     * config with an empty mailer DSN
     *
     * @var array
     */
    public $dsnEmpty = [
        'dsn' => null,
        'transport' => 'Smtp',
        'host' => 'cakephp.org',
        'port' => 2525,
    ];
}

/**
 * CakeEmailTest class
 *
 * @package       Cake.Test.Case.Network.Email
 */
class CakeEmailTest extends CakeTestCase
{
    /**
     * setUp
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->_configFileExists = true;
        $emailConfig = new File(CONFIG . 'email.php');
        if (!$emailConfig->exists()) {
            $this->_configFileExists = false;
            $emailConfig->create();
        }

        $this->CakeEmail = new TestCakeEmail();

        App::build([
            'View' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'View' . DS],
            'Lib' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Lib' . DS],
        ]);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        if (!$this->_configFileExists) {
            unlink(CONFIG . 'email.php');
        }

        parent::tearDown();
    }

    /**
     * testConfigString method
     *
     * @return void
     */
    public function testConfigString(): void
    {
        $configs = new TestEmailConfig();
        $this->CakeEmail->config('test');

        $result = $this->CakeEmail->to();
        $this->assertEquals($configs->test['to'], $result);

        $result = $this->CakeEmail->from();
        $this->assertEquals($configs->test['from'], $result);

        $result = $this->CakeEmail->subject();
        $this->assertEquals($configs->test['subject'], $result);

        $result = $this->CakeEmail->theme();
        $this->assertEquals($configs->test['theme'], $result);

        $result = $this->CakeEmail->transport();
        $this->assertEquals($configs->test['transport'], $result);

        $result = $this->CakeEmail->transportClass();
        $this->assertInstanceOf('DebugTransport', $result);

        $result = $this->CakeEmail->helpers();
        $this->assertEquals($configs->test['helpers'], $result);
    }

    /**
     * testConstructWithConfigString method
     *
     * @return void
     */
    public function testConstructWithConfigString(): void
    {
        $configs = new TestEmailConfig();
        $this->CakeEmail = new TestCakeEmail('test');

        $result = $this->CakeEmail->to();
        $this->assertEquals($configs->test['to'], $result);

        $result = $this->CakeEmail->from();
        $this->assertEquals($configs->test['from'], $result);

        $result = $this->CakeEmail->subject();
        $this->assertEquals($configs->test['subject'], $result);

        $result = $this->CakeEmail->transport();
        $this->assertEquals($configs->test['transport'], $result);

        $result = $this->CakeEmail->transportClass();
        $this->assertTrue($result instanceof DebugTransport);

        $result = $this->CakeEmail->send('This is the message');

        $this->assertTrue((bool)strpos($result['headers'], 'Message-ID: '));
        $this->assertTrue((bool)strpos($result['headers'], 'To: '));
    }

    /**
     * testTransport method
     *
     * @return void
     */
    public function testTransport(): void
    {
        $result = $this->CakeEmail->transport('Debug');
        $this->assertSame($this->CakeEmail, $result);
        $this->assertSame('Debug', $this->CakeEmail->transport());

        $result = $this->CakeEmail->transportClass();
        $this->assertInstanceOf('DebugTransport', $result);

        $this->expectException('SocketException');
        $this->CakeEmail->transport('Invalid');
        $this->CakeEmail->transportClass();
    }

    /**
     * testExtendTransport method
     *
     * @return void
     */
    public function testExtendTransport(): void
    {
        $this->expectException('SocketException');
        $this->CakeEmail->transport('Extend');
        $this->CakeEmail->transportClass();
    }

    /**
     * testSendRender method
     *
     * @return void
     */
    public function testSendRender(): void
    {
        $this->CakeEmail->reset();
        $this->CakeEmail->transport('debug');

        $this->CakeEmail->from('cake@cakephp.org');
        $this->CakeEmail->to(['you@cakephp.org' => 'You']);
        $this->CakeEmail->subject('My title');
        $this->CakeEmail->config(['empty']);
        $this->CakeEmail->template('default', 'default');
        $result = $this->CakeEmail->send();

        $this->assertStringContainsString('This email was sent using the CakePHP Framework', $result['message']);
        $this->assertStringContainsString('Message-ID: ', $result['headers']);
        $this->assertStringContainsString('To: ', $result['headers']);
    }

    /**
     * testSendRender both method
     *
     * @return void
     */
    public function testSendRenderBoth(): void
    {
        $this->CakeEmail->reset();
        $this->CakeEmail->transport('debug');

        $this->CakeEmail->from('cake@cakephp.org');
        $this->CakeEmail->to(['you@cakephp.org' => 'You']);
        $this->CakeEmail->subject('My title');
        $this->CakeEmail->config(['empty']);
        $this->CakeEmail->template('default', 'default');
        $this->CakeEmail->emailFormat('both');
        $result = $this->CakeEmail->send();

        $this->assertStringContainsString('Message-ID: ', $result['headers']);
        $this->assertStringContainsString('To: ', $result['headers']);

        $boundary = $this->CakeEmail->getBoundary();
        $this->assertStringContainsString('Content-Type: multipart/alternative; boundary="' . $boundary . '"', $result['headers']);

        $expected = "--$boundary\r\n" .
            "Content-Type: text/plain; charset=UTF-8\r\n" .
            "Content-Transfer-Encoding: 8bit\r\n" .
            "\r\n" .
            "\r\n" .
            "\r\n" .
            'This email was sent using the CakePHP Framework, https://cakephp.org.' .
            "\r\n" .
            "\r\n" .
            "--$boundary\r\n" .
            "Content-Type: text/html; charset=UTF-8\r\n" .
            "Content-Transfer-Encoding: 8bit\r\n" .
            "\r\n" .
            '<!DOCTYPE html';
        $this->assertStringStartsWith($expected, $result['message']);

        $expected = "</html>\r\n" .
            "\r\n" .
            "\r\n" .
            "--$boundary--\r\n";
        $this->assertStringEndsWith($expected, $result['message']);
    }

    /**
     * testSendRenderWithVars method
     *
     * @return void
     */
    public function testSendRenderWithVars(): void
    {
        $this->CakeEmail->reset();
        $this->CakeEmail->transport('debug');

        $this->CakeEmail->from('cake@cakephp.org');
        $this->CakeEmail->to(['you@cakephp.org' => 'You']);
        $this->CakeEmail->subject('My title');
        $this->CakeEmail->config(['empty']);
        $this->CakeEmail->template('custom', 'default');
        $this->CakeEmail->viewVars(['value' => 12345]);
        $result = $this->CakeEmail->send();

        $this->assertStringContainsString('Here is your value: 12345', $result['message']);
    }

    /**
     * testSendRenderThemed method
     *
     * @return void
     */
    public function testSendRenderThemed(): void
    {
        $this->CakeEmail->reset();
        $this->CakeEmail->transport('debug');

        $this->CakeEmail->from('cake@cakephp.org');
        $this->CakeEmail->to(['you@cakephp.org' => 'You']);
        $this->CakeEmail->subject('My title');
        $this->CakeEmail->config(['empty']);
        $this->CakeEmail->theme('TestTheme');
        $this->CakeEmail->template('themed', 'default');
        $result = $this->CakeEmail->send();

        $this->assertStringContainsString('In TestTheme', $result['message']);
        $this->assertStringContainsString('Message-ID: ', $result['headers']);
        $this->assertStringContainsString('To: ', $result['headers']);
        $this->assertStringContainsString('/theme/TestTheme/img/test.jpg', $result['message']);
    }

    /**
     * testSendRenderWithHelpers method
     *
     * @return void
     */
    public function testSendRenderWithHelpers(): void
    {
        $this->CakeEmail->reset();
        $this->CakeEmail->transport('debug');

        $timestamp = time();
        $this->CakeEmail->from('cake@cakephp.org');
        $this->CakeEmail->to(['you@cakephp.org' => 'You']);
        $this->CakeEmail->subject('My title');
        $this->CakeEmail->config(['empty']);
        $this->CakeEmail->template('custom_helper', 'default');
        $this->CakeEmail->viewVars(['time' => $timestamp]);

        $result = $this->CakeEmail->helpers(['Time']);
        $this->assertInstanceOf('CakeEmail', $result);

        $result = $this->CakeEmail->send();
        $this->assertTrue((bool)strpos($result['message'], 'Right now: ' . date('Y-m-d\TH:i:s\Z', $timestamp)));

        $result = $this->CakeEmail->helpers();
        $this->assertEquals(['Time'], $result);
    }

    /**
     * testSendRenderPlugin method
     *
     * @return void
     */
    public function testSendRenderPlugin(): void
    {
        App::build([
            'Plugin' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Plugin' . DS],
        ]);
        CakePlugin::load(['TestLegacyPlugin', 'TestTwoLegacyPlugin']);

        $this->CakeEmail->reset();
        $this->CakeEmail->transport('debug');
        $this->CakeEmail->from('cake@cakephp.org');
        $this->CakeEmail->to(['you@cakephp.org' => 'You']);
        $this->CakeEmail->subject('My title');
        $this->CakeEmail->config(['empty']);

        $result = $this->CakeEmail->template('TestLegacyPlugin.test_plugin_tpl', 'default')->send();
        $this->assertStringContainsString('Into TestLegacyPlugin.', $result['message']);
        $this->assertStringContainsString('This email was sent using the CakePHP Framework', $result['message']);

        $result = $this->CakeEmail->template('TestLegacyPlugin.test_plugin_tpl', 'TestLegacyPlugin.plug_default')->send();
        $this->assertStringContainsString('Into TestLegacyPlugin.', $result['message']);
        $this->assertStringContainsString('This email was sent using the TestLegacyPlugin.', $result['message']);

        $result = $this->CakeEmail->template('TestLegacyPlugin.test_plugin_tpl', 'plug_default')->send();
        $this->assertStringContainsString('Into TestLegacyPlugin.', $result['message']);
        $this->assertStringContainsString('This email was sent using the TestLegacyPlugin.', $result['message']);

        $this->CakeEmail->template(
            'TestLegacyPlugin.test_plugin_tpl',
            'TestTwoLegacyPlugin.default',
        );
        $result = $this->CakeEmail->send();
        $this->assertStringContainsString('Into TestLegacyPlugin.', $result['message']);
        $this->assertStringContainsString('This email was sent using TestTwoLegacyPlugin.', $result['message']);

        // test plugin template overridden by theme
        $this->CakeEmail->theme('TestTheme');
        $result = $this->CakeEmail->send();

        $this->assertStringContainsString('Into TestLegacyPlugin. (themed)', $result['message']);

        $this->CakeEmail->viewVars(['value' => 12345]);
        $result = $this->CakeEmail->template('custom', 'TestLegacyPlugin.plug_default')->send();
        $this->assertStringContainsString('Here is your value: 12345', $result['message']);
        $this->assertStringContainsString('This email was sent using the TestLegacyPlugin.', $result['message']);

        $this->expectException('MissingViewException');
        $this->CakeEmail->template('test_plugin_tpl', 'plug_default')->send();
    }
}
