<?php
/**
 * EmailComponentTest file
 *
 * Series of tests for email component.
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
 * @package       Cake.Test.Case.Controller.Component
 * @since         CakePHP(tm) v 1.2.0.5347
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Test\TestCase\Legacy\Controller\Component;

use Cake\Controller\Controller;
use Cake\Core\App;
use Cake\Core\Configure;
use Cake\TestSuite\CakeTestCase;
use Cake\Utility\ClassRegistry;
use DebugCompTransport;

App::uses('DebugCompTransport', 'Network/Email');

/**
 * EmailTestController class
 *
 * @package       Cake.Test.Case.Controller.Component
 */
class EmailTestController extends Controller
{
    /**
     * uses property
     *
     * @var mixed
     */
    public $uses = null;

    /**
     * components property
     *
     * @var array
     */
    public $components = ['Session', 'EmailTest'];
}

/**
 * EmailTest class
 *
 * @package       Cake.Test.Case.Controller.Component
 */
class EmailComponentTest extends CakeTestCase
{
    /**
     * Controller property
     *
     * @var EmailTestController
     */
    public $Controller;

    /**
     * name property
     *
     * @var string
     */
    public $name = 'Email';

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        App::build([
            'Controller/Component' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Controller' . DS . 'Component' . DS],
            'Lib' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Lib' . DS],
        ]);

        Configure::write('App.encoding', 'UTF-8');

        $this->Controller = new EmailTestController();
        $this->Controller->Components->init($this->Controller);
        $this->Controller->EmailTest->initialize($this->Controller, []);

        DebugCompTransport::$sentDate = date(DATE_RFC2822);

        App::build([
            'View' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'View' . DS],
        ]);
    }

    /**
     * testTemplates method
     *
     * @return void
     */
    public function testTemplates(): void
    {
        ClassRegistry::flush();

        $this->Controller->EmailTest->to = 'postmaster@example.com';
        $this->Controller->EmailTest->from = 'noreply@example.com';
        $this->Controller->EmailTest->subject = 'Cake SMTP test';
        $this->Controller->EmailTest->replyTo = 'noreply@example.com';

        $this->Controller->EmailTest->delivery = 'DebugComp';
        $this->Controller->EmailTest->messageId = false;

        $date = DebugCompTransport::$sentDate;
        $header = <<<HEADBLOC
To: postmaster@example.com
From: noreply@example.com
Subject: Cake SMTP test
Header:

From: noreply@example.com
Reply-To: noreply@example.com
X-Mailer: CakePHP Email Component
Date: $date
MIME-Version: 1.0
Content-Type: {CONTENTTYPE}
Content-Transfer-Encoding: 8bitMessage:


HEADBLOC;

        $this->Controller->EmailTest->layout = 'default';
        $this->Controller->EmailTest->template = 'default';
        $this->Controller->set('title_for_layout', 'Email Test');

        $text = <<<TEXTBLOC

This is the body of the message

This email was sent using the CakePHP Framework, https://cakephp.org.
TEXTBLOC;

        $html = <<<HTMLBLOC
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN">

<html>
<head>
	<title>Email Test</title>
</head>

<body>
	<p> This is the body of the message</p><p> </p>
	<p>This email was sent using the <a href="https://cakephp.org">CakePHP Framework</a></p>
</body>
</html>
HTMLBLOC;

        $this->Controller->EmailTest->sendAs = 'text';
        $expected = '<pre>' . str_replace('{CONTENTTYPE}', 'text/plain; charset=UTF-8', $header) . $text . "\n" . '</pre>';
        $this->assertTrue($this->Controller->EmailTest->send('This is the body of the message'));
        $this->assertTextEquals($expected, DebugCompTransport::$lastEmail);

        $this->Controller->EmailTest->sendAs = 'html';
        $expected = '<pre>' . str_replace('{CONTENTTYPE}', 'text/html; charset=UTF-8', $header) . $html . "\n" . '</pre>';
        $this->assertTrue($this->Controller->EmailTest->send('This is the body of the message'));
        $this->assertTextEquals($expected, DebugCompTransport::$lastEmail);

        $this->Controller->EmailTest->sendAs = 'both';
        $expected = str_replace('{CONTENTTYPE}', 'multipart/alternative; boundary="{boundary}"', $header);
        $expected .= "--{boundary}\n" .
            'Content-Type: text/plain; charset=UTF-8' . "\n" .
            'Content-Transfer-Encoding: 8bit' . "\n\n" .
            $text .
            "\n\n" .
            '--{boundary}' . "\n" .
            'Content-Type: text/html; charset=UTF-8' . "\n" .
            'Content-Transfer-Encoding: 8bit' . "\n\n" .
            $html .
            "\n\n\n" .
            '--{boundary}--' . "\n";

        $expected = '<pre>' . $expected . '</pre>';

        $this->assertTrue($this->Controller->EmailTest->send('This is the body of the message'));
        $this->assertTextEquals(
            $expected,
            preg_replace('/[a-z0-9]{32}/i', '{boundary}', DebugCompTransport::$lastEmail),
        );

        $html = <<<HTMLBLOC
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN">

<html>
<head>
	<title>Email Test</title>
</head>

<body>
	<p> This is the body of the message</p><p> </p>
	<p>This email was sent using the CakePHP Framework</p>
</body>
</html>

HTMLBLOC;

        $this->Controller->EmailTest->sendAs = 'html';
        $expected = '<pre>' . str_replace('{CONTENTTYPE}', 'text/html; charset=UTF-8', $header) . $html . '</pre>';
        $this->assertTrue($this->Controller->EmailTest->send('This is the body of the message', 'default', 'thin'));
        $this->assertTextEquals($expected, DebugCompTransport::$lastEmail);
    }

    /**
     * test that elements used in email templates get helpers.
     *
     * @return void
     */
    public function testTemplateNestedElements(): void
    {
        $this->Controller->EmailTest->to = 'postmaster@example.com';
        $this->Controller->EmailTest->from = 'noreply@example.com';
        $this->Controller->EmailTest->subject = 'Cake SMTP test';
        $this->Controller->EmailTest->replyTo = 'noreply@example.com';

        $this->Controller->EmailTest->delivery = 'DebugComp';
        $this->Controller->EmailTest->messageId = false;
        $this->Controller->EmailTest->layout = 'default';
        $this->Controller->EmailTest->template = 'nested_element';
        $this->Controller->EmailTest->sendAs = 'html';
        $this->Controller->helpers = ['Html'];

        $this->Controller->EmailTest->send();
        $result = DebugCompTransport::$lastEmail;
        $this->assertMatchesRegularExpression('/Test/', $result);
        $this->assertMatchesRegularExpression('/http\:\/\/example\.com/', $result);
    }

    /**
     * testMessageRetrievalWithTemplate method
     *
     * @return void
     */
    public function testMessageRetrievalWithTemplate(): void
    {
        App::build([
            'View' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'View' . DS],
        ]);

        $this->Controller->set('value', 22091985);
        $this->Controller->set('title_for_layout', 'EmailTest');

        $this->Controller->EmailTest->to = 'postmaster@example.com';
        $this->Controller->EmailTest->from = 'noreply@example.com';
        $this->Controller->EmailTest->subject = 'Cake Debug Test';
        $this->Controller->EmailTest->replyTo = 'noreply@example.com';
        $this->Controller->EmailTest->layout = 'default';
        $this->Controller->EmailTest->template = 'custom';

        $this->Controller->EmailTest->delivery = 'DebugComp';

        $text = <<<TEXTBLOC

Here is your value: 22091985
This email was sent using the CakePHP Framework, https://cakephp.org.
TEXTBLOC;

        $html = <<<HTMLBLOC
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN">

<html>
<head>
	<title>EmailTest</title>
</head>

<body>
	<p>Here is your value: <b>22091985</b></p>

	<p>This email was sent using the <a href="https://cakephp.org">CakePHP Framework</a></p>
</body>
</html>
HTMLBLOC;

        $this->Controller->EmailTest->sendAs = 'both';
        $this->assertTrue($this->Controller->EmailTest->send());
        $this->assertTextEquals($this->Controller->EmailTest->textMessage, $text);
        $this->assertTextEquals($this->Controller->EmailTest->htmlMessage, $html);

        $this->Controller->EmailTest->sendAs = 'text';
        $this->assertTrue($this->Controller->EmailTest->send());
        $this->assertTextEquals($this->Controller->EmailTest->textMessage, $text);
        $this->assertNull($this->Controller->EmailTest->htmlMessage);

        $this->Controller->EmailTest->sendAs = 'html';
        $this->assertTrue($this->Controller->EmailTest->send());
        $this->assertNull($this->Controller->EmailTest->textMessage);
        $this->assertTextEquals($this->Controller->EmailTest->htmlMessage, $html);
    }

    /**
     * testMessageRetrievalWithHelper method
     *
     * @return void
     */
    public function testMessageRetrievalWithHelper(): void
    {
        App::build([
            'View' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'View' . DS],
        ]);

        $timestamp = time();
        $this->Controller->set('time', $timestamp);
        $this->Controller->set('title_for_layout', 'EmailTest');
        $this->Controller->helpers = ['Time'];

        $this->Controller->EmailTest->to = 'postmaster@example.com';
        $this->Controller->EmailTest->from = 'noreply@example.com';
        $this->Controller->EmailTest->subject = 'Cake Debug Test';
        $this->Controller->EmailTest->replyTo = 'noreply@example.com';
        $this->Controller->EmailTest->layout = 'default';
        $this->Controller->EmailTest->template = 'custom_helper';
        $this->Controller->EmailTest->sendAs = 'text';
        $this->Controller->EmailTest->delivery = 'DebugComp';

        $this->assertTrue($this->Controller->EmailTest->send());
        $this->assertTrue((bool)strpos($this->Controller->EmailTest->textMessage, 'Right now: ' . date('Y-m-d\TH:i:s\Z', $timestamp)));
    }
}
