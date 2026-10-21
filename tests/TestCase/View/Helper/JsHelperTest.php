<?php
/**
 * JsHelper Test Case
 *
 * TestCase for the JsHelper
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
 * @since         CakePHP(tm) v 1.3
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Test\TestCase\View\Helper;

use Cake\Core\Configure;
use Cake\Network\CakeRequest;
use Cake\TestSuite\CakeTestCase;
use Cake\View\Helper\FormHelper;
use Cake\View\Helper\HtmlHelper;
use Cake\View\Helper\JsBaseEngineHelper;
use Cake\View\Helper\JsHelper;
use Cake\View\View;
use stdClass;

/**
 * OptionEngineHelper
 *
 * @package       Cake.Test.Case.View.Helper
 */
class OptionEngineHelper extends JsBaseEngineHelper
{
    protected $_optionMap = [
        'request' => [
            'complete' => 'success',
            'request' => 'beforeSend',
            'type' => 'dataType',
        ],
    ];

    public function get($selector): mixed
    {
        return null;
    }

    public function event($type, $callback, $options = []): mixed
    {
        return null;
    }

    public function domReady($functionBody): mixed
    {
        return null;
    }

    public function each($callback): mixed
    {
        return null;
    }

    public function effect($name, $options = []): mixed
    {
        return null;
    }

    public function request($url, $options = []): mixed
    {
        return null;
    }

    public function drag($options = []): mixed
    {
        return null;
    }

    public function drop($options = []): mixed
    {
        return null;
    }

    public function sortable($options = []): mixed
    {
        return null;
    }

    public function slider($options = []): mixed
    {
        return null;
    }

    public function serializeForm($options = []): mixed
    {
        return null;
    }
}

/**
 * JsHelper TestCase.
 *
 * @package       Cake.Test.Case.View.Helper
 */
class JsHelperTest extends CakeTestCase
{
    /**
     * Regexp for CDATA start block
     *
     * @var string
     */
    public $cDataStart = 'preg:/^\/\/<!\[CDATA\[[\n\r]*/';

    /**
     * Regexp for CDATA end block
     *
     * @var string
     */
    public $cDataEnd = 'preg:/[^\]]*\]\]\>[\s\r\n]*/';

    public ?View $View = null;

    public ?JsHelper $Js = null;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        Configure::write('Asset.timestamp', false);

        $controller = null;
        $this->View = $this->getMock(View::class, ['append'], [&$controller]);
        $this->Js = new JsHelper($this->View, 'Option');
        $request = new CakeRequest(null, false);
        $this->Js->request = $request;
        $this->Js->Html = new HtmlHelper($this->View);
        $this->Js->Html->request = $request;
        $this->Js->Form = new FormHelper($this->View);

        $this->Js->Form->request = $request;
        $this->Js->Form->Html = $this->Js->Html;
        $this->Js->OptionEngine = new OptionEngineHelper($this->View);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        $this->View = null;
        $this->Js = null;

        parent::tearDown();
    }

    /**
     * Switches $this->Js to a mocked engine.
     *
     * @return void
     */
    protected function _useMock(): void
    {
        $request = new CakeRequest(null, false);

        $this->Js = new JsHelper($this->View, ['TestJs']);
        $this->Js->TestJsEngine = $this->getMock(JsBaseEngineHelper::class, [], [$this->View]);
        $this->Js->request = $request;
        $this->Js->Html = new HtmlHelper($this->View);
        $this->Js->Html->request = $request;
        $this->Js->Form = new FormHelper($this->View);
        $this->Js->Form->request = $request;
        $this->Js->Form->Html = new HtmlHelper($this->View);
    }

    /**
     * test object construction
     *
     * @return void
     */
    public function testConstruction(): void
    {
        $js = new JsHelper($this->View);
        $this->assertEquals(['Html', 'Form', 'JqueryEngine'], $js->helpers);

        $js = new JsHelper($this->View, ['mootools']);
        $this->assertEquals(['Html', 'Form', 'mootoolsEngine'], $js->helpers);

        $js = new JsHelper($this->View, 'prototype');
        $this->assertEquals(['Html', 'Form', 'prototypeEngine'], $js->helpers);

        $js = new JsHelper($this->View, 'TestDispatchPlugin.Dojo');
        $this->assertEquals(['Html', 'Form', 'TestDispatchPlugin.DojoEngine'], $js->helpers);
    }

    /**
     * test that methods dispatch internally and to the engine class
     *
     * @return void
     */
    public function testMethodDispatching(): void
    {
        $warningTriggered = false;
        $warningMessage = '';
        set_error_handler(function ($errno, $errstr) use (&$warningTriggered, &$warningMessage) {
            $warningTriggered = true;
            $warningMessage = $errstr;

            return true;
        }, E_WARNING | E_USER_WARNING);

        try {
            $this->_useMock();

            $this->Js->TestJsEngine
                ->expects($this->once())
                ->method('event')
                ->with('click', 'callback');

            $this->Js->event('click', 'callback');

            $this->Js->TestJsEngine = new stdClass();
            $this->Js->someMethodThatSurelyDoesntExist();
        } finally {
            restore_error_handler();
        }

        $this->assertTrue($warningTriggered, 'Expected warning was not triggered');
        $this->assertSame('JsHelper:: Missing Method someMethodThatSurelyDoesntExist is undefined', $warningMessage);
    }

    /**
     * Test that method dispatching for events respects buffer parameters and bufferedMethods Lists.
     *
     * @return void
     */
    public function testEventDispatchWithBuffering(): void
    {
        $this->_useMock();

        $this->Js->TestJsEngine->bufferedMethods = ['event', 'sortables'];
        $this->Js->TestJsEngine->expects($this->exactly(3))
            ->method('event')
            ->will($this->returnValue('This is an event call'));

        $this->Js->event('click', 'foo');
        $result = $this->Js->getBuffer();
        $this->assertEquals(1, count($result));
        $this->assertEquals('This is an event call', $result[0]);

        $result = $this->Js->event('click', 'foo', ['buffer' => false]);
        $buffer = $this->Js->getBuffer();
        $this->assertTrue(empty($buffer));
        $this->assertEquals('This is an event call', $result);

        $result = $this->Js->event('click', 'foo', false);
        $buffer = $this->Js->getBuffer();
        $this->assertTrue(empty($buffer));
        $this->assertEquals('This is an event call', $result);
    }

    /**
     * Test that method dispatching for effects respects buffer parameters and bufferedMethods Lists.
     *
     * @return void
     */
    public function testEffectDispatchWithBuffering(): void
    {
        $this->_useMock();
        $this->Js->TestJsEngine->expects($this->exactly(4))
            ->method('effect')
            ->will($this->returnValue('I am not buffered.'));

        $result = $this->Js->effect('slideIn');
        $buffer = $this->Js->getBuffer();
        $this->assertTrue(empty($buffer));
        $this->assertEquals('I am not buffered.', $result);

        $result = $this->Js->effect('slideIn', true);
        $buffer = $this->Js->getBuffer();
        $this->assertNull($result);
        $this->assertEquals(1, count($buffer));
        $this->assertEquals('I am not buffered.', $buffer[0]);

        $result = $this->Js->effect('slideIn', ['speed' => 'slow'], true);
        $buffer = $this->Js->getBuffer();
        $this->assertNull($result);
        $this->assertEquals(1, count($buffer));
        $this->assertEquals('I am not buffered.', $buffer[0]);

        $result = $this->Js->effect('slideIn', ['speed' => 'slow', 'buffer' => true]);
        $buffer = $this->Js->getBuffer();
        $this->assertNull($result);
        $this->assertEquals(1, count($buffer));
        $this->assertEquals('I am not buffered.', $buffer[0]);
    }

    /**
     * test that writeScripts generates scripts inline.
     *
     * @return void
     */
    public function testWriteScriptsNoFile(): void
    {
        $this->_useMock();
        $this->Js->buffer('one = 1;');
        $this->Js->buffer('two = 2;');
        $result = $this->Js->writeBuffer(['onDomReady' => false, 'cache' => false, 'clear' => false]);
        $expected = [
            '<script',
            "one = 1;\ntwo = 2;",
            '/script',
        ];
        $this->assertTags($result, $expected);

        $this->Js->TestJsEngine->expects($this->atLeastOnce())->method('domReady');
        $this->Js->writeBuffer(['onDomReady' => true, 'cache' => false, 'clear' => false]);

        $this->View->expects($this->once())
            ->method('append')
            ->with('script', $this->matchesRegularExpression('/one\s\=\s1;\ntwo\s\=\s2;/'));
        $this->Js->writeBuffer(['onDomReady' => false, 'inline' => false, 'cache' => false]);
    }

    /**
     * With the html5 setting of HtmlHelper off, the script blocks of the buffer have their type and CDATA
     *
     * @return void
     */
    public function testWriteScriptsXhtml(): void
    {
        $this->_useMock();
        $request = $this->Js->Html->request;
        $this->Js->Html = new HtmlHelper($this->View, ['html5' => false]);
        $this->Js->Html->request = $request;
        $this->Js->buffer('one = 1;');
        $result = $this->Js->writeBuffer(['onDomReady' => false, 'cache' => false]);
        $expected = [
            'script' => ['type' => 'text/javascript'],
            $this->cDataStart,
            'one = 1;',
            $this->cDataEnd,
            '/script',
        ];
        $this->assertTags($result, $expected);
    }

    /**
     * test that writing the buffer with inline = false includes a script tag.
     *
     * @return void
     */
    public function testWriteBufferNotInline(): void
    {
        $this->Js->set('foo', 1);

        $this->View->expects($this->once())
            ->method('append')
            ->with('script', $this->matchesRegularExpression('#<script>window.app \= \{"foo"\:1\}\;<\/script>#'));

        $this->Js->writeBuffer(['onDomReady' => false, 'inline' => false, 'safe' => false]);
    }

    /**
     * test that writeBuffer() sets domReady = false when the request is done by XHR.
     * Including a domReady() when in XHR can cause issues as events aren't triggered by some libraries
     *
     * @return void
     */
    public function testWriteBufferAndXhr(): void
    {
        $this->_useMock();
        $requestWith = null;
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            $requestWith = $_SERVER['HTTP_X_REQUESTED_WITH'];
        }
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';

        $this->Js->buffer('alert("test");');
        $this->Js->TestJsEngine->expects($this->never())->method('domReady');
        $this->Js->writeBuffer();

        unset($_SERVER['HTTP_X_REQUESTED_WITH']);
        if ($requestWith !== null) {
            $_SERVER['HTTP_X_REQUESTED_WITH'] = $requestWith;
        }
    }

    /**
     * test that writeScripts makes files, and puts the events into them.
     *
     * @return void
     */
    public function testWriteScriptsInFile(): void
    {
        $this->skipIf(!is_writable(WWW_ROOT . 'js'), 'webroot/js is not Writable, script caching test has been skipped.');

        Configure::write('Cache.disable', false);
        $this->Js->request->webroot = '/';
        $this->Js->JsBaseEngine = $this->getMock(JsBaseEngineHelper::class, [], [$this->View]);
        $this->Js->buffer('one = 1;');
        $this->Js->buffer('two = 2;');
        $result = $this->Js->writeBuffer(['onDomReady' => false, 'cache' => true]);
        $expected = [
            'script' => ['src' => 'preg:/(.)*\.js/'],
        ];
        $this->assertTags($result, $expected);
        preg_match('/src="(.*\.js)"/', $result, $filename);
        $this->assertTrue(file_exists(WWW_ROOT . $filename[1]));
        $contents = file_get_contents(WWW_ROOT . $filename[1]);
        $this->assertMatchesRegularExpression('/one\s=\s1;\ntwo\s=\s2;/', $contents);
        if (file_exists(WWW_ROOT . $filename[1])) {
            unlink(WWW_ROOT . $filename[1]);
        }

        Configure::write('Cache.disable', true);
        $this->Js->buffer('one = 1;');
        $this->Js->buffer('two = 2;');
        $result = $this->Js->writeBuffer(['onDomReady' => false, 'cache' => true]);
        $this->assertMatchesRegularExpression('/one\s=\s1;\ntwo\s=\s2;/', $result);
        $this->assertFalse(file_exists(WWW_ROOT . $filename[1]));
    }

    /**
     * test link()
     *
     * @return void
     */
    public function testLinkWithMock(): void
    {
        $this->_useMock();

        $options = ['update' => '#content'];

        $callSequence = [];

        $this->Js->TestJsEngine
            ->expects($this->once())
            ->method('get')
            ->willReturnCallback(function () use (&$callSequence) {
                $callSequence[] = 'get';

                return $this->Js->TestJsEngine;
            });

        $this->Js->TestJsEngine
            ->expects($this->once())
            ->method('request')
            ->with('/posts/view/1', $options)
            ->willReturnCallback(function () use (&$callSequence) {
                $callSequence[] = 'request';

                return '--ajax code--';
            });

        $this->Js->TestJsEngine
            ->expects($this->once())
            ->method('event')
            ->with('click', '--ajax code--', $options + ['buffer' => null])
            ->willReturnCallback(function () use (&$callSequence) {
                $callSequence[] = 'event';

                return '';
            });

        $result = $this->Js->link('test link', '/posts/view/1', $options);

        $this->assertEquals(['get', 'request', 'event'], $callSequence);

        $expected = [
            'a' => ['id' => 'preg:/link-\d+/', 'href' => '/posts/view/1'],
            'test link',
            '/a',
        ];
        $this->assertTags($result, $expected);
    }

    /**
     * test link with a mock and confirmation
     *
     * @return void
     */
    public function testLinkWithMockAndConfirm(): void
    {
        $this->_useMock();

        $options = [
            'confirm' => 'Are you sure?',
            'update' => '#content',
            'class' => 'my-class',
            'id' => 'custom-id',
            'escape' => false,
        ];
        $this->Js->TestJsEngine->expects($this->once())
            ->method('confirmReturn')
            ->with($options['confirm'])
            ->will($this->returnValue('--confirm script--'));

        $this->Js->TestJsEngine->expects($this->once())
            ->method('request')
            ->with('/posts/view/1');

        $this->Js->TestJsEngine->expects($this->once())
            ->method('event')
            ->with('click', '--confirm script--');

        $result = $this->Js->link('test link »', '/posts/view/1', $options);
        $expected = [
            'a' => ['id' => $options['id'], 'class' => $options['class'], 'href' => '/posts/view/1'],
            'test link »',
            '/a',
        ];
        $this->assertTags($result, $expected);
    }

    /**
     * test link passing on htmlAttributes
     *
     * @return void
     */
    public function testLinkWithAribtraryAttributes(): void
    {
        $this->_useMock();

        $options = ['id' => 'something', 'htmlAttributes' => ['arbitrary' => 'value', 'batman' => 'robin']];
        $result = $this->Js->link('test link', '/posts/view/1', $options);
        $expected = [
            'a' => ['id' => $options['id'], 'href' => '/posts/view/1', 'arbitrary' => 'value',
                'batman' => 'robin'],
            'test link',
            '/a',
        ];
        $this->assertTags($result, $expected);
    }

    /**
     * test that link() and no buffering returns an <a> and <script> tags.
     *
     * @return void
     */
    public function testLinkWithNoBuffering(): void
    {
        $this->_useMock();

        $methodCalls = [];

        $this->Js->TestJsEngine->expects($this->any())
            ->method('get')
            ->willReturn(null);

        $this->Js->TestJsEngine->expects($this->once())
            ->method('request')
            ->with('/posts/view/1', ['update' => '#content'])
            ->willReturnCallback(function () use (&$methodCalls) {
                $methodCalls[] = 'request';

                return 'ajax code';
            });

        $this->Js->TestJsEngine->expects($this->once())
            ->method('event')
            ->willReturnCallback(function () use (&$methodCalls) {
                $methodCalls[] = 'event';

                return '-event handler-';
            });

        $options = ['update' => '#content', 'buffer' => false];
        $result = $this->Js->link('test link', '/posts/view/1', $options);

        $this->assertContains('request', $methodCalls);
        $this->assertContains('event', $methodCalls);

        $expected = [
            'a' => ['id' => 'preg:/link-\d+/', 'href' => '/posts/view/1'],
            'test link',
            '/a',
            '<script',
            '-event handler-',
            '/script',
        ];
        $this->assertTags($result, $expected);
    }

    /**
     * test link with buffering off and safe on.
     *
     * @return void
     */
    public function testLinkWithNoBufferingAndSafe(): void
    {
        $this->_useMock();

        $methodCalls = [];

        $this->Js->TestJsEngine->expects($this->any())
            ->method('get')
            ->willReturn(null);

        $this->Js->TestJsEngine->expects($this->once())
            ->method('request')
            ->with('/posts/view/1', ['update' => '#content'])
            ->willReturnCallback(function () use (&$methodCalls) {
                $methodCalls[] = 'request';

                return 'ajax code';
            });

        $this->Js->TestJsEngine->expects($this->once())
            ->method('event')
            ->willReturnCallback(function () use (&$methodCalls) {
                $methodCalls[] = 'event';

                return '-event handler-';
            });

        $options = ['update' => '#content', 'buffer' => false, 'safe' => false];
        $result = $this->Js->link('test link', '/posts/view/1', $options);

        $this->assertContains('request', $methodCalls);
        $this->assertContains('event', $methodCalls);

        $expected = [
            'a' => ['id' => 'preg:/link-\d+/', 'href' => '/posts/view/1'],
            'test link',
            '/a',
            '<script',
            '-event handler-',
            '/script',
        ];
        $this->assertTags($result, $expected);
    }

    /**
     * test submit() with a Mock to check Engine method calls
     *
     * @return void
     */
    public function testSubmitWithMock(): void
    {
        $this->_useMock();

        $options = ['update' => '#content', 'id' => 'test-submit', 'style' => 'margin: 0'];

        $callSequence = [];

        $this->Js->TestJsEngine->expects($this->once())
            ->method('get')
            ->willReturnCallback(function () use (&$callSequence) {
                $callSequence[] = 'get';

                return $this->Js->TestJsEngine;
            });

        $this->Js->TestJsEngine->expects($this->once())
            ->method('serializeForm')
            ->willReturnCallback(function () use (&$callSequence) {
                $callSequence[] = 'serializeForm';

                return 'serialize-code';
            });

        $this->Js->TestJsEngine->expects($this->once())
            ->method('request')
            ->willReturnCallback(function () use (&$callSequence) {
                $callSequence[] = 'request';

                return 'ajax-code';
            });

        $params = [
            'update' => $options['update'], 'data' => 'serialize-code',
            'method' => 'post', 'dataExpression' => true, 'buffer' => null,
        ];

        $this->Js->TestJsEngine
            ->expects($this->once())
            ->method('event')
            ->with('click', 'ajax-code', $params)
            ->willReturnCallback(function () use (&$callSequence) {
                $callSequence[] = 'event';

                return '';
            });

        $result = $this->Js->submit('Save', $options);

        $this->assertEquals(['get', 'serializeForm', 'request', 'event'], $callSequence);

        $expected = [
            'div' => ['class' => 'submit'],
            'input' => ['type' => 'submit', 'id' => $options['id'], 'value' => 'Save', 'style' => 'margin: 0'],
            '/div',
        ];
        $this->assertTags($result, $expected);
    }

    /**
     * test submit() with a mock
     *
     * @return void
     */
    public function testSubmitWithMockRequestParams(): void
    {
        $this->_useMock();

        $callSequence = [];

        $this->Js->TestJsEngine->expects($this->once())
            ->method('get')
            ->willReturnCallback(function () use (&$callSequence) {
                $callSequence[] = 'get';

                return $this->Js->TestJsEngine;
            });

        $this->Js->TestJsEngine->expects($this->once())
            ->method('serializeForm')
            ->willReturnCallback(function () use (&$callSequence) {
                $callSequence[] = 'serializeForm';

                return 'serialize-code';
            });

        $requestParams = [
            'update' => '#content',
            'data' => 'serialize-code',
            'method' => 'post',
            'dataExpression' => true,
        ];

        $this->Js
            ->TestJsEngine
            ->expects($this->once())
            ->method('request')
            ->with('/custom/url', $requestParams)
            ->willReturnCallback(function () use (&$callSequence) {
                $callSequence[] = 'request';

                return 'ajax-code';
            });

        $params = [
            'update' => '#content', 'data' => 'serialize-code',
            'method' => 'post', 'dataExpression' => true, 'buffer' => null,
        ];

        $this->Js
            ->TestJsEngine
            ->expects($this->once())
            ->method('event')
            ->with('click', 'ajax-code', $params)
            ->willReturnCallback(function () use (&$callSequence) {
                $callSequence[] = 'event';

                return '';
            });

        $options = ['update' => '#content', 'id' => 'test-submit', 'url' => '/custom/url'];
        $result = $this->Js->submit('Save', $options);

        $this->assertEquals(['get', 'serializeForm', 'request', 'event'], $callSequence);

        $expected = [
            'div' => ['class' => 'submit'],
            'input' => ['type' => 'submit', 'id' => $options['id'], 'value' => 'Save'],
            '/div',
        ];
        $this->assertTags($result, $expected);
    }

    /**
     * test that no buffer works with submit() and that parameters are leaking into the script tag.
     *
     * @return void
     */
    public function testSubmitWithNoBuffer(): void
    {
        $this->_useMock();
        $options = ['update' => '#content', 'id' => 'test-submit', 'buffer' => false, 'safe' => false];

        $methodSequence = [];

        $this->Js->TestJsEngine->expects($this->any())
            ->method('get')
            ->willReturnCallback(function () use (&$methodSequence) {
                $methodSequence[] = 'get';

                return null;
            });

        $this->Js->TestJsEngine->expects($this->once())
            ->method('serializeForm')
            ->willReturnCallback(function () use (&$methodSequence) {
                $methodSequence[] = 'serializeForm';

                return 'serialize-code';
            });

        $this->Js->TestJsEngine->expects($this->once())
            ->method('request')
            ->willReturnCallback(function () use (&$methodSequence) {
                $methodSequence[] = 'request';

                return 'ajax-code';
            });

        $params = [
            'update' => $options['update'], 'data' => 'serialize-code',
            'method' => 'post', 'dataExpression' => true, 'buffer' => false,
        ];

        $this->Js->TestJsEngine->expects($this->once())
            ->method('event')
            ->with('click', 'ajax-code', $params)
            ->willReturnCallback(function () use (&$methodSequence) {
                $methodSequence[] = 'event';

                return 'event-handler';
            });

        $result = $this->Js->submit('Save', $options);

        $this->assertEquals(['get', 'serializeForm', 'request', 'event'], $methodSequence);

        $expected = [
            'div' => ['class' => 'submit'],
            'input' => ['type' => 'submit', 'id' => $options['id'], 'value' => 'Save'],
            '/div',
            '<script',
            'event-handler',
            '/script',
        ];
        $this->assertTags($result, $expected);
    }

    /**
     * Test that Object::Object() is not breaking json output in JsHelper
     *
     * @return void
     */
    public function testObjectPassThrough(): void
    {
        $result = $this->Js->object(['one' => 'first', 'two' => 'second']);
        $expected = '{"one":"first","two":"second"}';
        $this->assertEquals($expected, $result);
    }

    /**
     * Test that inherited Helper::value() is overwritten in JsHelper::value()
     * and calls JsBaseEngineHelper::value().
     *
     * @return void
     */
    public function testValuePassThrough(): void
    {
        $result = $this->Js->value('string "quote"', true);
        $expected = '"string \"quote\""';
        $this->assertEquals($expected, $result);
    }

    /**
     * test set()'ing variables to the JavaScript buffer and controlling the output var name.
     *
     * @return void
     */
    public function testSet(): void
    {
        $this->Js->set('loggedIn', true);
        $this->Js->set(['height' => 'tall', 'color' => 'purple']);
        $result = $this->Js->getBuffer();
        $expected = 'window.app = {"loggedIn":true,"height":"tall","color":"purple"};';
        $this->assertEquals($expected, $result[0]);

        $this->Js->set('loggedIn', true);
        $this->Js->set(['height' => 'tall', 'color' => 'purple']);
        $this->Js->setVariable = 'WICKED';
        $result = $this->Js->getBuffer();
        $expected = 'window.WICKED = {"loggedIn":true,"height":"tall","color":"purple"};';
        $this->assertEquals($expected, $result[0]);

        $this->Js->set('loggedIn', true);
        $this->Js->set(['height' => 'tall', 'color' => 'purple']);
        $this->Js->setVariable = 'Application.variables';
        $result = $this->Js->getBuffer();
        $expected = 'Application.variables = {"loggedIn":true,"height":"tall","color":"purple"};';
        $this->assertEquals($expected, $result[0]);
    }

    /**
     * test that vars set with Js->set() go to the top of the buffered scripts list.
     *
     * @return void
     */
    public function testSetVarsAtTopOfBufferedScripts(): void
    {
        $this->Js->set(['height' => 'tall', 'color' => 'purple']);
        $this->Js->alert('hey you!', ['buffer' => true]);
        $this->Js->confirm('Are you sure?', ['buffer' => true]);
        $result = $this->Js->getBuffer(false);

        $expected = 'window.app = {"height":"tall","color":"purple"};';
        $this->assertEquals($expected, $result[0]);
        $this->assertEquals('alert("hey you!");', $result[1]);
        $this->assertEquals('confirm("Are you sure?");', $result[2]);
    }
}
