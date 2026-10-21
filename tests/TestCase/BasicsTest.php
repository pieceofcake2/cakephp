<?php
/**
 * BasicsTest file
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
 * @package       Cake.Test.Case
 * @since         CakePHP(tm) v 1.2.0.4206
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Test\TestCase;

use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Log\CakeLog;
use Cake\Network\CakeResponse;
use Cake\TestSuite\CakeTestCase;
use Cake\Utility\Debugger;
use Cake\Utility\Folder;
use InvalidArgumentException;
use stdClass;

require_once CAKE . 'basics.php';

/**
 * BasicsTest class
 *
 * @package       Cake.Test.Case
 */
class BasicsTest extends CakeTestCase
{
    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        App::build([
            'Locale' => [CORE_TESTS . DS . 'test_app' . DS . 'resources' . DS . 'locales' . DS],
        ]);
    }

    /**
     * test the array_diff_key compatibility function.
     *
     * @return void
     */
    public function testArrayDiffKey(): void
    {
        $one = ['one' => 1, 'two' => 2, 'three' => 3];
        $two = ['one' => 'one', 'two' => 'two'];
        $result = array_diff_key($one, $two);
        $expected = ['three' => 3];
        $this->assertEquals($expected, $result);

        $one = ['one' => ['value', 'value-two'], 'two' => 2, 'three' => 3];
        $two = ['two' => 'two'];
        $result = array_diff_key($one, $two);
        $expected = ['one' => ['value', 'value-two'], 'three' => 3];
        $this->assertEquals($expected, $result);

        $one = ['one' => null, 'two' => 2, 'three' => '', 'four' => 0];
        $two = ['two' => 'two'];
        $result = array_diff_key($one, $two);
        $expected = ['one' => null, 'three' => '', 'four' => 0];
        $this->assertEquals($expected, $result);

        $one = ['minYear' => null, 'maxYear' => null, 'separator' => '-', 'interval' => 1, 'monthNames' => true];
        $two = ['minYear' => null, 'maxYear' => null, 'separator' => '-', 'interval' => 1, 'monthNames' => true];
        $result = array_diff_key($one, $two);
        $this->assertSame([], $result);
    }

    /**
     * testHttpBase method
     *
     * @return void
     */
    public function testEnv(): void
    {
        $server = $_SERVER;
        $env = $_ENV;

        $_SERVER['HTTP_HOST'] = 'localhost';
        $this->assertEquals(env('HTTP_BASE'), '.localhost');

        $_SERVER['HTTP_HOST'] = 'com.ar';
        $this->assertEquals(env('HTTP_BASE'), '.com.ar');

        $_SERVER['HTTP_HOST'] = 'example.ar';
        $this->assertEquals(env('HTTP_BASE'), '.example.ar');

        $_SERVER['HTTP_HOST'] = 'example.com';
        $this->assertEquals(env('HTTP_BASE'), '.example.com');

        $_SERVER['HTTP_HOST'] = 'www.example.com';
        $this->assertEquals(env('HTTP_BASE'), '.example.com');

        $_SERVER['HTTP_HOST'] = 'subdomain.example.com';
        $this->assertEquals(env('HTTP_BASE'), '.example.com');

        $_SERVER['HTTP_HOST'] = 'example.com.ar';
        $this->assertEquals(env('HTTP_BASE'), '.example.com.ar');

        $_SERVER['HTTP_HOST'] = 'www.example.com.ar';
        $this->assertEquals(env('HTTP_BASE'), '.example.com.ar');

        $_SERVER['HTTP_HOST'] = 'subdomain.example.com.ar';
        $this->assertEquals(env('HTTP_BASE'), '.example.com.ar');

        $_SERVER['HTTP_HOST'] = 'double.subdomain.example.com';
        $this->assertEquals(env('HTTP_BASE'), '.subdomain.example.com');

        $_SERVER['HTTP_HOST'] = 'double.subdomain.example.com.ar';
        $this->assertEquals(env('HTTP_BASE'), '.subdomain.example.com.ar');

        $_SERVER = $_ENV = [];

        $_SERVER['SCRIPT_NAME'] = '/a/test/test.php';
        $this->assertEquals(env('SCRIPT_NAME'), '/a/test/test.php');

        $_SERVER = $_ENV = [];

        $_ENV['CGI_MODE'] = 'BINARY';
        $_ENV['SCRIPT_URL'] = '/a/test/test.php';
        $this->assertEquals(env('SCRIPT_NAME'), '/a/test/test.php');

        $_SERVER = $_ENV = [];

        $this->assertFalse(env('HTTPS'));

        $_SERVER['HTTPS'] = 'on';
        $this->assertTrue(env('HTTPS'));

        $_SERVER['HTTPS'] = '1';
        $this->assertTrue(env('HTTPS'));

        $_SERVER['HTTPS'] = 'I am not empty';
        $this->assertTrue(env('HTTPS'));

        $_SERVER['HTTPS'] = 1;
        $this->assertTrue(env('HTTPS'));

        $_SERVER['HTTPS'] = 'off';
        $this->assertFalse(env('HTTPS'));

        $_SERVER['HTTPS'] = false;
        $this->assertFalse(env('HTTPS'));

        $_SERVER['HTTPS'] = '';
        $this->assertFalse(env('HTTPS'));

        $_SERVER = [];

        $_ENV['SCRIPT_URI'] = 'https://domain.test/a/test.php';
        $this->assertTrue(env('HTTPS'));

        $_ENV['SCRIPT_URI'] = 'http://domain.test/a/test.php';
        $this->assertFalse(env('HTTPS'));

        $_SERVER = $_ENV = [];

        $this->assertNull(env('TEST_ME'));

        $_ENV['TEST_ME'] = 'a';
        $this->assertEquals(env('TEST_ME'), 'a');

        $_SERVER['TEST_ME'] = 'b';
        $this->assertEquals(env('TEST_ME'), 'b');

        unset($_ENV['TEST_ME']);
        $this->assertEquals(env('TEST_ME'), 'b');

        $_SERVER = $server;
        $_ENV = $env;
    }

    /**
     * Test h()
     *
     * @return void
     */
    public function testRaw(): void
    {
        // As it is, unlike h()
        $this->assertSame('<p>Which one</p>', raw('<p>Which one</p>'));
        $this->assertSame('', raw(null));
        $this->assertSame('12', raw(12));
        $this->assertSame('1.5', raw(1.5));

        $object = new class {
            public function __toString(): string
            {
                return '<b>bold</b>';
            }
        };
        $this->assertSame('<b>bold</b>', raw($object));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('raw() takes a string, not array.');
        raw(['<p>']);
    }

    public function testH(): void
    {
        $string = '<foo>';
        $result = h($string);
        $this->assertEquals('&lt;foo&gt;', $result);

        $in = ['this & that', '<p>Which one</p>'];
        $result = h($in);
        $expected = ['this &amp; that', '&lt;p&gt;Which one&lt;/p&gt;'];
        $this->assertEquals($expected, $result);

        $string = '<foo> & &nbsp;';
        $result = h($string);
        $this->assertEquals('&lt;foo&gt; &amp; &amp;nbsp;', $result);

        $string = '<foo> & &nbsp;';
        $result = h($string, false);
        $this->assertEquals('&lt;foo&gt; &amp; &nbsp;', $result);

        $string = '<foo> & &nbsp;';
        $result = h($string, 'UTF-8');
        $this->assertEquals('&lt;foo&gt; &amp; &amp;nbsp;', $result);

        $arr = ['<foo>', '&nbsp;'];
        $result = h($arr);
        $expected = [
            '&lt;foo&gt;',
            '&amp;nbsp;',
        ];
        $this->assertEquals($expected, $result);

        $arr = ['<foo>', '&nbsp;'];
        $result = h($arr, false);
        $expected = [
            '&lt;foo&gt;',
            '&nbsp;',
        ];
        $this->assertEquals($expected, $result);

        $arr = ['f' => '<foo>', 'n' => '&nbsp;'];
        $result = h($arr, false);
        $expected = [
            'f' => '&lt;foo&gt;',
            'n' => '&nbsp;',
        ];
        $this->assertEquals($expected, $result);

        // Test that boolean values are not converted to strings
        $result = h(false);
        $this->assertFalse($result);

        $arr = ['foo' => false, 'bar' => true];
        $result = h($arr);
        $this->assertFalse($result['foo']);
        $this->assertTrue($result['bar']);

        $obj = new stdClass();
        $result = h($obj);
        $this->assertEquals('(object)stdClass', $result);

        $obj = new CakeResponse(['body' => 'Body content']);
        $result = h($obj);
        $this->assertEquals('Body content', $result);

        $result = h(null);
        $this->assertEquals('', $result);
    }

    /**
     * Test am()
     *
     * @return void
     */
    public function testAm(): void
    {
        $result = am(['one', 'two'], 2, 3, 4);
        $expected = ['one', 'two', 2, 3, 4];
        $this->assertEquals($expected, $result);

        $result = am(['one' => [2, 3], 'two' => ['foo']], ['one' => [4, 5]]);
        $expected = ['one' => [4, 5], 'two' => ['foo']];
        $this->assertEquals($expected, $result);
    }

    /**
     * test cache()
     *
     * @return void
     */
    public function testCache(): void
    {
        $_cacheDisable = Configure::read('Cache.disable');
        $this->skipIf($_cacheDisable, 'Cache is disabled, skipping cache() tests.');

        Configure::write('Cache.disable', true);
        $result = cache('basics_test', 'simple cache write');
        $this->assertNull($result);

        $result = cache('basics_test');
        $this->assertNull($result);

        Configure::write('Cache.disable', false);
        $result = cache('basics_test', 'simple cache write');
        $this->assertTrue((bool)$result);
        $this->assertTrue(file_exists(CACHE . 'basics_test'));

        $result = cache('basics_test');
        $this->assertEquals('simple cache write', $result);
        unlink(CACHE . 'basics_test');

        cache('basics_test', 'expired', '+1 second');
        sleep(2);
        $result = cache('basics_test', null, '+1 second');
        $this->assertNull($result);

        Configure::write('Cache.disable', $_cacheDisable);
    }

    /**
     * test clearCache()
     *
     * @return void
     */
    public function testClearCache(): void
    {
        $cacheOff = Configure::read('Cache.disable');
        $this->skipIf($cacheOff, 'Cache is disabled, skipping clearCache() tests.');

        cache('views' . DS . 'basics_test.cache', 'simple cache write');
        $this->assertTrue(file_exists(CACHE . 'views' . DS . 'basics_test.cache'));

        cache('views' . DS . 'basics_test_2.cache', 'simple cache write 2');
        $this->assertTrue(file_exists(CACHE . 'views' . DS . 'basics_test_2.cache'));

        cache('views' . DS . 'basics_test_3.cache', 'simple cache write 3');
        $this->assertTrue(file_exists(CACHE . 'views' . DS . 'basics_test_3.cache'));

        $result = clearCache(['basics_test', 'basics_test_2'], 'views', '.cache');
        $this->assertTrue($result);
        $this->assertFalse(file_exists(CACHE . 'views' . DS . 'basics_test.cache'));
        $this->assertFalse(file_exists(CACHE . 'views' . DS . 'basics_test.cache'));
        $this->assertTrue(file_exists(CACHE . 'views' . DS . 'basics_test_3.cache'));

        $result = clearCache(null, 'views', '.cache');
        $this->assertTrue($result);
        $this->assertFalse(file_exists(CACHE . 'views' . DS . 'basics_test_3.cache'));

        // Different path from views and with prefix
        cache('models' . DS . 'basics_test.cache', 'simple cache write');
        $this->assertTrue(file_exists(CACHE . 'models' . DS . 'basics_test.cache'));

        cache('models' . DS . 'basics_test_2.cache', 'simple cache write 2');
        $this->assertTrue(file_exists(CACHE . 'models' . DS . 'basics_test_2.cache'));

        cache('models' . DS . 'basics_test_3.cache', 'simple cache write 3');
        $this->assertTrue(file_exists(CACHE . 'models' . DS . 'basics_test_3.cache'));

        $result = clearCache('basics', 'models', '.cache');
        $this->assertTrue($result);
        $this->assertFalse(file_exists(CACHE . 'models' . DS . 'basics_test.cache'));
        $this->assertFalse(file_exists(CACHE . 'models' . DS . 'basics_test_2.cache'));
        $this->assertFalse(file_exists(CACHE . 'models' . DS . 'basics_test_3.cache'));

        // checking if empty files were not removed
        $emptyExists = file_exists(CACHE . 'views' . DS . 'empty');
        if (!$emptyExists) {
            cache('views' . DS . 'empty', '');
        }
        cache('views' . DS . 'basics_test.php', 'simple cache write');
        $this->assertTrue(file_exists(CACHE . 'views' . DS . 'basics_test.php'));
        $this->assertTrue(file_exists(CACHE . 'views' . DS . 'empty'));

        $result = clearCache();
        $this->assertTrue($result);
        $this->assertTrue(file_exists(CACHE . 'views' . DS . 'empty'));
        $this->assertFalse(file_exists(CACHE . 'views' . DS . 'basics_test.php'));
        if (!$emptyExists) {
            unlink(CACHE . 'views' . DS . 'empty');
        }
    }

    /**
     * test __()
     *
     * @return void
     */
    public function testTranslate(): void
    {
        Configure::write('Config.language', 'rule_1_po');

        $result = __('Plural Rule 1');
        $expected = 'Plural Rule 1 (translated)';
        $this->assertEquals($expected, $result);

        $result = __('Plural Rule 1 (from core)');
        $expected = 'Plural Rule 1 (from core translated)';
        $this->assertEquals($expected, $result);

        $result = __('Some string with %s', 'arguments');
        $expected = 'Some string with arguments';
        $this->assertEquals($expected, $result);

        $result = __('Some string with %s %s', 'multiple', 'arguments');
        $expected = 'Some string with multiple arguments';
        $this->assertEquals($expected, $result);

        $result = __('Some string with %s and a null argument', null);
        $expected = 'Some string with %s and a null argument';
        $this->assertEquals($expected, $result);

        $result = __('Some string with multiple %s%s, first being null', null, 'arguments');
        $expected = 'Some string with multiple arguments, first being null';
        $this->assertEquals($expected, $result);

        $result = __('Some string with %s %s', ['multiple', 'arguments']);
        $expected = 'Some string with multiple arguments';
        $this->assertEquals($expected, $result);

        $result = __('Testing %2$s %1$s', 'order', 'different');
        $expected = 'Testing different order';
        $this->assertEquals($expected, $result);

        $result = __('Testing %2$s %1$s', ['order', 'different']);
        $expected = 'Testing different order';
        $this->assertEquals($expected, $result);

        $result = __('Testing %.2f number', 1.2345);
        $expected = 'Testing 1.23 number';
        $this->assertEquals($expected, $result);
    }

    /**
     * testTranslatePercent
     *
     * @return void
     */
    public function testTranslatePercent(): void
    {
        $result = __('%s are 100% real fruit', 'Apples');
        $expected = 'Apples are 100% real fruit';
        $this->assertEquals($expected, $result, 'Percent sign at end of word should be considered literal');

        $result = __('%s are %d% real fruit', 'Apples', 100);
        $expected = 'Apples are 100% real fruit';
        $this->assertEquals($expected, $result, 'A digit marker should not be misinterpreted');

        $result = __('%s are %s% real fruit', 'Apples', 100);
        $expected = 'Apples are 100% real fruit';
        $this->assertEquals($expected, $result, 'A string marker should not be misinterpreted');

        $result = __('%nonsense %s', 'Apples');
        $expected = '%nonsense Apples';
        $this->assertEquals($expected, $result, 'A percent sign at the start of the string should be considered literal');

        $result = __('%s are awesome%', 'Apples');
        $expected = 'Apples are awesome%';
        $this->assertEquals($expected, $result, 'A percent sign at the end of the string should be considered literal');

        $result = __('%2$d %1$s entered the bowl', 'Apples', 2);
        $expected = '2 Apples entered the bowl';
        $this->assertEquals($expected, $result, 'Positional replacement markers should not be misinterpreted');

        $result = __('%.2f% of all %s agree', 99.44444, 'Cats');
        $expected = '99.44% of all Cats agree';
        $this->assertEquals($expected, $result, 'significant-digit placeholder should not be misinterpreted');
    }

    /**
     * testTranslateWithFormatSpecifiers
     *
     * @return void
     */
    public function testTranslateWithFormatSpecifiers(): void
    {
        $expected = 'Check,   one, two, three';
        $result = __('Check, %+10s, three', 'one, two');
        $this->assertEquals($expected, $result);

        $expected = 'Check,    +1, two, three';
        $result = __('Check, %+5d, two, three', 1);
        $this->assertEquals($expected, $result);

        $expected = 'Check, @@one, two, three';
        $result = __('Check, %\'@+10s, three', 'one, two');
        $this->assertEquals($expected, $result);

        $expected = 'Check, one, two  , three';
        $result = __('Check, %-10s, three', 'one, two');
        $this->assertEquals($expected, $result);

        $expected = 'Check, one, two##, three';
        $result = __('Check, %\'#-10s, three', 'one, two');
        $this->assertEquals($expected, $result);

        $expected = 'Check,   one, two, three';
        $result = __d('default', 'Check, %+10s, three', 'one, two');
        $this->assertEquals($expected, $result);

        $expected = 'Check, @@one, two, three';
        $result = __d('default', 'Check, %\'@+10s, three', 'one, two');
        $this->assertEquals($expected, $result);

        $expected = 'Check, one, two  , three';
        $result = __d('default', 'Check, %-10s, three', 'one, two');
        $this->assertEquals($expected, $result);

        $expected = 'Check, one, two##, three';
        $result = __d('default', 'Check, %\'#-10s, three', 'one, two');
        $this->assertEquals($expected, $result);
    }

    /**
     * testTranslateDomainPluralWithFormatSpecifiers
     *
     * @return void
     */
    public function testTranslateDomainPluralWithFormatSpecifiers(): void
    {
        $result = __dn('core', '%+5d item.', '%+5d items.', 1, 1);
        $expected = '   +1 item.';
        $this->assertEquals($expected, $result);

        $result = __dn('core', '%-5d item.', '%-5d items.', 10, 10);
        $expected = '10    items.';
        $this->assertEquals($expected, $result);

        $result = __dn('core', '%\'#+5d item.', '%\'*+5d items.', 1, 1);
        $expected = '###+1 item.';
        $this->assertEquals($expected, $result);

        $result = __dn('core', '%\'#+5d item.', '%\'*+5d items.', 90, 90);
        $expected = '**+90 items.';
        $this->assertEquals($expected, $result);

        $result = __dn('core', '%\'#+5d item.', '%\'*+5d items.', 9000, 9000);
        $expected = '+9000 items.';
        $this->assertEquals($expected, $result);
    }

    /**
     * test testTranslatePluralWithFormatSpecifiers
     *
     * @return void
     */
    public function testTranslatePluralWithFormatSpecifiers(): void
    {
        Configure::write('Config.language', 'rule_1_po');

        $result = __n('%-5d = 1', '%-5d = 0 or > 1', 10);
        $expected = '%-5d = 0 or > 1 (translated)';
        $this->assertEquals($expected, $result);
    }

    /**
     * test testTranslateDomainCategoryWithFormatSpecifiers
     *
     * @return void
     */
    public function testTranslateDomainCategoryWithFormatSpecifiers(): void
    {
        Configure::write('Config.language', 'rule_1_po');

        $result = __dc('default', '%+10s world', 6, 'hello');
        $expected = '     hello world';
        $this->assertEquals($expected, $result);

        $result = __dc('default', '%-10s world', 6, 'hello');
        $expected = 'hello      world';
        $this->assertEquals($expected, $result);

        $result = __dc('default', '%\'@-10s world', 6, 'hello');
        $expected = 'hello@@@@@ world';
        $this->assertEquals($expected, $result);
    }

    /**
     * test testTranslateDomainCategoryPluralWithFormatSpecifiers
     *
     * @return void
     */
    public function testTranslateDomainCategoryPluralWithFormatSpecifiers(): void
    {
        Configure::write('Config.language', 'rule_1_po');

        $result = __dcn('default', '%-5d = 1', '%-5d = 0 or > 1', 0, 6);
        $expected = '%-5d = 0 or > 1 (translated)';
        $this->assertEquals($expected, $result);

        $result = __dcn('default', '%-5d = 1', '%-5d = 0 or > 1', 1, 6);
        $expected = '%-5d = 1 (translated)';
        $this->assertEquals($expected, $result);
    }

    /**
     * test testTranslateCategoryWithFormatSpecifiers
     *
     * @return void
     */
    public function testTranslateCategoryWithFormatSpecifiers(): void
    {
        $result = __c('Some string with %+10s', 6, 'arguments');
        $expected = 'Some string with  arguments';
        $this->assertEquals($expected, $result);

        $result = __c('Some string with %-10s: args', 6, 'arguments');
        $expected = 'Some string with arguments : args';
        $this->assertEquals($expected, $result);

        $result = __c('Some string with %\'*-10s: args', 6, 'arguments');
        $expected = 'Some string with arguments*: args';
        $this->assertEquals($expected, $result);
    }

    /**
     * test __n()
     *
     * @return void
     */
    public function testTranslatePlural(): void
    {
        Configure::write('Config.language', 'rule_1_po');

        $result = __n('%d = 1', '%d = 0 or > 1', 0);
        $expected = '%d = 0 or > 1 (translated)';
        $this->assertEquals($expected, $result);

        $result = __n('%d = 1', '%d = 0 or > 1', 1);
        $expected = '%d = 1 (translated)';
        $this->assertEquals($expected, $result);

        $result = __n('%d = 1 (from core)', '%d = 0 or > 1 (from core)', 2);
        $expected = '%d = 0 or > 1 (from core translated)';
        $this->assertEquals($expected, $result);

        $result = __n('%d item.', '%d items.', 1, 1);
        $expected = '1 item.';
        $this->assertEquals($expected, $result);

        $result = __n('%d item for id %s', '%d items for id %s', 2, 2, '1234');
        $expected = '2 items for id 1234';
        $this->assertEquals($expected, $result);

        $result = __n('%d item for id %s', '%d items for id %s', 2, [2, '1234']);
        $expected = '2 items for id 1234';
        $this->assertEquals($expected, $result);
    }

    /**
     * test __d()
     *
     * @return void
     */
    public function testTranslateDomain(): void
    {
        Configure::write('Config.language', 'rule_1_po');

        $result = __d('default', 'Plural Rule 1');
        $expected = 'Plural Rule 1 (translated)';
        $this->assertEquals($expected, $result);

        $result = __d('core', 'Plural Rule 1');
        $expected = 'Plural Rule 1';
        $this->assertEquals($expected, $result);

        $result = __d('core', 'Plural Rule 1 (from core)');
        $expected = 'Plural Rule 1 (from core translated)';
        $this->assertEquals($expected, $result);

        $result = __d('core', 'Some string with %s', 'arguments');
        $expected = 'Some string with arguments';
        $this->assertEquals($expected, $result);

        $result = __d('core', 'Some string with %s %s', 'multiple', 'arguments');
        $expected = 'Some string with multiple arguments';
        $this->assertEquals($expected, $result);

        $result = __d('core', 'Some string with %s %s', ['multiple', 'arguments']);
        $expected = 'Some string with multiple arguments';
        $this->assertEquals($expected, $result);
    }

    /**
     * test __dn()
     *
     * @return void
     */
    public function testTranslateDomainPlural(): void
    {
        Configure::write('Config.language', 'rule_1_po');

        $result = __dn('default', '%d = 1', '%d = 0 or > 1', 0);
        $expected = '%d = 0 or > 1 (translated)';
        $this->assertEquals($expected, $result);

        $result = __dn('core', '%d = 1', '%d = 0 or > 1', 0);
        $expected = '%d = 0 or > 1';
        $this->assertEquals($expected, $result);

        $result = __dn('core', '%d = 1 (from core)', '%d = 0 or > 1 (from core)', 0);
        $expected = '%d = 0 or > 1 (from core translated)';
        $this->assertEquals($expected, $result);

        $result = __dn('default', '%d = 1', '%d = 0 or > 1', 1);
        $expected = '%d = 1 (translated)';
        $this->assertEquals($expected, $result);

        $result = __dn('core', '%d item.', '%d items.', 1, 1);
        $expected = '1 item.';
        $this->assertEquals($expected, $result);

        $result = __dn('core', '%d item for id %s', '%d items for id %s', 2, 2, '1234');
        $expected = '2 items for id 1234';
        $this->assertEquals($expected, $result);

        $result = __dn('core', '%d item for id %s', '%d items for id %s', 2, [2, '1234']);
        $expected = '2 items for id 1234';
        $this->assertEquals($expected, $result);
    }

    /**
     * test __c()
     *
     * @return void
     */
    public function testTranslateCategory(): void
    {
        Configure::write('Config.language', 'rule_1_po');

        $result = __c('Plural Rule 1', 6);
        $expected = 'Plural Rule 1 (translated)';
        $this->assertEquals($expected, $result);

        $result = __c('Plural Rule 1 (from core)', 6);
        $expected = 'Plural Rule 1 (from core translated)';
        $this->assertEquals($expected, $result);

        $result = __c('Some string with %s', 6, 'arguments');
        $expected = 'Some string with arguments';
        $this->assertEquals($expected, $result);

        $result = __c('Some string with %s %s', 6, 'multiple', 'arguments');
        $expected = 'Some string with multiple arguments';
        $this->assertEquals($expected, $result);

        $result = __c('Some string with %s %s', 6, ['multiple', 'arguments']);
        $expected = 'Some string with multiple arguments';
        $this->assertEquals($expected, $result);
    }

    /**
     * test __dc()
     *
     * @return void
     */
    public function testTranslateDomainCategory(): void
    {
        Configure::write('Config.language', 'rule_1_po');

        $result = __dc('default', 'Plural Rule 1', 6);
        $expected = 'Plural Rule 1 (translated)';
        $this->assertEquals($expected, $result);

        $result = __dc('default', 'Plural Rule 1 (from core)', 6);
        $expected = 'Plural Rule 1 (from core translated)';
        $this->assertEquals($expected, $result);

        $result = __dc('core', 'Plural Rule 1', 6);
        $expected = 'Plural Rule 1';
        $this->assertEquals($expected, $result);

        $result = __dc('core', 'Plural Rule 1 (from core)', 6);
        $expected = 'Plural Rule 1 (from core translated)';
        $this->assertEquals($expected, $result);

        $result = __dc('core', 'Some string with %s', 6, 'arguments');
        $expected = 'Some string with arguments';
        $this->assertEquals($expected, $result);

        $result = __dc('core', 'Some string with %s %s', 6, 'multiple', 'arguments');
        $expected = 'Some string with multiple arguments';
        $this->assertEquals($expected, $result);

        $result = __dc('core', 'Some string with %s %s', 6, ['multiple', 'arguments']);
        $expected = 'Some string with multiple arguments';
        $this->assertEquals($expected, $result);
    }

    /**
     * test __dcn()
     *
     * @return void
     */
    public function testTranslateDomainCategoryPlural(): void
    {
        Configure::write('Config.language', 'rule_1_po');

        $result = __dcn('default', '%d = 1', '%d = 0 or > 1', 0, 6);
        $expected = '%d = 0 or > 1 (translated)';
        $this->assertEquals($expected, $result);

        $result = __dcn('default', '%d = 1 (from core)', '%d = 0 or > 1 (from core)', 1, 6);
        $expected = '%d = 1 (from core translated)';
        $this->assertEquals($expected, $result);

        $result = __dcn('core', '%d = 1', '%d = 0 or > 1', 0, 6);
        $expected = '%d = 0 or > 1';
        $this->assertEquals($expected, $result);

        $result = __dcn('core', '%d item.', '%d items.', 1, 6, 1);
        $expected = '1 item.';
        $this->assertEquals($expected, $result);

        $result = __dcn('core', '%d item for id %s', '%d items for id %s', 2, 6, 2, '1234');
        $expected = '2 items for id 1234';
        $this->assertEquals($expected, $result);

        $result = __dcn('core', '%d item for id %s', '%d items for id %s', 2, 6, [2, '1234']);
        $expected = '2 items for id 1234';
        $this->assertEquals($expected, $result);
    }

    /**
     * test LogError()
     *
     * @return void
     */
    public function testLogError(): void
    {
        if (file_exists(LOGS . 'error.log')) {
            unlink(LOGS . 'error.log');
        }

        // disable stderr output for this test
        if (CakeLog::stream('stderr')) {
            CakeLog::disable('stderr');
        }

        LogError('Testing LogError() basic function');
        LogError("Testing with\nmulti-line\nstring");

        if (CakeLog::stream('stderr')) {
            CakeLog::enable('stderr');
        }

        $result = file_get_contents(LOGS . 'error.log');
        $this->assertMatchesRegularExpression('/Error: Testing LogError\(\) basic function/', $result);
        $this->assertDoesNotMatchRegularExpression("/Error: Testing with\nmulti-line\nstring/", $result);
        $this->assertMatchesRegularExpression('/Error: Testing with multi-line string/', $result);
    }

    /**
     * test fileExistsInPath()
     *
     * @return void
     */
    public function testFileExistsInPath(): void
    {
        if (!function_exists('ini_set')) {
            $this->markTestSkipped('%s ini_set function not available');
        }

        $_includePath = ini_get('include_path');

        $path = TMP . 'basics_test';
        $folder1 = $path . DS . 'folder1';
        $folder2 = $path . DS . 'folder2';
        $file1 = $path . DS . 'file1.php';
        $file2 = $folder1 . DS . 'file2.php';
        $file3 = $folder1 . DS . 'file3.php';
        $file4 = $folder2 . DS . 'file4.php';

        new Folder($path, true);
        new Folder($folder1, true);
        new Folder($folder2, true);
        touch($file1);
        touch($file2);
        touch($file3);
        touch($file4);

        ini_set('include_path', $path . PATH_SEPARATOR . $folder1);

        $this->assertEquals(fileExistsInPath('file1.php'), $file1);
        $this->assertEquals(fileExistsInPath('file2.php'), $file2);
        $this->assertEquals(fileExistsInPath('folder1' . DS . 'file2.php'), $file2);
        $this->assertEquals(fileExistsInPath($file2), $file2);
        $this->assertEquals(fileExistsInPath('file3.php'), $file3);
        $this->assertEquals(fileExistsInPath($file4), $file4);

        $this->assertFalse(fileExistsInPath('file1'));
        $this->assertFalse(fileExistsInPath('file4.php'));

        $Folder = new Folder($path);
        $Folder->delete();

        ini_set('include_path', $_includePath);
    }

    /**
     * test convertSlash()
     *
     * @return void
     */
    public function testConvertSlash(): void
    {
        $result = convertSlash('\path\to\location\\');
        $expected = '\path\to\location\\';
        $this->assertEquals($expected, $result);

        $result = convertSlash('/path/to/location/');
        $expected = 'path_to_location';
        $this->assertEquals($expected, $result);
    }

    /**
     * test debug()
     *
     * @return void
     */
    public function testDebug(): void
    {
        ob_start();
        debug('this-is-a-test', false);
        $result = ob_get_clean();
        $expectedText = <<<EXPECTED
%s (line %d)
########## DEBUG ##########
'this-is-a-test'
###########################

EXPECTED;
        $expected = sprintf($expectedText, str_replace([CORE_ROOT, ROOT], '', __FILE__), __LINE__ - 9);

        $this->assertEquals($expected, $result);

        ob_start();
        debug('<div>this-is-a-test</div>', true);
        $result = ob_get_clean();
        $expectedHtml = <<<EXPECTED
<div class="cake-debug-output">
<span><strong>%s</strong> (line <strong>%d</strong>)</span>
<pre class="cake-debug">
&#039;&lt;div&gt;this-is-a-test&lt;/div&gt;&#039;
</pre>
</div>
EXPECTED;
        $expected = sprintf($expectedHtml, str_replace([CORE_ROOT, ROOT], '', __FILE__), __LINE__ - 10);
        $this->assertEquals($expected, $result);

        ob_start();
        debug('<div>this-is-a-test</div>', true, true);
        $result = ob_get_clean();
        $expected = <<<EXPECTED
<div class="cake-debug-output">
<span><strong>%s</strong> (line <strong>%d</strong>)</span>
<pre class="cake-debug">
&#039;&lt;div&gt;this-is-a-test&lt;/div&gt;&#039;
</pre>
</div>
EXPECTED;
        $expected = sprintf($expected, str_replace([CORE_ROOT, ROOT], '', __FILE__), __LINE__ - 10);
        $this->assertEquals($expected, $result);

        ob_start();
        debug('<div>this-is-a-test</div>', true, false);
        $result = ob_get_clean();
        $expected = <<<EXPECTED
<div class="cake-debug-output">

<pre class="cake-debug">
&#039;&lt;div&gt;this-is-a-test&lt;/div&gt;&#039;
</pre>
</div>
EXPECTED;
        $expected = sprintf($expected, str_replace([CORE_ROOT, ROOT], '', __FILE__), __LINE__ - 10);
        $this->assertEquals($expected, $result);

        ob_start();
        debug('<div>this-is-a-test</div>', null);
        $result = ob_get_clean();
        $expectedHtml = <<<EXPECTED
<div class="cake-debug-output">
<span><strong>%s</strong> (line <strong>%d</strong>)</span>
<pre class="cake-debug">
&#039;&lt;div&gt;this-is-a-test&lt;/div&gt;&#039;
</pre>
</div>
EXPECTED;
        $expectedText = <<<EXPECTED
%s (line %d)
########## DEBUG ##########
'<div>this-is-a-test</div>'
###########################

EXPECTED;
        if (PHP_SAPI === 'cli') {
            $expected = sprintf($expectedText, str_replace([CORE_ROOT, ROOT], '', __FILE__), __LINE__ - 18);
        } else {
            $expected = sprintf($expectedHtml, str_replace([CORE_ROOT, ROOT], '', __FILE__), __LINE__ - 20);
        }
        $this->assertEquals($expected, $result);

        ob_start();
        debug('<div>this-is-a-test</div>', null, false);
        $result = ob_get_clean();
        $expectedHtml = <<<EXPECTED
<div class="cake-debug-output">

<pre class="cake-debug">
&#039;&lt;div&gt;this-is-a-test&lt;/div&gt;&#039;
</pre>
</div>
EXPECTED;
        $expectedText = <<<EXPECTED

########## DEBUG ##########
'<div>this-is-a-test</div>'
###########################

EXPECTED;
        if (PHP_SAPI === 'cli') {
            $expected = sprintf($expectedText, str_replace([CORE_ROOT, ROOT], '', __FILE__), __LINE__ - 18);
        } else {
            $expected = sprintf($expectedHtml, str_replace([CORE_ROOT, ROOT], '', __FILE__), __LINE__ - 19);
        }
        $this->assertEquals($expected, $result);

        ob_start();
        debug('<div>this-is-a-test</div>', false);
        $result = ob_get_clean();
        $expected = <<<EXPECTED
%s (line %d)
########## DEBUG ##########
'<div>this-is-a-test</div>'
###########################

EXPECTED;
        $expected = sprintf($expected, str_replace([CORE_ROOT, ROOT], '', __FILE__), __LINE__ - 9);
        $this->assertEquals($expected, $result);

        ob_start();
        debug('<div>this-is-a-test</div>', false, true);
        $result = ob_get_clean();
        $expected = <<<EXPECTED
%s (line %d)
########## DEBUG ##########
'<div>this-is-a-test</div>'
###########################

EXPECTED;
        $expected = sprintf($expected, str_replace([CORE_ROOT, ROOT], '', __FILE__), __LINE__ - 9);
        $this->assertEquals($expected, $result);

        ob_start();
        debug('<div>this-is-a-test</div>', false, false);
        $result = ob_get_clean();
        $expected = <<<EXPECTED

            ########## DEBUG ##########
            '<div>this-is-a-test</div>'
            ###########################

            EXPECTED;
        $expected = sprintf($expected, str_replace([CORE_ROOT, ROOT], '', __FILE__), __LINE__ - 9);
        $this->assertEquals($expected, $result);

        ob_start();
        debug(false, false, false);
        $result = ob_get_clean();
        $expected = <<<EXPECTED

########## DEBUG ##########
false
###########################

EXPECTED;
        $expected = sprintf($expected, str_replace([CORE_ROOT, ROOT], '', __FILE__), __LINE__ - 9);
        $this->assertEquals($expected, $result);
    }

    /**
     * test pr()
     *
     * @return void
     */
    public function testPr(): void
    {
        $this->skipIf(PHP_SAPI === 'cli', 'Skipping web test in cli mode');
        ob_start();
        pr('this is a test');
        $result = ob_get_clean();
        $expected = '<pre>this is a test</pre>';
        $this->assertEquals($expected, $result);

        ob_start();
        pr(['this' => 'is', 'a' => 'test']);
        $result = ob_get_clean();
        $expected = "<pre>Array\n(\n    [this] => is\n    [a] => test\n)\n</pre>";
        $this->assertEquals($expected, $result);
    }

    /**
     * test pr()
     *
     * @return void
     */
    public function testPrCli(): void
    {
        $this->skipIf(PHP_SAPI !== 'cli', 'Skipping cli test in web mode');
        ob_start();
        pr('this is a test');
        $result = ob_get_clean();
        $expected = "\nthis is a test\n";
        $this->assertEquals($expected, $result);

        ob_start();
        pr(['this' => 'is', 'a' => 'test']);
        $result = ob_get_clean();
        $expected = "\nArray\n(\n    [this] => is\n    [a] => test\n)\n\n";
        $this->assertEquals($expected, $result);
    }

    /**
     * test stripslashes_deep()
     *
     * @return void
     */
    public function testStripslashesDeep(): void
    {
        $this->assertEquals(stripslashes_deep("tes\'t"), "tes't");
        $this->assertEquals(stripslashes_deep('tes\\' . chr(0) . 't'), 'tes' . chr(0) . 't');
        $this->assertEquals(stripslashes_deep('tes\"t'), 'tes"t');
        $this->assertEquals(stripslashes_deep("tes\'t"), "tes't");
        $this->assertEquals(stripslashes_deep('te\\st'), 'test');

        $nested = [
            'a' => "tes\'t",
            'b' => 'tes\\' . chr(0) . 't',
            'c' => [
                'd' => 'tes\"t',
                'e' => "te\'s\'t",
                ['f' => "tes\'t"],
                ],
            'g' => 'te\\st',
        ];
        $expected = [
            'a' => "tes't",
            'b' => 'tes' . chr(0) . 't',
            'c' => [
                'd' => 'tes"t',
                'e' => "te's't",
                ['f' => "tes't"],
                ],
            'g' => 'test',
        ];
        $this->assertEquals($expected, stripslashes_deep($nested));
    }

    /**
     * Tests that the stackTrace() method is a shortcut for Debugger::trace()
     *
     * @return void
     */
    public function testStackTrace(): void
    {
        ob_start();
        [, $expected] = [stackTrace(), Debugger::trace()];
        $result = ob_get_clean();
        $this->assertEquals($expected, $result);

        $opts = ['args' => true];
        ob_start();
        [, $expected] = [stackTrace($opts), Debugger::trace($opts)];
        $result = ob_get_clean();
        $this->assertEquals($expected, $result);
    }

    /**
     * test pluginSplit
     *
     * @return void
     */
    public function testPluginSplit(): void
    {
        $result = pluginSplit('Something.else');
        $this->assertEquals(['Something', 'else'], $result);

        $result = pluginSplit('Something.else.more.dots');
        $this->assertEquals(['Something', 'else.more.dots'], $result);

        $result = pluginSplit('Somethingelse');
        $this->assertEquals([null, 'Somethingelse'], $result);

        $result = pluginSplit('Something.else', true);
        $this->assertEquals(['Something.', 'else'], $result);

        $result = pluginSplit('Something.else.more.dots', true);
        $this->assertEquals(['Something.', 'else.more.dots'], $result);

        $result = pluginSplit('Post', false, 'Blog');
        $this->assertEquals(['Blog', 'Post'], $result);

        $result = pluginSplit('Blog.Post', false, 'Ultimate');
        $this->assertEquals(['Blog', 'Post'], $result);
    }

    /**
     * Test that config() includes the files under CONFIG, and only returns
     * true when all of the given files exist.
     *
     * @return void
     */
    public function testConfig(): void
    {
        // config() includes files relative to CONFIG, which is the config folder of the
        // app skeleton here, so the test reaches its file under TMP with a relative path.
        $file = 'basics_test_config_' . getmypid();
        file_put_contents(TMP . $file . '.php', '<?php \Cake\Core\Configure::write(\'BasicsTest.config\', \'loaded\');');

        $configParts = explode(DS, rtrim(realpath(CONFIG), DS));
        $tmpParts = explode(DS, rtrim(realpath(TMP), DS));
        while ($configParts && $tmpParts && $configParts[0] === $tmpParts[0]) {
            array_shift($configParts);
            array_shift($tmpParts);
        }
        $name = str_repeat('..' . DS, count($configParts)) . implode(DS, array_merge($tmpParts, [$file]));

        try {
            $this->assertTrue(config($name));
            $this->assertSame('loaded', Configure::read('BasicsTest.config'));
            $this->assertFalse(config($name, 'basics_test_missing_config'));
            $this->assertFalse(config('basics_test_missing_config'));
        } finally {
            unlink(TMP . $file . '.php');
        }
    }

    /**
     * Test that debug(), pr() and stackTrace() print nothing when debug is off.
     *
     * @return void
     */
    public function testDebugFunctionsWithDebugOff(): void
    {
        Configure::write('debug', 0);
        ob_start();
        debug('this-is-a-test');
        pr(['this-is-a-test']);
        stackTrace();
        $this->assertSame('', ob_get_clean());
    }

    /**
     * Test sortByKey() in both orders and with the sort types, and with a value
     * that is not an array.
     *
     * @return void
     */
    public function testSortByKey(): void
    {
        $data = [
            'a' => ['id' => 10, 'name' => 'beta'],
            'b' => ['id' => 2, 'name' => 'alpha'],
            'c' => ['id' => 33, 'name' => 'Gamma'],
        ];

        $result = sortByKey($data, 'id');
        $this->assertSame([$data['b'], $data['a'], $data['c']], $result);

        $result = sortByKey($data, 'id', 'desc');
        $this->assertSame([$data['c'], $data['a'], $data['b']], $result);

        $result = sortByKey($data, 'name', 'asc', SORT_STRING);
        $this->assertSame([$data['c'], $data['b'], $data['a']], $result);

        $result = sortByKey($data, 'name', 'asc', SORT_STRING | SORT_FLAG_CASE);
        $this->assertSame([$data['b'], $data['a'], $data['c']], $result);

        $notArray = 'string';
        $this->assertNull(sortByKey($notArray, 'id'));
    }

    /**
     * Test the values that env() reads from getenv() and the values that it
     * emulates when $_SERVER and $_ENV do not have them.
     *
     * @return void
     */
    public function testEnvEmulation(): void
    {
        $server = $_SERVER;
        $env = $_ENV;
        $name = 'CAKE_BASICS_TEST_' . getmypid();
        // env() falls back to getenv(), so the variables of the process are cleared too.
        $variables = [
            'CGI_MODE', 'DOCUMENT_ROOT', 'HTTPS', 'HTTP_BASE', 'HTTP_HOST', 'HTTP_PC_REMOTE_ADDR',
            'PHP_SELF', 'REMOTE_ADDR', 'SCRIPT_FILENAME', 'SCRIPT_NAME', 'SCRIPT_URI', 'SCRIPT_URL',
            'SERVER_ADDR',
        ];
        $saved = [];
        foreach ($variables as $variable) {
            $saved[$variable] = getenv($variable);
            putenv($variable);
        }

        try {
            $_SERVER = $_ENV = [];
            putenv($name . '=from getenv');
            $this->assertSame('from getenv', env($name));
            $_ENV[$name] = 'from $_ENV';
            $this->assertSame('from $_ENV', env($name));
            putenv($name);
            unset($_ENV[$name]);
            $this->assertNull(env($name));

            $_SERVER = ['REMOTE_ADDR' => '10.0.0.1', 'SERVER_ADDR' => '10.0.0.1'];
            $this->assertSame('10.0.0.1', env('REMOTE_ADDR'));
            $_SERVER['HTTP_PC_REMOTE_ADDR'] = '192.0.2.10';
            $this->assertSame('192.0.2.10', env('REMOTE_ADDR'));
            $_SERVER['REMOTE_ADDR'] = '192.0.2.20';
            $this->assertSame('192.0.2.20', env('REMOTE_ADDR'));

            $_SERVER = [
                'SCRIPT_NAME' => '/app/index.php',
                'SCRIPT_FILENAME' => '/var/www/app/index.php',
            ];
            $this->assertSame('/var/www', env('DOCUMENT_ROOT'));
            $this->assertSame('/app/index.php', env('PHP_SELF'));

            $_SERVER = [
                'SCRIPT_NAME' => '/app/index',
                'SCRIPT_FILENAME' => '/var/www/app/index.php',
            ];
            $this->assertSame('/var/www', env('DOCUMENT_ROOT'));

            $_SERVER = [
                'DOCUMENT_ROOT' => '/srv/site',
                'SCRIPT_FILENAME' => '/srv/site/webroot/index.php',
            ];
            $this->assertSame('/webroot/index.php', env('PHP_SELF'));

            $_SERVER = [];
            $this->assertFalse(env('CGI_MODE'));
            $this->assertSame('', env('DOCUMENT_ROOT'));
        } finally {
            putenv($name);
            foreach ($saved as $variable => $value) {
                putenv($value === false ? $variable : $variable . '=' . $value);
            }
            $_SERVER = $server;
            $_ENV = $env;
        }
    }

    /**
     * Test cache() with the tmp target, with a file younger than the expiry that it
     * reads, with an expired file that it deletes, and with an unknown target that it
     * does not write.
     *
     * @return void
     */
    public function testCacheTargets(): void
    {
        $this->skipIf((bool)Configure::read('Cache.disable'), 'Cache is disabled, skipping cache() tests.');
        $file = 'basics_test_cache_' . getmypid();

        try {
            $this->assertSame('tmp data', cache($file, 'tmp data', '+1 hour', 'TMP'));
            $this->assertSame('tmp data', file_get_contents(TMP . $file));
            $this->assertSame('tmp data', cache($file, null, '+1 hour', 'tmp'));

            // The expiry is relative: a file written half an hour ago is still fresh...
            touch(TMP . $file, time() - 1800);
            clearstatcache();
            $this->assertSame('tmp data', cache($file, null, '+1 hour', 'tmp'));

            // ...and a file written two hours ago is expired and deleted.
            touch(TMP . $file, time() - 7200);
            clearstatcache();
            $this->assertNull(cache($file, null, '+1 hour', 'tmp'));
            $this->assertFileDoesNotExist(TMP . $file);

            $this->assertSame('data', cache($file, 'data', '+1 hour', 'unknown'));
            $this->assertFileDoesNotExist(TMP . $file);
            $this->assertFileDoesNotExist(CACHE . $file);
        } finally {
            if (file_exists(TMP . $file)) {
                unlink(TMP . $file);
            }
        }
    }

    /**
     * Test that __() translates the string '0' like any other string.
     *
     * @return void
     */
    public function testTranslateZeroString(): void
    {
        $this->markTestIncomplete("Known bug: __() returns null for '0', because it checks the string with !\$singular");

        $this->assertSame('0', __('0'));
    }

    /**
     * Test that clearCache() returns false when no file matches.
     *
     * @return void
     */
    public function testClearCacheWithoutMatches(): void
    {
        $this->assertFalse(clearCache('basics_test_no_such_cache_' . getmypid(), 'views', '.cache'));
        $this->assertFalse(clearCache('basics_test_no_such_cache', 'basics_test_no_such_dir'));
    }

    /**
     * Test the translation functions with a context, with the domain,
     * category and plural variants.
     *
     * @return void
     */
    public function testTranslateWithContext(): void
    {
        Configure::write('Config.language', 'nld');

        $this->assertSame('brief', __x('mail', 'letter'));
        $this->assertSame('brieven', __xn('mail', 'letter', 'letters', 2));
        $this->assertSame('brief', __xn('mail', 'letter', 'letters', 1));
        $this->assertSame('letters', __xn('character', 'letter', 'letters', 2));
        $this->assertSame('danspartij', __dx('default', 'social gathering', 'ball'));
        $this->assertSame('brieven', __dxn('default', 'mail', 'letter', 'letters', 3));
        $this->assertSame('bal', __dxc('default', 'spherical object', 'ball', 6));
        $this->assertSame('brief', __dxcn('default', 'mail', 'letter', 'letters', 1, 6));
        $this->assertSame('saldo', __xc('money', 'balance', 6));
        $this->assertSame('balans', __('balance'));
        $this->assertSame('No context 2', __x('mail', 'No context %d', 2));
    }

    /**
     * Test that the translation functions return null for an empty message.
     *
     * @return void
     */
    public function testTranslateEmptyMessage(): void
    {
        $this->assertNull(__(''));
        $this->assertNull(__n('', 'plural', 2));
        $this->assertNull(__d('default', ''));
        $this->assertNull(__dn('default', '', 'plural', 2));
        $this->assertNull(__dc('default', '', 6));
        $this->assertNull(__dcn('default', '', 'plural', 2, 6));
        $this->assertNull(__c('', 6));
        $this->assertNull(__x('context', ''));
        $this->assertNull(__xn('context', '', 'plural', 2));
        $this->assertNull(__dx('default', 'context', ''));
        $this->assertNull(__dxn('default', 'context', '', 'plural', 2));
        $this->assertNull(__dxc('default', 'context', '', 6));
        $this->assertNull(__dxcn('default', 'context', '', 'plural', 2, 6));
        $this->assertNull(__xc('context', '', 6));
    }
}
