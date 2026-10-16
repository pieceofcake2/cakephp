<?php
/**
 * CookieComponentTest file
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
 * @since         CakePHP(tm) v 1.2.0.5435
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

/**
 * CookieComponentTestController class
 *
 * @package       Cake.Test.Case.Controller.Component
 */
class CookieComponentTestController extends Controller
{
    /**
     * components property
     *
     * @var array
     */
    public $components = ['Cookie'];

    /**
     * beforeFilter method
     *
     * @return void
     */
    public function beforeFilter(): void
    {
        $this->Cookie->name = 'CakeTestCookie';
        $this->Cookie->time = 10;
        $this->Cookie->path = '/';
        $this->Cookie->domain = '';
        $this->Cookie->secure = false;
        $this->Cookie->key = 'somerandomhaskey';
    }
}

/**
 * Log engine that keeps the messages written while cookies are encrypted or decrypted.
 *
 * @package       Cake.Test.Case.Controller.Component
 */
class CookieComponentTestCaptureLog extends BaseLog
{
    /**
     * Logged messages as [type, message] pairs.
     *
     * @var array<int, array{0: string, 1: string}>
     */
    public static $messages = [];

    /**
     * Keeps the message.
     *
     * @param string $type The type of log.
     * @param string $message The message.
     * @return void
     */
    public function write($type, $message): void
    {
        static::$messages[] = [$type, $message];
    }
}

/**
 * CookieComponentTest class
 *
 * @package       Cake.Test.Case.Controller.Component
 */
class CookieComponentTest extends CakeTestCase
{
    /**
     * Controller property
     *
     * @var CookieComponentTestController
     */
    public $Controller;

    /**
     * start
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $_COOKIE = [];
        $this->Controller = new CookieComponentTestController(new CakeRequest(), new CakeResponse());
        $this->Controller->constructClasses();
        $this->Cookie = $this->Controller->Cookie;

        $this->Cookie->name = 'CakeTestCookie';
        $this->Cookie->time = 10;
        $this->Cookie->path = '/';
        $this->Cookie->domain = '';
        $this->Cookie->secure = false;
        $this->Cookie->key = 'somerandomhaskey';

        $this->Cookie->startup($this->Controller);
    }

    /**
     * end
     *
     * @return void
     */
    public function tearDown(): void
    {
        CakeLog::drop('cookie_test_capture');
        Configure::delete('Cookie.key');
        Configure::delete('Cookie.type');
        CookieComponentTestCaptureLog::$messages = [];
        $this->_setCipherReported(true);
        Configure::delete('Security.useOpenSsl');
        $this->Cookie->destroy();

        parent::tearDown();
    }

    /**
     * sets up some default cookie data.
     *
     * @return void
     */
    protected function _setCookieData(): void
    {
        $this->Cookie->write(['Encrytped_array' => ['name' => 'CakePHP', 'version' => '1.2.0.x', 'tag' => 'CakePHP Rocks!']]);
        $this->Cookie->write(['Encrytped_multi_cookies.name' => 'CakePHP']);
        $this->Cookie->write(['Encrytped_multi_cookies.version' => '1.2.0.x']);
        $this->Cookie->write(['Encrytped_multi_cookies.tag' => 'CakePHP Rocks!']);

        $this->Cookie->write(['Plain_array' => ['name' => 'CakePHP', 'version' => '1.2.0.x', 'tag' => 'CakePHP Rocks!']], null, false);
        $this->Cookie->write(['Plain_multi_cookies.name' => 'CakePHP'], null, false);
        $this->Cookie->write(['Plain_multi_cookies.version' => '1.2.0.x'], null, false);
        $this->Cookie->write(['Plain_multi_cookies.tag' => 'CakePHP Rocks!'], null, false);
    }

    /**
     * test that initialize sets settings from components array
     *
     * @return void
     */
    public function testSettings(): void
    {
        $settings = [
            'time' => '5 days',
            'path' => '/',
        ];
        $Cookie = new CookieComponent(new ComponentCollection(), $settings);
        $this->assertEquals($Cookie->time, $settings['time']);
        $this->assertEquals($Cookie->path, $settings['path']);
    }

    /**
     * Creates a CookieComponent with the given settings for a new controller.
     *
     * @param array $settings Component settings.
     * @return array{0: CookieComponent, 1: Controller}
     */
    protected function _newCookieComponent(array $settings = []): mixed
    {
        $Controller = new CookieComponentTestController(new CakeRequest(), new CakeResponse());
        $Collection = new ComponentCollection();
        $Collection->setController($Controller);

        return [new CookieComponent($Collection, $settings), $Controller];
    }

    /**
     * The Cookie.key Configure value is the default key when it is a non-empty string.
     *
     * @return void
     */
    public function testConfigureCookieKeyIsDefaultKey(): void
    {
        Configure::write('Cookie.key', 'configured-cookie-key');
        [$Cookie] = $this->_newCookieComponent();
        $this->assertSame('configured-cookie-key', $Cookie->key);
    }

    /**
     * Without Cookie.key (or with an empty or non-string one), Security.salt stays the default key.
     *
     * @return void
     */
    public function testConfigureCookieKeyFallsBackToSalt(): void
    {
        Configure::write('Security.salt', 'the-salt-of-this-test');
        foreach ([null, '', false, 12345678901234567890123456789012] as $value) {
            Configure::write('Cookie.key', $value);
            [$Cookie] = $this->_newCookieComponent();
            $this->assertSame('the-salt-of-this-test', $Cookie->key, var_export($value, true));
        }
        Configure::delete('Cookie.key');
        [$Cookie] = $this->_newCookieComponent();
        $this->assertSame('the-salt-of-this-test', $Cookie->key);
    }

    /**
     * An explicit key in the settings or on the property wins over Cookie.key.
     *
     * @return void
     */
    public function testExplicitKeyOverridesConfigureCookieKey(): void
    {
        Configure::write('Cookie.key', 'configured-cookie-key');
        [$Cookie] = $this->_newCookieComponent(['key' => 'settings-key']);
        $this->assertSame('settings-key', $Cookie->key);

        [$Cookie] = $this->_newCookieComponent();
        $Cookie->key = 'property-key';
        $this->assertSame('property-key', $Cookie->key);
    }

    /**
     * 'aes' cookies round-trip with a 32-byte Cookie.key and no explicit key.
     *
     * @return void
     */
    public function testAesRoundTripWithConfigureCookieKey(): void
    {
        $key = str_repeat('k', 32);
        Configure::write('Security.useOpenSsl', true);
        Configure::write('Cookie.type', 'aes');
        Configure::write('Cookie.key', $key);
        $this->_captureCipherReports();
        [$Cookie, $Controller] = $this->_newCookieComponent();

        $Cookie->write('Testing', ['a' => 'value']);
        $cookie = $Controller->response->cookie('CakeCookie[Testing]');
        $this->assertStringStartsWith('Q2FrZQ==.', $cookie['value']);
        $encrypted = base64_decode(substr($cookie['value'], strlen('Q2FrZQ==.')));
        $this->assertSame('{"a":"value"}', Security::decrypt($encrypted, $key));

        $_COOKIE['CakeCookie'] = ['Testing' => $cookie['value']];
        [$Reader] = $this->_newCookieComponent();
        $this->assertSame(['a' => 'value'], $Reader->read('Testing'));
        $this->assertSame([], CookieComponentTestCaptureLog::$messages, 'No cipher report for aes');
    }

    /**
     * A Cookie.key shorter than 32 bytes behaves like a short explicit key: 'aes' throws
     * the existing CakeException of Security::encrypt().
     *
     * @return void
     */
    public function testShortConfigureCookieKeyWithAes(): void
    {
        Configure::write('Security.useOpenSsl', true);
        Configure::write('Cookie.key', 'short');
        [$Cookie] = $this->_newCookieComponent(['type' => 'aes']);
        $this->expectException(CakeException::class);
        $this->expectExceptionMessage('Invalid key for encrypt(), key must be at least 256 bits (32 bytes) long.');
        $Cookie->write('Testing', 'value');
    }

    /**
     * The Cookie.type Configure value is the default type.
     *
     * @return void
     */
    public function testConfigureCookieTypeIsDefaultType(): void
    {
        foreach (['aes', 'rijndael', 'cipher'] as $type) {
            Configure::write('Cookie.type', $type);
            [$Cookie] = $this->_newCookieComponent();
            $this->assertSame($type, $this->_cookieType($Cookie));
        }
    }

    /**
     * An explicit type in the settings or with type() wins over Cookie.type.
     *
     * @return void
     */
    public function testExplicitTypeOverridesConfigureCookieType(): void
    {
        Configure::write('Cookie.type', 'aes');
        [$Cookie] = $this->_newCookieComponent(['type' => 'rijndael']);
        $this->assertSame('rijndael', $this->_cookieType($Cookie));

        [$Cookie] = $this->_newCookieComponent();
        $Cookie->type('cipher');
        $this->assertSame('cipher', $this->_cookieType($Cookie));
    }

    /**
     * Without Cookie.type the type stays 'cipher'.
     *
     * @return void
     */
    public function testConfigureCookieTypeUnsetDefaultsToCipher(): void
    {
        Configure::delete('Cookie.type');
        [$Cookie] = $this->_newCookieComponent();
        $this->assertSame('cipher', $this->_cookieType($Cookie));
    }

    /**
     * An invalid Cookie.type behaves like type() with that value: a warning and 'cipher'.
     *
     * @return void
     */
    public function testInvalidConfigureCookieType(): void
    {
        Configure::write('Cookie.type', 'blowfish');
        $errors = [];
        set_error_handler(function ($errno, $errstr) use (&$errors) {
            $errors[] = [$errno, $errstr];

            return true;
        }, E_USER_WARNING);
        try {
            [$Cookie] = $this->_newCookieComponent();
        } finally {
            restore_error_handler();
        }
        $this->assertSame([[E_USER_WARNING, 'You must use cipher, rijndael or aes for cookie encryption type']], $errors);
        $this->assertSame('cipher', $this->_cookieType($Cookie));
    }

    /**
     * Returns the encryption type of a CookieComponent.
     *
     * @param CookieComponent $Cookie The component.
     * @return string
     */
    protected function _cookieType(CookieComponent $Cookie): string
    {
        return Closure::bind(function () {
            return $this->_type;
        }, $Cookie, CookieComponent::class)();
    }

    /**
     * test that the encryption type can be set with the 'type' setting
     *
     * @return void
     */
    public function testSettingsType(): void
    {
        Configure::write('Security.useOpenSsl', true);
        $Controller = new CookieComponentTestController(new CakeRequest(), new CakeResponse());
        $Collection = new ComponentCollection();
        $Collection->setController($Controller);
        $key = 'qSI232qs*&sXOw!adre@34SAv!@*(XSL#$%)asGb$@11~_+!@#HKis~#^';
        $Cookie = new CookieComponent($Collection, ['type' => 'aes', 'key' => $key]);

        $Cookie->write('Testing', 'value');
        $cookie = $Controller->response->cookie('CakeCookie[Testing]');
        $this->assertStringStartsWith('Q2FrZQ==.', $cookie['value']);

        $encrypted = base64_decode(substr($cookie['value'], strlen('Q2FrZQ==.')));
        $this->assertSame('value', Security::decrypt($encrypted, $key));
        $this->assertSame('value', $Cookie->read('Testing'));
    }

    /**
     * test that an invalid 'type' setting falls back to cipher with a warning
     *
     * @return void
     */
    public function testSettingsInvalidType(): void
    {
        $errors = [];
        set_error_handler(function ($errno, $errstr) use (&$errors) {
            $errors[] = [$errno, $errstr];

            return true;
        });
        try {
            new CookieComponent(new ComponentCollection(), ['type' => 'invalid']);
        } finally {
            restore_error_handler();
        }

        $expected = [[E_USER_WARNING, 'You must use cipher, rijndael or aes for cookie encryption type']];
        $this->assertSame($expected, $errors);
    }

    /**
     * testCookieName
     *
     * @return void
     */
    public function testCookieName(): void
    {
        $this->assertEquals('CakeTestCookie', $this->Cookie->name);
    }

    /**
     * testReadEncryptedCookieData
     *
     * @return void
     */
    public function testReadEncryptedCookieData(): void
    {
        $this->_setCookieData();
        $data = $this->Cookie->read('Encrytped_array');
        $expected = ['name' => 'CakePHP', 'version' => '1.2.0.x', 'tag' => 'CakePHP Rocks!'];
        $this->assertEquals($expected, $data);

        $data = $this->Cookie->read('Encrytped_multi_cookies');
        $expected = ['name' => 'CakePHP', 'version' => '1.2.0.x', 'tag' => 'CakePHP Rocks!'];
        $this->assertEquals($expected, $data);
    }

    /**
     * Sets whether the Security::cipher() deprecation and seed reports were already made.
     *
     * @param bool $reported Value for both flags.
     * @return void
     */
    protected function _setCipherReported($reported): void
    {
        Closure::bind(function () use ($reported) {
            static::$_cipherDeprecationReported = $reported;
            static::$_cipherSeedReported = $reported;
        }, null, Security::class)();
    }

    /**
     * Resets the Security::cipher() reports and starts capturing log messages.
     *
     * @return void
     */
    protected function _captureCipherReports(): void
    {
        $this->_setCipherReported(false);
        CookieComponentTestCaptureLog::$messages = [];
        CakeLog::config('cookie_test_capture', ['engine' => 'CookieComponentTestCapture']);
    }

    /**
     * Asserts that no captured log message contains any of the given values.
     *
     * @param array<int, string> $values Cookie values.
     * @return void
     */
    protected function _assertValuesNotLogged(array $values): void
    {
        foreach (CookieComponentTestCaptureLog::$messages as $entry) {
            foreach ($values as $value) {
                $this->assertStringNotContainsString($value, $entry[1]);
            }
        }
    }

    /**
     * The default 'cipher' type logs its deprecation once, naming CookieComponent,
     * and never logs cookie values.
     *
     * @return void
     */
    public function testCipherDeprecationNamesCookieComponent(): void
    {
        Configure::write('debug', 0);
        Configure::write('Security.cipherSeed', '2659758734');
        $_COOKIE['CakeTestCookie'] = [
            'Incoming' => $this->_encrypt(['secret' => 'IncomingSecretValue']),
        ];
        $this->_captureCipherReports();

        $this->Cookie->write('Written', 'WrittenSecretValue');
        $this->Cookie->write('Other', ['nested' => 'NestedSecretValue']);
        $this->assertSame(['secret' => 'IncomingSecretValue'], $this->Cookie->read('Incoming'));
        $this->assertSame('WrittenSecretValue', $this->Cookie->read('Written'));

        $this->assertCount(1, CookieComponentTestCaptureLog::$messages);
        $this->assertSame('warning', CookieComponentTestCaptureLog::$messages[0][0]);
        $this->assertSame(
            'CookieComponent (type \'cipher\') uses Security::cipher(), which is insecure and deprecated. '
            . 'Set Configure \'Cookie.type\' to \'aes\' and \'Cookie.key\' to a random value of at least 32 bytes, '
            . 'with Security.useOpenSsl enabled; existing \'cipher\' cookies are lost once. '
            . 'See https://pieceofcake2.github.io/docs/development/configuration.html',
            CookieComponentTestCaptureLog::$messages[0][1],
        );
        $this->_assertValuesNotLogged(['IncomingSecretValue', 'WrittenSecretValue', 'NestedSecretValue', 'somerandomhaskey']);
    }

    /**
     * With debug on, the 'cipher' deprecation is also triggered once as E_USER_DEPRECATED.
     *
     * @return void
     */
    public function testCipherDeprecationTriggeredOnceInDebug(): void
    {
        Configure::write('debug', 2);
        Configure::write('Security.cipherSeed', '2659758734');
        $this->_captureCipherReports();

        $errors = [];
        set_error_handler(function ($errno, $errstr) use (&$errors) {
            $errors[] = $errstr;

            return true;
        }, E_USER_DEPRECATED);
        try {
            $this->Cookie->write('First', 'FirstSecretValue');
            $this->Cookie->write('Second', 'SecondSecretValue');
        } finally {
            restore_error_handler();
        }

        $this->assertCount(1, $errors);
        $this->assertStringStartsWith('CookieComponent (type \'cipher\') uses Security::cipher()', $errors[0]);
        $this->assertCount(1, CookieComponentTestCaptureLog::$messages);
        $this->assertStringNotContainsString('SecretValue', $errors[0]);
        $this->_assertValuesNotLogged(['FirstSecretValue', 'SecondSecretValue']);
    }

    /**
     * An out-of-range seed is logged once, next to the deprecation, without cookie values.
     *
     * @return void
     */
    public function testCipherSeedOutOfRangeLoggedOnce(): void
    {
        Configure::write('debug', 0);
        Configure::write('Security.cipherSeed', '76859309657453542496749683645');
        $this->_captureCipherReports();

        $this->Cookie->write('First', 'FirstSecretValue');
        $this->Cookie->write('Second', 'SecondSecretValue');
        $this->assertSame('FirstSecretValue', $this->Cookie->read('First'));

        $this->assertCount(2, CookieComponentTestCaptureLog::$messages);
        $this->assertStringStartsWith('CookieComponent (type \'cipher\')', CookieComponentTestCaptureLog::$messages[0][1]);
        $this->assertStringStartsWith(
            'Security.cipherSeed is not a digits-only number from 1 to 4294967295, so Security::cipher() reduces it to seed 0.',
            CookieComponentTestCaptureLog::$messages[1][1],
        );
        $this->_assertValuesNotLogged(['FirstSecretValue', 'SecondSecretValue', '76859309657453542496749683645']);
    }

    /**
     * Valid 'aes' cookies are not reported.
     *
     * @return void
     */
    public function testNoCipherReportForAesCookies(): void
    {
        Configure::write('Security.useOpenSsl', true);
        Configure::write('Security.cipherSeed', '76859309657453542496749683645');
        $this->Cookie->type('aes');
        $this->Cookie->key = str_repeat('0123456789abcdef', 2);
        $this->_captureCipherReports();

        $this->Cookie->write('Secret', 'AesSecretValue');
        $this->Cookie->write('Nested', ['a' => 'b']);
        $this->assertSame('AesSecretValue', $this->Cookie->read('Secret'));
        $this->assertSame(['a' => 'b'], $this->Cookie->read('Nested'));

        $this->assertSame([], CookieComponentTestCaptureLog::$messages);
    }

    /**
     * Plain (unencrypted) cookies do not use Security::cipher() and are not reported.
     *
     * @return void
     */
    public function testNoCipherReportForPlainCookies(): void
    {
        Configure::write('Security.cipherSeed', '76859309657453542496749683645');
        $_COOKIE['CakeTestCookie'] = [
            'Legacy' => 'name|value,other|thing',
            'Plain' => 'just a value',
            'Json' => '{"a":"b"}',
        ];
        $this->_captureCipherReports();

        $this->Cookie->write('Unencrypted', 'PlainValue', false);
        $this->assertSame('PlainValue', $this->Cookie->read('Unencrypted'));
        $this->assertSame(['name' => 'value', 'other' => 'thing'], $this->Cookie->read('Legacy'));
        $this->assertSame('just a value', $this->Cookie->read('Plain'));
        $this->assertSame(['a' => 'b'], $this->Cookie->read('Json'));

        $this->assertSame([], CookieComponentTestCaptureLog::$messages);
    }

    /**
     * test read operations on corrupted cookie data.
     *
     * @return void
     */
    public function testReadCorruptedCookieData(): void
    {
        $this->skipIf(!extension_loaded('mcrypt'), 'No Mcrypt, skipping.');
        $this->Cookie->type('aes');
        $this->Cookie->key = sha1('some bad key');

        $data = $this->_implode(['name' => 'jill', 'age' => 24]);
        // Corrupt the cookie data by slicing some bytes off.
        $_COOKIE['CakeTestCookie'] = [
            'BadData' => substr(Security::encrypt($data, $this->Cookie->key), 0, -5),
        ];
        $this->assertFalse($this->Cookie->check('BadData.name'), 'Key does not exist');
        $this->assertNull($this->Cookie->read('BadData.name'), 'Key does not exist');
    }

    /**
     * testReadPlainCookieData
     *
     * @return void
     */
    public function testReadPlainCookieData(): void
    {
        $this->_setCookieData();
        $data = $this->Cookie->read('Plain_array');
        $expected = ['name' => 'CakePHP', 'version' => '1.2.0.x', 'tag' => 'CakePHP Rocks!'];
        $this->assertEquals($expected, $data);

        $data = $this->Cookie->read('Plain_multi_cookies');
        $expected = ['name' => 'CakePHP', 'version' => '1.2.0.x', 'tag' => 'CakePHP Rocks!'];
        $this->assertEquals($expected, $data);
    }

    /**
     * test read array keys from string data.
     *
     * @return void
     */
    public function testReadNestedDataFromStrings(): void
    {
        $_COOKIE['CakeTestCookie'] = [
            'User' => 'bad data',
        ];
        $this->assertFalse($this->Cookie->check('User.name'), 'No key');
        $this->assertNull($this->Cookie->read('User.name'), 'No key');
    }

    /**
     * test read() after switching the cookie name.
     *
     * @return void
     */
    public function testReadWithNameSwitch(): void
    {
        $_COOKIE = [
            'CakeTestCookie' => [
                'key' => 'value',
            ],
            'OtherTestCookie' => [
                'key' => 'other value',
            ],
        ];
        $this->assertEquals('value', $this->Cookie->read('key'));

        $this->Cookie->name = 'OtherTestCookie';
        $this->assertEquals('other value', $this->Cookie->read('key'));
    }

    /**
     * test a simple write()
     *
     * @return void
     */
    public function testWriteSimple(): void
    {
        $this->Cookie->write('Testing', 'value');
        $result = $this->Cookie->read('Testing');

        $this->assertEquals('value', $result);
    }

    /**
     * test write() encrypted data with falsey value
     *
     * @return void
     */
    public function testWriteWithFalseyValue(): void
    {
        $this->skipIf(!extension_loaded('mcrypt'), 'No Mcrypt, skipping.');
        $this->Cookie->type('aes');
        $this->Cookie->key = 'qSI232qs*&sXOw!adre@34SAv!@*(XSL#$%)asGb$@11~_+!@#HKis~#^';

        $this->Cookie->write('Testing');
        $result = $this->Cookie->read('Testing');
        $this->assertNull($result);

        $this->Cookie->write('Testing', '');
        $result = $this->Cookie->read('Testing');
        $this->assertEquals('', $result);

        $this->Cookie->write('Testing', false);
        $result = $this->Cookie->read('Testing');
        $this->assertFalse($result);

        $this->Cookie->write('Testing', 1);
        $result = $this->Cookie->read('Testing');
        $this->assertEquals(1, $result);

        $this->Cookie->write('Testing', '0');
        $result = $this->Cookie->read('Testing');
        $this->assertSame('0', $result);

        $this->Cookie->write('Testing', 0);
        $result = $this->Cookie->read('Testing');
        $this->assertSame(0, $result);
    }

    /**
     * test that two write() calls use the expiry.
     *
     * @return void
     */
    public function testWriteMultipleShareExpiry(): void
    {
        $this->Cookie->write('key1', 'value1', false);
        $this->Cookie->write('key2', 'value2', false);

        $name = $this->Cookie->name . '[key1]';
        $result = $this->Controller->response->cookie($name);
        $this->assertWithinMargin(time() + 10, $result['expire'], 2, 'Expiry time is wrong');

        $name = $this->Cookie->name . '[key2]';
        $result = $this->Controller->response->cookie($name);
        $this->assertWithinMargin(time() + 10, $result['expire'], 2, 'Expiry time is wrong');
    }

    /**
     * test write with distant future cookies
     *
     * @return void
     */
    public function testWriteFarFuture(): void
    {
        $this->Cookie->write('Testing', 'value', false, '+90 years');
        $future = new DateTime('now');
        $future->modify('+90 years');

        $expected = [
            'name' => $this->Cookie->name . '[Testing]',
            'value' => 'value',
            'path' => '/',
            'domain' => '',
            'secure' => false,
            'httpOnly' => false];
        $result = $this->Controller->response->cookie($this->Cookie->name . '[Testing]');

        $this->assertEquals($future->format('U'), $result['expire'], '');
        unset($result['expire']);

        $this->assertEquals($expected, $result);
    }

    /**
     * test write with httpOnly cookies
     *
     * @return void
     */
    public function testWriteHttpOnly(): void
    {
        $this->Cookie->httpOnly = true;
        $this->Cookie->secure = false;
        $this->Cookie->write('Testing', 'value', false);
        $expected = [
            'name' => $this->Cookie->name . '[Testing]',
            'value' => 'value',
            'expire' => time() + 10,
            'path' => '/',
            'domain' => '',
            'secure' => false,
            'httpOnly' => true];
        $result = $this->Controller->response->cookie($this->Cookie->name . '[Testing]');
        $this->assertEquals($expected, $result);
    }

    /**
     * test delete with httpOnly
     *
     * @return void
     */
    public function testDeleteHttpOnly(): void
    {
        $this->Cookie->httpOnly = true;
        $this->Cookie->secure = false;
        $this->Cookie->delete('Testing', false);
        $expected = [
            'name' => $this->Cookie->name . '[Testing]',
            'value' => '',
            'expire' => time() - 42000,
            'path' => '/',
            'domain' => '',
            'secure' => false,
            'httpOnly' => true];
        $result = $this->Controller->response->cookie($this->Cookie->name . '[Testing]');
        $this->assertEquals($expected, $result);
    }

    /**
     * testWritePlainCookieArray
     *
     * @return void
     */
    public function testWritePlainCookieArray(): void
    {
        $this->Cookie->write(['name' => 'CakePHP', 'version' => '1.2.0.x', 'tag' => 'CakePHP Rocks!'], null, false);

        $this->assertEquals('CakePHP', $this->Cookie->read('name'));
        $this->assertEquals('1.2.0.x', $this->Cookie->read('version'));
        $this->assertEquals('CakePHP Rocks!', $this->Cookie->read('tag'));

        $this->Cookie->delete('name');
        $this->Cookie->delete('version');
        $this->Cookie->delete('tag');
    }

    /**
     * test writing values that are not scalars
     *
     * @return void
     */
    public function testWriteArrayValues(): void
    {
        $this->Cookie->secure = false;
        $this->Cookie->write('Testing', [1, 2, 3], false);
        $expected = [
            'name' => $this->Cookie->name . '[Testing]',
            'value' => '[1,2,3]',
            'path' => '/',
            'domain' => '',
            'secure' => false,
            'httpOnly' => false];
        $result = $this->Controller->response->cookie($this->Cookie->name . '[Testing]');

        $this->assertWithinMargin($result['expire'], time() + 10, 1);
        unset($result['expire']);
        $this->assertEquals($expected, $result);
    }

    /**
     * Test that writing mixed arrays results in the correct data.
     *
     * @return void
     */
    public function testWriteMixedArray(): void
    {
        $this->Cookie->encrypt = false;
        $this->Cookie->write('User', ['name' => 'mark'], false);
        $this->Cookie->write('User.email', 'mark@example.com', false);
        $expected = [
            'name' => $this->Cookie->name . '[User]',
            'value' => '{"name":"mark","email":"mark@example.com"}',
            'path' => '/',
            'domain' => '',
            'secure' => false,
            'httpOnly' => false,
        ];
        $result = $this->Controller->response->cookie($this->Cookie->name . '[User]');
        unset($result['expire']);

        $this->assertEquals($expected, $result);

        $this->Cookie->write('User.email', 'mark@example.com', false);
        $this->Cookie->write('User', ['name' => 'mark'], false);
        $expected = [
            'name' => $this->Cookie->name . '[User]',
            'value' => '{"name":"mark"}',
            'path' => '/',
            'domain' => '',
            'secure' => false,
            'httpOnly' => false,
        ];
        $result = $this->Controller->response->cookie($this->Cookie->name . '[User]');
        unset($result['expire']);

        $this->assertEquals($expected, $result);
    }

    /**
     * Test that replacing scalar with array works.
     *
     * @return void
     */
    public function testReplaceScalarWithArray(): void
    {
        $this->Cookie->write('foo', 1);
        $this->Cookie->write('foo.bar', 2);

        $data = $this->Cookie->read();
        $expected = [
            'foo' => [
                'bar' => 2,
            ],
        ];
        $this->assertEquals($expected, $data);
    }

    /**
     * testReadingCookieValue
     *
     * @return void
     */
    public function testReadingCookieValue(): void
    {
        $this->_setCookieData();
        $data = $this->Cookie->read();
        $expected = [
            'Encrytped_array' => [
                'name' => 'CakePHP',
                'version' => '1.2.0.x',
                'tag' => 'CakePHP Rocks!'],
            'Encrytped_multi_cookies' => [
                'name' => 'CakePHP',
                'version' => '1.2.0.x',
                'tag' => 'CakePHP Rocks!'],
            'Plain_array' => [
                'name' => 'CakePHP',
                'version' => '1.2.0.x',
                'tag' => 'CakePHP Rocks!'],
            'Plain_multi_cookies' => [
                'name' => 'CakePHP',
                'version' => '1.2.0.x',
                'tag' => 'CakePHP Rocks!']];
        $this->assertEquals($expected, $data);
    }

    /**
     * testDeleteCookieValue
     *
     * @return void
     */
    public function testDeleteCookieValue(): void
    {
        $this->_setCookieData();
        $this->Cookie->delete('Encrytped_multi_cookies.name');
        $data = $this->Cookie->read('Encrytped_multi_cookies');
        $expected = ['version' => '1.2.0.x', 'tag' => 'CakePHP Rocks!'];
        $this->assertEquals($expected, $data);

        $this->Cookie->delete('Encrytped_array');
        $data = $this->Cookie->read('Encrytped_array');
        $this->assertNull($data);

        $this->Cookie->delete('Plain_multi_cookies.name');
        $data = $this->Cookie->read('Plain_multi_cookies');
        $expected = ['version' => '1.2.0.x', 'tag' => 'CakePHP Rocks!'];
        $this->assertEquals($expected, $data);

        $this->Cookie->delete('Plain_array');
        $data = $this->Cookie->read('Plain_array');
        $this->assertNull($data);
    }

    /**
     * test delete() on corrupted/truncated cookie data.
     *
     * @return void
     */
    public function testDeleteCorruptedCookieData(): void
    {
        $this->skipIf(!extension_loaded('mcrypt'), 'No Mcrypt, skipping.');
        $this->Cookie->type('aes');
        $this->Cookie->key = sha1('some bad key');

        $data = $this->_implode(['name' => 'jill', 'age' => 24]);
        // Corrupt the cookie data by slicing some bytes off.
        $_COOKIE['CakeTestCookie'] = [
            'BadData' => substr(Security::encrypt($data, $this->Cookie->key), 0, -5),
        ];

        $this->assertNull($this->Cookie->delete('BadData.name'));
        $this->assertNull($this->Cookie->read('BadData.name'));
    }

    /**
     * testReadingCookieArray
     *
     * @return void
     */
    public function testReadingCookieArray(): void
    {
        $this->_setCookieData();

        $data = $this->Cookie->read('Encrytped_array.name');
        $expected = 'CakePHP';
        $this->assertEquals($expected, $data);

        $data = $this->Cookie->read('Encrytped_array.version');
        $expected = '1.2.0.x';
        $this->assertEquals($expected, $data);

        $data = $this->Cookie->read('Encrytped_array.tag');
        $expected = 'CakePHP Rocks!';
        $this->assertEquals($expected, $data);

        $data = $this->Cookie->read('Encrytped_multi_cookies.name');
        $expected = 'CakePHP';
        $this->assertEquals($expected, $data);

        $data = $this->Cookie->read('Encrytped_multi_cookies.version');
        $expected = '1.2.0.x';
        $this->assertEquals($expected, $data);

        $data = $this->Cookie->read('Encrytped_multi_cookies.tag');
        $expected = 'CakePHP Rocks!';
        $this->assertEquals($expected, $data);

        $data = $this->Cookie->read('Plain_array.name');
        $expected = 'CakePHP';
        $this->assertEquals($expected, $data);

        $data = $this->Cookie->read('Plain_array.version');
        $expected = '1.2.0.x';
        $this->assertEquals($expected, $data);

        $data = $this->Cookie->read('Plain_array.tag');
        $expected = 'CakePHP Rocks!';
        $this->assertEquals($expected, $data);

        $data = $this->Cookie->read('Plain_multi_cookies.name');
        $expected = 'CakePHP';
        $this->assertEquals($expected, $data);

        $data = $this->Cookie->read('Plain_multi_cookies.version');
        $expected = '1.2.0.x';
        $this->assertEquals($expected, $data);

        $data = $this->Cookie->read('Plain_multi_cookies.tag');
        $expected = 'CakePHP Rocks!';
        $this->assertEquals($expected, $data);
    }

    /**
     * testReadingCookieDataOnStartup
     *
     * @return void
     */
    public function testReadingCookieDataOnStartup(): void
    {
        $data = $this->Cookie->read('Encrytped_array');
        $this->assertNull($data);

        $data = $this->Cookie->read('Encrytped_multi_cookies');
        $this->assertNull($data);

        $data = $this->Cookie->read('Plain_array');
        $this->assertNull($data);

        $data = $this->Cookie->read('Plain_multi_cookies');
        $this->assertNull($data);

        $_COOKIE['CakeTestCookie'] = [
                'Encrytped_array' => $this->_encrypt(['name' => 'CakePHP', 'version' => '1.2.0.x', 'tag' => 'CakePHP Rocks!']),
                'Encrytped_multi_cookies' => [
                        'name' => $this->_encrypt('CakePHP'),
                        'version' => $this->_encrypt('1.2.0.x'),
                        'tag' => $this->_encrypt('CakePHP Rocks!')],
                'Plain_array' => '{"name":"CakePHP","version":"1.2.0.x","tag":"CakePHP Rocks!"}',
                'Plain_multi_cookies' => [
                        'name' => 'CakePHP',
                        'version' => '1.2.0.x',
                        'tag' => 'CakePHP Rocks!']];

        $this->Cookie->startup(new CookieComponentTestController());

        $data = $this->Cookie->read('Encrytped_array');
        $expected = ['name' => 'CakePHP', 'version' => '1.2.0.x', 'tag' => 'CakePHP Rocks!'];
        $this->assertEquals($expected, $data);

        $data = $this->Cookie->read('Encrytped_multi_cookies');
        $expected = ['name' => 'CakePHP', 'version' => '1.2.0.x', 'tag' => 'CakePHP Rocks!'];
        $this->assertEquals($expected, $data);

        $data = $this->Cookie->read('Plain_array');
        $expected = ['name' => 'CakePHP', 'version' => '1.2.0.x', 'tag' => 'CakePHP Rocks!'];
        $this->assertEquals($expected, $data);

        $data = $this->Cookie->read('Plain_multi_cookies');
        $expected = ['name' => 'CakePHP', 'version' => '1.2.0.x', 'tag' => 'CakePHP Rocks!'];
        $this->assertEquals($expected, $data);
        $this->Cookie->destroy();
        unset($_COOKIE['CakeTestCookie']);
    }

    /**
     * testReadingCookieDataWithoutStartup
     *
     * @return void
     */
    public function testReadingCookieDataWithoutStartup(): void
    {
        $data = $this->Cookie->read('Encrytped_array');
        $expected = null;
        $this->assertEquals($expected, $data);

        $data = $this->Cookie->read('Encrytped_multi_cookies');
        $expected = null;
        $this->assertEquals($expected, $data);

        $data = $this->Cookie->read('Plain_array');
        $expected = null;
        $this->assertEquals($expected, $data);

        $data = $this->Cookie->read('Plain_multi_cookies');
        $expected = null;
        $this->assertEquals($expected, $data);

        $_COOKIE['CakeTestCookie'] = [
                'Encrytped_array' => $this->_encrypt(['name' => 'CakePHP', 'version' => '1.2.0.x', 'tag' => 'CakePHP Rocks!']),
                'Encrytped_multi_cookies' => [
                        'name' => $this->_encrypt('CakePHP'),
                        'version' => $this->_encrypt('1.2.0.x'),
                        'tag' => $this->_encrypt('CakePHP Rocks!')],
                'Plain_array' => '{"name":"CakePHP","version":"1.2.0.x","tag":"CakePHP Rocks!"}',
                'Plain_multi_cookies' => [
                        'name' => 'CakePHP',
                        'version' => '1.2.0.x',
                        'tag' => 'CakePHP Rocks!']];

        $data = $this->Cookie->read('Encrytped_array');
        $expected = ['name' => 'CakePHP', 'version' => '1.2.0.x', 'tag' => 'CakePHP Rocks!'];
        $this->assertEquals($expected, $data);

        $data = $this->Cookie->read('Encrytped_multi_cookies');
        $expected = ['name' => 'CakePHP', 'version' => '1.2.0.x', 'tag' => 'CakePHP Rocks!'];
        $this->assertEquals($expected, $data);

        $data = $this->Cookie->read('Plain_array');
        $expected = ['name' => 'CakePHP', 'version' => '1.2.0.x', 'tag' => 'CakePHP Rocks!'];
        $this->assertEquals($expected, $data);

        $data = $this->Cookie->read('Plain_multi_cookies');
        $expected = ['name' => 'CakePHP', 'version' => '1.2.0.x', 'tag' => 'CakePHP Rocks!'];
        $this->assertEquals($expected, $data);
        $this->Cookie->destroy();
        unset($_COOKIE['CakeTestCookie']);
    }

    /**
     * Test Reading legacy cookie values.
     *
     * @return void
     */
    public function testReadLegacyCookieValue(): void
    {
        $_COOKIE['CakeTestCookie'] = [
            'Legacy' => ['value' => $this->_oldImplode([1, 2, 3])],
        ];
        $result = $this->Cookie->read('Legacy.value');
        $expected = [1, 2, 3];
        $this->assertEquals($expected, $result);
    }

    /**
     * Test reading empty values.
     *
     * @return void
     */
    public function testReadEmpty(): void
    {
        $_COOKIE['CakeTestCookie'] = [
            'JSON' => '{"name":"value"}',
            'Empty' => '',
            'String' => '{"somewhat:"broken"}',
            'Array' => '{}',
        ];
        $this->assertEquals(['name' => 'value'], $this->Cookie->read('JSON'));
        $this->assertEquals('value', $this->Cookie->read('JSON.name'));
        $this->assertEquals('', $this->Cookie->read('Empty'));
        $this->assertEquals('{"somewhat:"broken"}', $this->Cookie->read('String'));
        $this->assertEquals([], $this->Cookie->read('Array'));
    }

    /**
     * Test reading empty key
     *
     * @return void
     */
    public function testReadEmptyKey(): void
    {
        $_COOKIE['CakeTestCookie'] = [
            '0' => '{"name":"value"}',
            'foo' => ['bar'],
        ];
        $this->assertEquals('value', $this->Cookie->read('0.name'));
        $this->assertEquals('bar', $this->Cookie->read('foo.0'));
    }

    /**
     * test that no error is issued for non array data.
     *
     * @return void
     */
    public function testNoErrorOnNonArrayData(): void
    {
        $this->Cookie->destroy();
        $_COOKIE['CakeTestCookie'] = 'kaboom';

        $this->assertNull($this->Cookie->read('value'));
    }

    /**
     * testCheck method
     *
     * @return void
     */
    public function testCheck(): void
    {
        $this->Cookie->write('CookieComponentTestCase', 'value');
        $this->assertTrue($this->Cookie->check('CookieComponentTestCase'));

        $this->assertFalse($this->Cookie->check('NotExistingCookieComponentTestCase'));
    }

    /**
     * testCheckingSavedEmpty method
     *
     * @return void
     */
    public function testCheckingSavedEmpty(): void
    {
        $this->Cookie->write('CookieComponentTestCase', 0);
        $this->assertTrue($this->Cookie->check('CookieComponentTestCase'));

        $this->Cookie->write('CookieComponentTestCase', '0');
        $this->assertTrue($this->Cookie->check('CookieComponentTestCase'));

        $this->Cookie->write('CookieComponentTestCase', false);
        $this->assertTrue($this->Cookie->check('CookieComponentTestCase'));

        $this->Cookie->write('CookieComponentTestCase', null);
        $this->assertFalse($this->Cookie->check('CookieComponentTestCase'));
    }

    /**
     * testCheckKeyWithSpaces method
     *
     * @return void
     */
    public function testCheckKeyWithSpaces(): void
    {
        $this->Cookie->write('CookieComponent Test', 'test');
        $this->assertTrue($this->Cookie->check('CookieComponent Test'));
        $this->Cookie->delete('CookieComponent Test');

        $this->Cookie->write('CookieComponent Test.Test Case', 'test');
        $this->assertTrue($this->Cookie->check('CookieComponent Test.Test Case'));
    }

    /**
     * testCheckEmpty
     *
     * @return void
     */
    public function testCheckEmpty(): void
    {
        $this->assertFalse($this->Cookie->check());
    }

    /**
     * test that deleting a top level keys kills the child elements too.
     *
     * @return void
     */
    public function testDeleteRemovesChildren(): void
    {
        $_COOKIE['CakeTestCookie'] = [
            'User' => ['email' => 'example@example.com', 'name' => 'mark'],
            'other' => 'value',
        ];
        $this->assertEquals('mark', $this->Cookie->read('User.name'));

        $this->Cookie->delete('User');
        $this->assertNull($this->Cookie->read('User.email'));
        $this->Cookie->destroy();
    }

    /**
     * Test deleting recursively with keys that don't exist.
     *
     * @return void
     */
    public function testDeleteChildrenNotExist(): void
    {
        $this->assertNull($this->Cookie->delete('NotFound'));
        $this->assertNull($this->Cookie->delete('Not.Found'));
    }

    /**
     * Test deleting deep child elements sends correct cookies.
     *
     * @return void
     */
    public function testDeleteDeepChildren(): void
    {
        $_COOKIE = [
            'CakeTestCookie' => [
                'foo' => $this->_encrypt([
                    'bar' => [
                        'baz' => 'value',
                    ],
                ]),
            ],
        ];

        $this->Cookie->delete('foo.bar.baz');

        $cookies = $this->Controller->response->cookie();
        $expected = [
            'CakeTestCookie[foo]' => [
                'name' => 'CakeTestCookie[foo]',
                'value' => '{"bar":[]}',
                'path' => '/',
                'domain' => '',
                'secure' => false,
                'httpOnly' => false,
            ],
        ];

        $expires = Hash::combine($cookies, '{*}.name', '{*}.expire');
        $cookies = Hash::remove($cookies, '{*}.expire');
        $this->assertEquals($expected, $cookies);

        $this->assertWithinMargin($expires['CakeTestCookie[foo]'], time() + 10, 2);
    }

    /**
     * Test destroy works.
     *
     * @return void
     */
    public function testDestroy(): void
    {
        $_COOKIE = [
            'CakeTestCookie' => [
                'foo' => $this->_encrypt([
                    'bar' => [
                        'baz' => 'value',
                    ],
                ]),
                'other' => 'value',
            ],
        ];

        $this->Cookie->destroy();

        $cookies = $this->Controller->response->cookie();
        $expected = [
            'CakeTestCookie[foo]' => [
                'name' => 'CakeTestCookie[foo]',
                'value' => '',
                'path' => '/',
                'domain' => '',
                'secure' => false,
                'httpOnly' => false,
            ],
            'CakeTestCookie[other]' => [
                'name' => 'CakeTestCookie[other]',
                'value' => '',
                'path' => '/',
                'domain' => '',
                'secure' => false,
                'httpOnly' => false,
            ],
        ];

        $expires = Hash::combine($cookies, '{*}.name', '{*}.expire');
        $cookies = Hash::remove($cookies, '{*}.expire');
        $this->assertEquals($expected, $cookies);
        $this->assertWithinMargin($expires['CakeTestCookie[foo]'], time() - 42000, 2);
        $this->assertWithinMargin($expires['CakeTestCookie[other]'], time() - 42000, 2);
    }

    /**
     * Helper method for generating old style encoded cookie values.
     *
     * @return string.
     */
    protected function _oldImplode(array $array): string
    {
        $string = '';
        foreach ($array as $key => $value) {
            $string .= ',' . $key . '|' . $value;
        }

        return substr($string, 1);
    }

    /**
     * Implode method to keep keys are multidimensional arrays
     *
     * @param array $array Map of key and values
     * @return string String in the form key1|value1,key2|value2
     */
    protected function _implode(array $array): string
    {
        return json_encode($array);
    }

    /**
     * encrypt method
     *
     * @param array|string $value
     * @return string
     */
    protected function _encrypt($value): string
    {
        if (is_array($value)) {
            $value = $this->_implode($value);
        }

        return 'Q2FrZQ==.' . base64_encode(Security::cipher($value, $this->Cookie->key));
    }
}
