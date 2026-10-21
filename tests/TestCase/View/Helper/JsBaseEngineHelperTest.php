<?php
/**
 * JsBaseEngineHelper Test Case
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

use Cake\Routing\Router;
use Cake\TestSuite\CakeTestCase;
use Cake\View\Helper\JsBaseEngineHelper;
use Cake\View\View;
use stdClass;

/**
 * JsEncodingObject
 *
 * @package       Cake.Test.Case.View.Helper
 */
class JsEncodingObject
{
    public $title;

    public $indexes;

    protected $_title = 'Old thing';

    private $__noshow = 'Never ever';
}

/**
 * Engine that exposes the protected option helpers of JsBaseEngineHelper.
 *
 * @package       Cake.Test.Case.View.Helper
 */
class JsBaseEngineTestHelper extends JsBaseEngineHelper
{
    protected $_optionMap = [
        'request' => [
            'complete' => 'success',
            'request' => 'beforeSend',
            'type' => 'dataType',
        ],
    ];

    protected $_callbackArguments = [
        'request' => [
            'beforeSend' => 'XMLHttpRequest',
            'success' => 'data, textStatus',
            'error' => '',
        ],
    ];

    /**
     * Calls _mapOptions() for the request method.
     *
     * @param array $options Options to map.
     * @return array
     */
    public function mapOptions($options = []): array
    {
        return $this->_mapOptions('request', $options);
    }

    /**
     * Calls _mapOptions() for the given method.
     *
     * @param string $method Method name.
     * @param array $options Options to map.
     * @return array
     */
    public function mapMethodOptions($method, $options): array
    {
        return $this->_mapOptions($method, $options);
    }

    /**
     * Calls _parseOptions().
     *
     * @param array $options Options to parse.
     * @param array $safe Keys that are not escaped.
     * @return string
     */
    public function parseOptions($options, $safe = []): string
    {
        return $this->_parseOptions($options, $safe);
    }

    /**
     * Calls _prepareCallbacks().
     *
     * @param string $method Method name.
     * @param array $options Options with callbacks.
     * @param array $callbacks Additional callback keys.
     * @return array
     */
    public function prepareCallbacks($method, $options, $callbacks = []): array
    {
        return $this->_prepareCallbacks($method, $options, $callbacks);
    }

    /**
     * Calls _processOptions().
     *
     * @param string $method Method name.
     * @param array $options Options to process.
     * @return string
     */
    public function processOptions($method, $options): string
    {
        return $this->_processOptions($method, $options);
    }

    /**
     * Calls _toQuerystring().
     *
     * @param array $parameters Parameters.
     * @return string
     */
    public function toQuerystring($parameters): string
    {
        return $this->_toQuerystring($parameters);
    }

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
 * JsBaseEngineHelper Test Case
 *
 * @package       Cake.Test.Case.View.Helper
 */
class JsBaseEngineHelperTest extends CakeTestCase
{
    public ?View $View = null;

    public ?JsBaseEngineTestHelper $JsEngine = null;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        Router::reload();
        $controller = null;
        $this->View = $this->getMock(View::class, ['append'], [&$controller]);
        $this->JsEngine = new JsBaseEngineTestHelper($this->View);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        $this->View = null;
        $this->JsEngine = null;
        Router::reload();

        parent::tearDown();
    }

    /**
     * test escape string skills
     *
     * @return void
     */
    public function testEscaping(): void
    {
        $result = $this->JsEngine->escape('');
        $expected = '';
        $this->assertEquals($expected, $result);

        $result = $this->JsEngine->escape('CakePHP' . "\n" . 'Rapid Development Framework');
        $expected = 'CakePHP\\nRapid Development Framework';
        $this->assertEquals($expected, $result);

        $result = $this->JsEngine->escape('CakePHP' . "\r\n" . 'Rapid Development Framework' . "\r" . 'For PHP');
        $expected = 'CakePHP\\r\\nRapid Development Framework\\rFor PHP';
        $this->assertEquals($expected, $result);

        $result = $this->JsEngine->escape('CakePHP: "Rapid Development Framework"');
        $expected = 'CakePHP: \\"Rapid Development Framework\\"';
        $this->assertEquals($expected, $result);

        $result = $this->JsEngine->escape("CakePHP: 'Rapid Development Framework'");
        $expected = "CakePHP: 'Rapid Development Framework'";
        $this->assertEquals($expected, $result);

        $result = $this->JsEngine->escape('my \\"string\\"');
        $expected = 'my \\\\\\"string\\\\\\"';
        $this->assertEquals($expected, $result);
    }

    /**
     * Test that escape() writes the short JSON escapes for tab, backspace and form feed,
     * and escapes the slash so that a string can not close a script element.
     *
     * @return void
     */
    public function testEscapeControlCharactersAndSlash(): void
    {
        $result = $this->JsEngine->escape("a\tb\x08c\x0Cd");
        $this->assertSame('a\tb\bc\fd', $result);

        $result = $this->JsEngine->escape('</script><script>alert(1)</script>');
        $this->assertSame('<\/script><script>alert(1)<\/script>', $result);
    }

    /**
     * Test that escape() writes two and three byte UTF-8 characters as \uXXXX escapes.
     *
     * @return void
     */
    public function testEscapeMultibyteCharacters(): void
    {
        $this->assertSame('caf\u00e9', $this->JsEngine->escape('café'));
        $this->assertSame('\u65e5\u672c\u8a9e', $this->JsEngine->escape('日本語'));
        $this->assertSame('\u00a9 2026 \u2014 ok', $this->JsEngine->escape('© 2026 — ok'));
    }

    /**
     * Test that escape() writes a UTF-8 character outside the Basic Multilingual Plane
     * as a JavaScript surrogate pair.
     *
     * @return void
     */
    public function testEscapeCharacterOutsideBasicMultilingualPlane(): void
    {
        // U+1F600 GRINNING FACE
        $this->assertSame('smile \ud83d\ude00', $this->JsEngine->escape("smile \xF0\x9F\x98\x80"));
        // U+10000 and U+10FFFF, the first and the last code point outside the BMP
        $this->assertSame('\ud800\udc00\udbff\udfff', $this->JsEngine->escape("\xF0\x90\x80\x80\xF4\x8F\xBF\xBF"));
        $this->assertSame(json_encode("a\xF0\x9F\x98\x80b"), '"' . $this->JsEngine->escape("a\xF0\x9F\x98\x80b") . '"');
    }

    /**
     * Test that escape() replaces a 4-byte sequence that is not valid UTF-8, because it
     * is overlong or past U+10FFFF, by a question mark.
     *
     * @return void
     */
    public function testEscapeInvalidFourByteSequence(): void
    {
        $this->assertSame('a?b', $this->JsEngine->escape("a\xF0\x80\x80\x80b"));
        $this->assertSame('a?b', $this->JsEngine->escape("a\xF4\x90\x80\x80b"));
    }

    /**
     * Test that escape() replaces the lead bytes 0xF8 to 0xFF, which RFC 3629 does not
     * allow, by a question mark. The continuation bytes after them are stray bytes.
     *
     * @return void
     */
    public function testEscapeInvalidLeadBytes(): void
    {
        $this->assertSame('a?????b', $this->JsEngine->escape("a\xF8\x88\x80\x80\x80b"));
        $this->assertSame('a??????b', $this->JsEngine->escape("a\xFC\x84\x80\x80\x80\x80b"));
        $this->assertSame('a?b?c', $this->JsEngine->escape("a\xFEb\xFFc"));
    }

    /**
     * Test that escape() keeps other C0 control characters as \u00XX escapes
     * instead of dropping them.
     *
     * @return void
     */
    public function testEscapeOtherControlCharacters(): void
    {
        $this->assertSame('a\u0001b\u001fc', $this->JsEngine->escape("a\x01b\x1Fc"));
    }

    /**
     * Test that escape() replaces a continuation byte without a lead byte by a question
     * mark, as it does for a sequence that is cut off, instead of dropping it.
     *
     * @return void
     */
    public function testEscapeStrayContinuationByte(): void
    {
        $this->assertSame('a?b', $this->JsEngine->escape("a\x80b"));
        $this->assertSame('\u00e9??', $this->JsEngine->escape("\xC3\xA9\xA9\xBF"));
    }

    /**
     * Test that escape() replaces a multibyte sequence that is cut off at the end
     * of the string by a question mark.
     *
     * @return void
     */
    public function testEscapeTruncatedMultibyteSequence(): void
    {
        $this->assertSame('abc?', $this->JsEngine->escape("abc\xC3"));
        $this->assertSame('abc?', $this->JsEngine->escape("abc\xE6\x97"));
        $this->assertSame('abc?', $this->JsEngine->escape("abc\xF0\x9F\x98"));
    }

    /**
     * Test that value() converts each PHP type to its JavaScript literal.
     *
     * @return void
     */
    public function testValue(): void
    {
        $this->assertSame('null', $this->JsEngine->value(null));
        $this->assertSame('true', $this->JsEngine->value(true));
        $this->assertSame('false', $this->JsEngine->value(false));
        $this->assertSame('1.50000000000', $this->JsEngine->value(1.5));
        $this->assertSame('-0.25000000000', $this->JsEngine->value(-0.25));
        $this->assertSame('"say \"hi\"\nnow"', $this->JsEngine->value("say \"hi\"\nnow"));
        $this->assertSame('"\u65e5\u672c"', $this->JsEngine->value('日本'));
        $this->assertSame('"0"', $this->JsEngine->value('0'));
        $this->assertSame('""', $this->JsEngine->value(''));
    }

    /**
     * Test that value() returns an integer as it is, which is also its JavaScript literal.
     *
     * @return void
     */
    public function testValueOfAnInteger(): void
    {
        $this->assertSame(42, $this->JsEngine->value(42));
        $this->assertSame(-7, $this->JsEngine->value(-7));
    }

    /**
     * Test that value() writes a float with a dot as the decimal separator, whatever
     * the LC_NUMERIC locale is, since JavaScript only accepts a dot.
     *
     * @return void
     */
    public function testValueOfAFloatIgnoresTheLocale(): void
    {
        $original = setlocale(LC_NUMERIC, '0');
        $this->skipIf(setlocale(LC_NUMERIC, 'de_DE.UTF-8', 'de_DE') === false, 'The German locale is not available.');
        try {
            $this->assertSame('1.50000000000', $this->JsEngine->value(1.5));
        } finally {
            setlocale(LC_NUMERIC, $original);
        }
    }

    /**
     * Test that value() encodes arrays and objects as JSON, and leaves strings
     * unquoted but escaped when $quoteString is false.
     *
     * @return void
     */
    public function testValueArraysObjectsAndUnquotedStrings(): void
    {
        $this->assertSame('[1,2,3]', $this->JsEngine->value([1, 2, 3]));
        $this->assertSame(
            '{"name":"Jos\u00e9","tags":["a","b"],"meta":{"active":true,"score":null}}',
            $this->JsEngine->value(['name' => 'José', 'tags' => ['a', 'b'], 'meta' => ['active' => true, 'score' => null]]),
        );

        $object = new stdClass();
        $object->id = 3;
        $object->title = 'Quote "this"';
        $this->assertSame('{"id":3,"title":"Quote \"this\""}', $this->JsEngine->value($object));

        $this->assertSame('say \"hi\"', $this->JsEngine->value('say "hi"', false));
        $this->assertSame('"say \"hi\""', $this->JsEngine->value('say "hi"', null));
    }

    /**
     * test prompt() creation
     *
     * @return void
     */
    public function testPrompt(): void
    {
        $result = $this->JsEngine->prompt('Hey, hey you', 'hi!');
        $expected = 'prompt("Hey, hey you", "hi!");';
        $this->assertEquals($expected, $result);

        $result = $this->JsEngine->prompt('"Hey"', '"hi"');
        $expected = 'prompt("\"Hey\"", "\"hi\"");';
        $this->assertEquals($expected, $result);

        $result = $this->JsEngine->prompt("Name?\nPlease");
        $this->assertSame('prompt("Name?\nPlease", "");', $result);
    }

    /**
     * test alert generation
     *
     * @return void
     */
    public function testAlert(): void
    {
        $result = $this->JsEngine->alert('Hey there');
        $expected = 'alert("Hey there");';
        $this->assertEquals($expected, $result);

        $result = $this->JsEngine->alert('"Hey"');
        $expected = 'alert("\"Hey\"");';
        $this->assertEquals($expected, $result);

        $result = $this->JsEngine->alert("Line 1\r\nGrüße");
        $this->assertSame('alert("Line 1\r\nGr\u00fc\u00dfe");', $result);
    }

    /**
     * test confirm generation
     *
     * @return void
     */
    public function testConfirm(): void
    {
        $result = $this->JsEngine->confirm('Are you sure?');
        $expected = 'confirm("Are you sure?");';
        $this->assertEquals($expected, $result);

        $result = $this->JsEngine->confirm('"Are you sure?"');
        $expected = 'confirm("\"Are you sure?\"");';
        $this->assertEquals($expected, $result);
    }

    /**
     * Test that confirmReturn() stores the answer and returns false from the
     * enclosing function when the user cancels.
     *
     * @return void
     */
    public function testConfirmReturn(): void
    {
        $result = $this->JsEngine->confirmReturn('Delete "post"?');
        $expected = "var _confirm = confirm(\"Delete \\\"post\\\"?\");if (!_confirm) {\n\treturn false;\n}";
        $this->assertSame($expected, $result);
    }

    /**
     * test Redirect
     *
     * @return void
     */
    public function testRedirect(): void
    {
        $result = $this->JsEngine->redirect(['controller' => 'posts', 'action' => 'view', 1]);
        $expected = 'window.location = "\/posts\/view\/1";';
        $this->assertEquals($expected, $result);

        $result = $this->JsEngine->redirect('https://example.com/path?a=1');
        $this->assertSame('window.location = "https:\/\/example.com\/path?a=1";', $result);
    }

    /**
     * Test that redirect() escapes the URL in its JavaScript string, so that a quote in
     * the URL cannot end the string and run code: the string literal decodes back to
     * the URL.
     *
     * @return void
     */
    public function testRedirectEscapesTheUrl(): void
    {
        $url = 'https://x/"+alert(1)+"';
        $result = $this->JsEngine->redirect($url);

        $this->assertStringStartsWith('window.location = "', $result);
        $this->assertStringEndsWith('";', $result);
        $literal = substr($result, strlen('window.location = '), -1);
        $this->assertSame($url, json_decode($literal), 'The string literal is ' . $literal);
    }

    /**
     * Test that object() encodes arrays, the public properties of objects, nested arrays
     * with numeric string keys and scalars as JSON, and puts the prefix and the postfix
     * around it.
     *
     * @return void
     */
    public function testObject(): void
    {
        $object = ['title' => 'New thing', 'indexes' => [5, 6, 7, 8]];
        $result = $this->JsEngine->object($object);
        $expected = '{"title":"New thing","indexes":[5,6,7,8]}';
        $this->assertEquals($expected, $result);

        $object = new JsEncodingObject();
        $object->title = 'New thing';
        $object->indexes = [5, 6, 7, 8];
        $result = $this->JsEngine->object($object);
        $this->assertEquals($expected, $result);

        $result = $this->JsEngine->object(['default' => 0]);
        $expected = '{"default":0}';
        $this->assertEquals($expected, $result);

        $result = $this->JsEngine->object([
            '2007' => [
                'Spring' => [
                    '1' => ['id' => 1, 'name' => 'Josh'], '2' => ['id' => 2, 'name' => 'Becky'],
                ],
                'Fall' => [
                    '1' => ['id' => 1, 'name' => 'Josh'], '2' => ['id' => 2, 'name' => 'Becky'],
                ],
            ],
            '2006' => [
                'Spring' => [
                    '1' => ['id' => 1, 'name' => 'Josh'], '2' => ['id' => 2, 'name' => 'Becky'],
                ],
                'Fall' => [
                    '1' => ['id' => 1, 'name' => 'Josh'], '2' => ['id' => 2, 'name' => 'Becky'],
                ],
            ],
        ]);
        $expected = '{"2007":{"Spring":{"1":{"id":1,"name":"Josh"},"2":{"id":2,"name":"Becky"}},"Fall":{"1":{"id":1,"name":"Josh"},"2":{"id":2,"name":"Becky"}}},"2006":{"Spring":{"1":{"id":1,"name":"Josh"},"2":{"id":2,"name":"Becky"}},"Fall":{"1":{"id":1,"name":"Josh"},"2":{"id":2,"name":"Becky"}}}}';
        $this->assertEquals($expected, $result);

        foreach (['true' => true, 'false' => false, 'null' => null] as $expected => $data) {
            $result = $this->JsEngine->object($data);
            $this->assertEquals($expected, $result);
        }

        $object = ['title' => 'New thing', 'indexes' => [5, 6, 7, 8], 'object' => ['inner' => ['value' => 1]]];
        $result = $this->JsEngine->object($object, ['prefix' => 'PREFIX', 'postfix' => 'POSTFIX']);
        $this->assertSame('PREFIX{"title":"New thing","indexes":[5,6,7,8],"object":{"inner":{"value":1}}}POSTFIX', $result);
    }

    /**
     * Test that object() of a string that is not valid UTF-8 still gives a JSON object
     * with the key, instead of the empty string that json_encode() failing leaves:
     * each invalid byte is replaced by U+FFFD REPLACEMENT CHARACTER.
     *
     * @return void
     */
    public function testObjectWithInvalidUtf8(): void
    {
        $result = $this->JsEngine->object(['name' => "a\x80b", 'ok' => "caf\xC3\xA9"]);
        $this->assertSame('{"name":"a\ufffdb","ok":"caf\u00e9"}', $result);

        $decoded = json_decode($result, true);
        $this->assertIsArray($decoded, 'Not JSON: ' . var_export($result, true));
        $this->assertSame(['name' => "a\u{FFFD}b", 'ok' => "caf\u{E9}"], $decoded);
    }

    /**
     * Test that object() escapes quotes, line breaks, slashes and non-ASCII characters
     * in strings, and keeps numbers, booleans and null as JSON literals.
     *
     * @return void
     */
    public function testObjectEscapesStringsAndKeepsScalars(): void
    {
        $data = [
            'quote' => 'He said "hi"',
            'lines' => "one\ntwo\r\n",
            'url' => '</script>',
            'name' => 'Grüße 日本',
            'int' => -3,
            'float' => 2.5,
            'yes' => true,
            'no' => false,
            'none' => null,
            'empty' => [],
        ];
        $expected = '{"quote":"He said \"hi\"","lines":"one\ntwo\r\n","url":"<\/script>",'
            . '"name":"Gr\u00fc\u00dfe \u65e5\u672c","int":-3,"float":2.5,"yes":true,"no":false,'
            . '"none":null,"empty":[]}';
        $this->assertSame($expected, $this->JsEngine->object($data));
    }

    /**
     * test Mapping of options.
     *
     * @return void
     */
    public function testOptionMapping(): void
    {
        $result = $this->JsEngine->mapOptions();
        $this->assertSame([], $result);

        $result = $this->JsEngine->mapOptions(['foo' => 'bar', 'baz' => 'sho']);
        $this->assertEquals(['foo' => 'bar', 'baz' => 'sho'], $result);

        $result = $this->JsEngine->mapOptions(['complete' => 'myFunc', 'type' => 'json', 'update' => '#element']);
        $this->assertEquals(['success' => 'myFunc', 'dataType' => 'json', 'update' => '#element'], $result);

        $result = $this->JsEngine->mapOptions(['success' => 'myFunc', 'dataType' => 'json', 'update' => '#element']);
        $this->assertEquals(['success' => 'myFunc', 'dataType' => 'json', 'update' => '#element'], $result);
    }

    /**
     * Test that _mapOptions() returns the options unchanged for a method without an option map.
     *
     * @return void
     */
    public function testOptionMappingForMethodWithoutMap(): void
    {
        $options = ['complete' => 'myFunc', 'type' => 'json'];
        $this->assertSame($options, $this->JsEngine->mapMethodOptions('drag', $options));
    }

    /**
     * test that option parsing escapes strings and saves what is supposed to be saved.
     *
     * @return void
     */
    public function testOptionParsing(): void
    {
        $result = $this->JsEngine->parseOptions(['url' => '/posts/view/1', 'key' => 1]);
        $expected = 'key:1, url:"\\/posts\\/view\\/1"';
        $this->assertEquals($expected, $result);

        $result = $this->JsEngine->parseOptions(['url' => '/posts/view/1', 'success' => 'doSuccess'], ['success']);
        $expected = 'success:doSuccess, url:"\\/posts\\/view\\/1"';
        $this->assertEquals($expected, $result);

        $result = $this->JsEngine->parseOptions(['zIndex' => 1.5, 'async' => false, 'data' => ['a' => 1], 'title' => 'a "b"']);
        $this->assertSame('async:false, data:{"a":1}, title:"a \"b\"", zIndex:1.50000000000', $result);
    }

    /**
     * Test that _prepareCallbacks() wraps each non-empty callback in a function with
     * the arguments of the method, plus any extra callback keys without arguments,
     * and leaves empty callbacks out.
     *
     * @return void
     */
    public function testPrepareCallbacks(): void
    {
        $options = [
            'beforeSend' => 'start();',
            'success' => 'show(data);',
            'error' => '',
            'onDone' => 'done();',
            'url' => '/posts',
        ];
        $result = $this->JsEngine->prepareCallbacks('request', $options, ['onDone']);
        $expected = [
            'beforeSend' => 'function (XMLHttpRequest) {start();}',
            'success' => 'function (data, textStatus) {show(data);}',
            'onDone' => 'function () {done();}',
            'url' => '/posts',
        ];
        $this->assertSame($expected, $result);

        $result = $this->JsEngine->prepareCallbacks('drag', ['onStart' => 'go();', 'success' => 'x();'], 'onStart');
        $this->assertSame(['onStart' => 'function () {go();}', 'success' => 'x();'], $result);
    }

    /**
     * Test that _prepareCallbacks() leaves the callbacks untouched and removes the
     * wrapCallbacks option when wrapCallbacks is false.
     *
     * @return void
     */
    public function testPrepareCallbacksWithoutWrapping(): void
    {
        $options = ['success' => 'function (r) {show(r);}', 'wrapCallbacks' => false];
        $result = $this->JsEngine->prepareCallbacks('request', $options);
        $this->assertSame(['success' => 'function (r) {show(r);}'], $result);

        $options = ['success' => 'show(data);', 'wrapCallbacks' => true];
        $result = $this->JsEngine->prepareCallbacks('request', $options);
        $this->assertSame(['success' => 'function (data, textStatus) {show(data);}'], $result);
    }

    /**
     * Test that _processOptions() maps the option names, wraps the callbacks and
     * writes the callbacks unquoted and everything else as JavaScript values.
     *
     * @return void
     */
    public function testProcessOptions(): void
    {
        $options = [
            'complete' => 'show(data);',
            'request' => 'start();',
            'type' => 'json',
            'update' => '#target',
            'async' => true,
        ];
        $result = $this->JsEngine->processOptions('request', $options);
        $expected = 'async:true, beforeSend:function (XMLHttpRequest) {start();}, dataType:"json", '
            . 'success:function (data, textStatus) {show(data);}, update:"#target"';
        $this->assertSame($expected, $result);
    }

    /**
     * Test that _toQuerystring() joins the parameters with & in their order.
     *
     * @return void
     */
    public function testToQuerystring(): void
    {
        $this->assertSame('', $this->JsEngine->toQuerystring([]));
        $this->assertSame('name=jim', $this->JsEngine->toQuerystring(['name' => 'jim']));
        $this->assertSame(
            'name=jim&height=185cm&0=first',
            $this->JsEngine->toQuerystring(['name' => 'jim', 'height' => '185cm', 0 => 'first']),
        );
    }

    /**
     * Test that _toQuerystring() joins the keys and the values as they are given, so
     * that values encoded by the caller are not encoded a second time.
     *
     * @return void
     */
    public function testToQuerystringDoesNotEncodeTheValues(): void
    {
        $this->assertSame("q=a b&n=caf\xC3\xA9", $this->JsEngine->toQuerystring(['q' => 'a b', 'n' => "caf\xC3\xA9"]));
        $this->assertSame('q=a%26b%20c', $this->JsEngine->toQuerystring(['q' => rawurlencode('a&b c')]));
    }

    /**
     * Test that _toQuerystring() writes a nested array with brackets, leaves a null value
     * out, and writes true and false as 1 and 0, as Router::url() does.
     *
     * @return void
     */
    public function testToQuerystringNestedArraysAndOtherTypes(): void
    {
        $this->assertSame(
            'filter[status]=open&filter[tags][0]=a b&page=2',
            $this->JsEngine->toQuerystring(['filter' => ['status' => 'open', 'tags' => ['a b']], 'page' => 2]),
        );
        $this->assertSame('yes=1&no=0', $this->JsEngine->toQuerystring(['yes' => true, 'no' => false, 'none' => null]));
    }

    /**
     * Test that _processOptions() leaves an empty callback out, instead of writing the
     * invalid JavaScript `error:` without a value.
     *
     * @return void
     */
    public function testProcessOptionsLeavesEmptyCallbacksOut(): void
    {
        $result = $this->JsEngine->processOptions('request', ['error' => '', 'complete' => 'show(data);']);
        $this->assertSame('success:function (data, textStatus) {show(data);}', $result);

        $result = $this->JsEngine->processOptions('request', ['error' => null, 'complete' => 'show', 'wrapCallbacks' => false]);
        $this->assertSame('success:show', $result);
    }
}
