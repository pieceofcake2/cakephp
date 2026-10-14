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
 * @since         CakePHP(tm) v 1.2.0.5432
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

/**
 * Log engine that keeps the messages written by Security::cipher().
 *
 * @package       Cake.Test.Case.Utility
 */
class SecurityTestCaptureLog extends BaseLog
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
    public function write($type, $message)
    {
        static::$messages[] = [$type, $message];
    }
}

/**
 * SecurityTest class
 *
 * @package       Cake.Test.Case.Utility
 */
class SecurityTest extends CakeTestCase
{
    /**
     * sut property
     *
     * @var mixed
     */
    public $sut = null;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        Configure::delete('Security.useOpenSsl');
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        Configure::delete('Security.useOpenSsl');
        CakeLog::drop('security_test_capture');
        SecurityTestCaptureLog::$messages = [];
        $this->_setCipherReported(true);

        parent::tearDown();
    }

    /**
     * Sets whether the 'cipher' deprecation and seed reports were already made.
     *
     * @param bool $reported Value for both flags.
     * @return void
     */
    protected function _setCipherReported($reported)
    {
        Closure::bind(function () use ($reported) {
            static::$_cipherDeprecationReported = $reported;
            static::$_cipherSeedReported = $reported;
        }, null, Security::class)();
    }

    /**
     * Resets the 'cipher' reports and starts capturing log messages.
     *
     * @return void
     */
    protected function _captureCipherReports()
    {
        $this->_setCipherReported(false);
        SecurityTestCaptureLog::$messages = [];
        CakeLog::config('security_test_capture', ['engine' => 'SecurityTestCapture']);
    }

    /**
     * Calls a function and collects the E_USER_DEPRECATED errors it triggers.
     *
     * @param callable $callback Function to call.
     * @param array $errors Collected error messages.
     * @return mixed The return value of $callback.
     */
    protected function _collectDeprecations(callable $callback, &$errors)
    {
        $errors = [];
        set_error_handler(function ($errno, $errstr) use (&$errors) {
            $errors[] = $errstr;

            return true;
        }, E_USER_DEPRECATED);
        try {
            return $callback();
        } finally {
            restore_error_handler();
        }
    }

    /**
     * Returns the captured log messages that contain $needle.
     *
     * @param string $needle Text to look for.
     * @return array<int, string>
     */
    protected function _capturedMessages($needle)
    {
        $found = [];
        foreach (SecurityTestCaptureLog::$messages as $entry) {
            if (strpos($entry[1], $needle) !== false) {
                $found[] = $entry[1];
            }
        }

        return $found;
    }

    /**
     * testInactiveMins method
     *
     * @return void
     */
    public function testInactiveMins()
    {
        Configure::write('Security.level', 'high');
        $this->assertEquals(10, Security::inactiveMins());

        Configure::write('Security.level', 'medium');
        $this->assertEquals(100, Security::inactiveMins());

        Configure::write('Security.level', 'low');
        $this->assertEquals(300, Security::inactiveMins());
    }

    /**
     * testGenerateAuthkey method
     *
     * @return void
     */
    public function testGenerateAuthkey()
    {
        $this->assertEquals(strlen(Security::generateAuthKey()), 40);
    }

    /**
     * testValidateAuthKey method
     *
     * @return void
     */
    public function testValidateAuthKey()
    {
        $authKey = Security::generateAuthKey();
        $this->assertTrue(Security::validateAuthKey($authKey));
    }

    /**
     * testHashInvalidSalt method
     *
     * @return void
     */
    public function testHashInvalidSalt()
    {
        $warningTriggered = false;
        $warningMessage = '';
        set_error_handler(function ($errno, $errstr) use (&$warningTriggered, &$warningMessage) {
            if ($errno === E_WARNING || $errno === E_USER_WARNING) {
                $warningTriggered = true;
                $warningMessage = $errstr;

                return true;
            }

            return false;
        }, E_WARNING | E_USER_WARNING);

        try {
            Security::hash('someKey', 'blowfish', true);
        } finally {
            restore_error_handler();
        }

        $this->assertTrue($warningTriggered, 'Expected warning was not triggered');
        $this->assertSame('Invalid salt: 1 for blowfish Please visit http://www.php.net/crypt and read the appropriate section for building blowfish salts.', $warningMessage);
    }

    /**
     * testHashAnotherInvalidSalt
     *
     * @return void
     */
    public function testHashAnotherInvalidSalt()
    {
        $warningTriggered = false;
        $warningMessage = '';
        set_error_handler(function ($errno, $errstr) use (&$warningTriggered, &$warningMessage) {
            if ($errno === E_WARNING || $errno === E_USER_WARNING) {
                $warningTriggered = true;
                $warningMessage = $errstr;

                return true;
            }

            return false;
        }, E_WARNING | E_USER_WARNING);

        try {
            Security::hash('someKey', 'blowfish', '$1$lksdjoijfaoijs');
        } finally {
            restore_error_handler();
        }

        $this->assertTrue($warningTriggered, 'Expected warning was not triggered');
        $this->assertSame('Invalid salt: $1$lksdjoijfaoijs for blowfish Please visit http://www.php.net/crypt and read the appropriate section for building blowfish salts.', $warningMessage);
    }

    /**
     * testHashYetAnotherInvalidSalt
     *
     * @return void
     */
    public function testHashYetAnotherInvalidSalt()
    {
        $warningTriggered = false;
        $warningMessage = '';
        set_error_handler(function ($errno, $errstr) use (&$warningTriggered, &$warningMessage) {
            if ($errno === E_WARNING || $errno === E_USER_WARNING) {
                $warningTriggered = true;
                $warningMessage = $errstr;

                return true;
            }

            return false;
        }, E_WARNING | E_USER_WARNING);

        try {
            Security::hash('someKey', 'blowfish', '$2a$10$123');
        } finally {
            restore_error_handler();
        }

        $this->assertTrue($warningTriggered, 'Expected warning was not triggered');
        $this->assertSame('Invalid salt: $2a$10$123 for blowfish Please visit http://www.php.net/crypt and read the appropriate section for building blowfish salts.', $warningMessage);
    }

    /**
     * testHashInvalidCost method
     *
     * @return void
     */
    public function testHashInvalidCost()
    {
        $warningTriggered = false;
        $warningMessage = '';

        set_error_handler(function ($errno, $errstr) use (&$warningTriggered, &$warningMessage) {
            if ($errno === E_WARNING || $errno === E_USER_WARNING) {
                $warningTriggered = true;
                $warningMessage = $errstr;

                return true;
            }

            return false;
        }, E_WARNING | E_USER_WARNING);

        try {
            Security::setCost(1000);
        } finally {
            restore_error_handler();
        }

        $this->assertTrue($warningTriggered, 'Expected warning was not triggered');
        $this->assertSame('Invalid value, cost must be between 4 and 31', $warningMessage);
    }

    /**
     * testHash method
     *
     * @return void
     */
    public function testHash()
    {
        $_hashType = Security::$hashType;

        $key = 'someKey';
        $hash = 'someHash';

        $this->assertSame(40, strlen(Security::hash($key, null, false)));
        $this->assertSame(40, strlen(Security::hash($key, 'sha1', false)));
        $this->assertSame(40, strlen(Security::hash($key, null, true)));
        $this->assertSame(40, strlen(Security::hash($key, 'sha1', true)));

        $result = Security::hash($key, null, $hash);
        $this->assertSame($result, 'e38fcb877dccb6a94729a81523851c931a46efb1');

        $result = Security::hash($key, 'sha1', $hash);
        $this->assertSame($result, 'e38fcb877dccb6a94729a81523851c931a46efb1');

        $hashType = 'sha1';
        Security::setHash($hashType);
        $this->assertSame($hashType, Security::$hashType);
        $this->assertSame(40, strlen(Security::hash($key, null, true)));
        $this->assertSame(40, strlen(Security::hash($key, null, false)));

        $this->assertSame(32, strlen(Security::hash($key, 'md5', false)));
        $this->assertSame(32, strlen(Security::hash($key, 'md5', true)));

        $hashType = 'md5';
        Security::setHash($hashType);
        $this->assertSame($hashType, Security::$hashType);
        $this->assertSame(32, strlen(Security::hash($key, null, false)));
        $this->assertSame(32, strlen(Security::hash($key, null, true)));

        if (!function_exists('hash') && !function_exists('mhash')) {
            $this->assertSame(32, strlen(Security::hash($key, 'sha256', false)));
            $this->assertSame(32, strlen(Security::hash($key, 'sha256', true)));
        } else {
            $this->assertSame(64, strlen(Security::hash($key, 'sha256', false)));
            $this->assertSame(64, strlen(Security::hash($key, 'sha256', true)));
        }

        Security::setHash($_hashType);
    }

    /**
     * Test that blowfish doesn't return '' when the salt is ''
     *
     * @return void
     */
    public function testHashBlowfishEmptySalt()
    {
        $test = Security::hash('password', 'blowfish');
        $this->skipIf(!str_contains($test, '$2a$'), 'Blowfish hashes are incorrect.');

        $stored = '';
        $hash = Security::hash('anything', 'blowfish', $stored);
        $this->assertNotEquals($stored, $hash);

        $hash = Security::hash('anything', 'blowfish', false);
        $this->assertNotEquals($stored, $hash);

        $hash = Security::hash('anything', 'blowfish', null);
        $this->assertNotEquals($stored, $hash);
    }

    /**
     * Test that hash() works with blowfish.
     *
     * @return void
     */
    public function testHashBlowfish()
    {
        $test = Security::hash('password', 'blowfish');
        $this->skipIf(!str_contains($test, '$2a$'), 'Blowfish hashes are incorrect.');

        Security::setCost(10);
        $_hashType = Security::$hashType;

        $key = 'someKey';
        $hashType = 'blowfish';
        Security::setHash($hashType);

        $this->assertSame($hashType, Security::$hashType);
        $this->assertSame(60, strlen(Security::hash($key, null, false)));

        $password = $submittedPassword = $key;
        $storedPassword = Security::hash($password);

        $hashedPassword = Security::hash($submittedPassword, null, $storedPassword);
        $this->assertSame($storedPassword, $hashedPassword);

        $submittedPassword = 'someOtherKey';
        $hashedPassword = Security::hash($submittedPassword, null, $storedPassword);
        $this->assertNotSame($storedPassword, $hashedPassword);

        $expected = sha1('customsaltsomevalue');
        $result = Security::hash('somevalue', 'sha1', 'customsalt');
        $this->assertSame($expected, $result);

        $oldSalt = Configure::read('Security.salt');
        Configure::write('Security.salt', 'customsalt');

        $expected = sha1('customsaltsomevalue');
        $result = Security::hash('somevalue', 'sha1', true);
        $this->assertSame($expected, $result);

        Configure::write('Security.salt', $oldSalt);
        Security::setHash($_hashType);
    }

    /**
     * testCipher method
     *
     * @return void
     */
    public function testCipher()
    {
        $length = 10;
        $txt = '';
        for ($i = 0; $i < $length; $i++) {
            $txt .= mt_rand(0, 255);
        }
        $key = 'my_key';
        $result = Security::cipher($txt, $key);
        $this->assertEquals($txt, Security::cipher($result, $key));

        $txt = '';
        $key = 'my_key';
        $result = Security::cipher($txt, $key);
        $this->assertEquals($txt, Security::cipher($result, $key));

        $txt = 123456;
        $key = 'my_key';
        $result = Security::cipher($txt, $key);
        $this->assertEquals($txt, Security::cipher($result, $key));

        $txt = '123456';
        $key = 'my_key';
        $result = Security::cipher($txt, $key);
        $this->assertEquals($txt, Security::cipher($result, $key));
    }

    /**
     * Security::cipher() output does not change for existing seeds.
     *
     * The expected values were generated with the code before the deprecation was added.
     *
     * @return void
     */
    public function testCipherOutputUnchanged()
    {
        $expected = [
            '76859309657453542496749683645' => 'bc2e6fd4b6ef8959f32f08283b30',
            '1234567890' => 'e3fa7487966e7b592f823fba7f70',
            '3804817265' => 'fb8b7eeb8e0efa68ff6795d21b3d',
        ];
        $this->_captureCipherReports();
        foreach ($expected as $seed => $hex) {
            Configure::write('Security.cipherSeed', (string)$seed);
            $this->_collectDeprecations(function () use ($hex) {
                $this->assertSame($hex, bin2hex(Security::cipher('Hello, cipher!', 'my_key')));
                $this->assertSame('Hello, cipher!', Security::cipher(hex2bin($hex), 'my_key'));
            }, $errors);
        }
    }

    /**
     * The 'cipher' deprecation is logged once per process, also with debug off.
     *
     * @return void
     */
    public function testCipherDeprecationLoggedOnce()
    {
        Configure::write('debug', 0);
        Configure::write('Security.cipherSeed', '1234567890');
        $this->_captureCipherReports();

        $errors = [];
        set_error_handler(function ($errno, $errstr) use (&$errors) {
            $errors[] = $errstr;

            return true;
        }, E_USER_DEPRECATED);
        try {
            $first = Security::cipher('secret value 1', 'my_key');
            Security::cipher($first, 'my_key');
            $result = Security::cipher('secret value 2', 'other_key');
        } finally {
            restore_error_handler();
        }

        $this->assertSame([], $errors, 'No E_USER_DEPRECATED with debug off');
        $this->assertCount(1, SecurityTestCaptureLog::$messages);
        $this->assertSame('warning', SecurityTestCaptureLog::$messages[0][0]);
        $this->assertSame(
            'Security::cipher() (called from SecurityTest::testCipherDeprecationLoggedOnce()) is insecure and deprecated. '
            . 'Set CookieComponent to \'type\' => \'aes\' with its own key of at least 32 bytes '
            . '(Security::encrypt() / Security::decrypt() for other data). '
            . 'Existing \'cipher\' cookies cannot be read after the switch. '
            . 'See https://pieceofcake2.github.io/docs/development/configuration.html',
            SecurityTestCaptureLog::$messages[0][1],
        );
        $this->assertStringNotContainsString('secret value', SecurityTestCaptureLog::$messages[0][1]);
        $this->assertStringNotContainsString('my_key', SecurityTestCaptureLog::$messages[0][1]);
        $this->assertSame('secret value 2', Security::cipher($result, 'other_key'));
    }

    /**
     * With debug on, the 'cipher' deprecation is also triggered as E_USER_DEPRECATED, once.
     *
     * @return void
     */
    public function testCipherDeprecationTriggeredOnceInDebug()
    {
        Configure::write('debug', 2);
        Configure::write('Security.cipherSeed', '1234567890');
        $this->_captureCipherReports();

        $this->_collectDeprecations(function () {
            Security::cipher('abc', 'my_key');
            Security::cipher('def', 'my_key');
        }, $errors);

        $this->assertCount(1, $errors);
        $this->assertStringStartsWith('Security::cipher() is insecure and deprecated. ', $errors[0]);
        $this->assertStringContainsString('is insecure and deprecated', $errors[0]);
        $this->assertStringContainsString('\'type\' => \'aes\' with its own key of at least 32 bytes', $errors[0]);
        $this->assertStringContainsString('https://pieceofcake2.github.io/docs/development/configuration.html', $errors[0]);
        $this->assertCount(1, SecurityTestCaptureLog::$messages);
        $this->assertSame($errors[0], SecurityTestCaptureLog::$messages[0][1]);
    }

    /**
     * Nothing is logged when Security::cipher() is not used.
     *
     * @return void
     */
    public function testNoCipherReportWithoutCipher()
    {
        Configure::write('Security.cipherSeed', '76859309657453542496749683645');
        Configure::write('Security.useOpenSsl', true);
        $this->_captureCipherReports();

        $key = str_repeat('k', 32);
        $encrypted = Security::encrypt('some text', $key);
        $this->assertSame('some text', Security::decrypt($encrypted, $key));
        Security::hash('some text');

        $this->assertSame([], SecurityTestCaptureLog::$messages);
    }

    /**
     * Data provider for testCipherSeedOutOfRangeLoggedOnce.
     *
     * @return array<string, array{0: mixed, 1: string}>
     */
    public static function outOfRangeCipherSeedProvider()
    {
        return [
            'old default (29 digits)' => ['76859309657453542496749683645', 'reduces it to seed 0'],
            '27 digits' => ['123456789012345678901234567', 'reduces it to seed 0'],
            'empty' => ['', 'reduces it to seed 0'],
            'zero' => ['0', 'reduces it to seed 0'],
            'zeros' => ['0000', 'reduces it to seed 0'],
            'not digits' => ['abcdef', 'reduces it to seed 0'],
            'not configured' => [null, 'reduces it to seed 0'],
            '2^32' => ['4294967296', 'reduces it to seed 0'],
            '2^32 + 1' => ['4294967297', 'keeps only its lowest 32 bits'],
            '15 digits' => ['770011223369876', 'keeps only its lowest 32 bits'],
            'integer above range' => [770011223369876, 'keeps only its lowest 32 bits'],
            'negative' => ['-5', 'keeps only its lowest 32 bits'],
            'digits with a suffix' => ['12345abc', 'keeps only its lowest 32 bits'],
        ];
    }

    /**
     * An out-of-range Security.cipherSeed is logged once, without the seed itself.
     *
     * @dataProvider outOfRangeCipherSeedProvider
     * @param mixed $seed The seed.
     * @param string $reduced Expected description of what happens to the seed.
     * @return void
     */
    public function testCipherSeedOutOfRangeLoggedOnce($seed, $reduced)
    {
        Configure::write('debug', 0);
        Configure::write('Security.cipherSeed', $seed);
        $this->_captureCipherReports();

        Security::cipher('abc', 'my_key');
        Security::cipher('def', 'my_key');

        $seedMessages = $this->_capturedMessages('Security.cipherSeed is not');
        $this->assertCount(1, $seedMessages);
        $this->assertCount(2, SecurityTestCaptureLog::$messages, 'Deprecation and seed messages');
        $this->assertSame(
            'Security.cipherSeed is not a digits-only number from 1 to 4294967295, so Security::cipher() '
            . $reduced . '. A seed in that range does not make \'cipher\' secure; '
            . 'switch CookieComponent to \'type\' => \'aes\' with its own key of at least 32 bytes. '
            . 'Changing Security.cipherSeed also makes existing \'cipher\' values unreadable. '
            . 'See https://pieceofcake2.github.io/docs/development/configuration.html',
            $seedMessages[0],
        );
        if (is_string($seed) && strlen($seed) > 3) {
            $this->assertStringNotContainsString($seed, $seedMessages[0]);
        }
    }

    /**
     * A seed from 1 to 4294967295 is not reported (only the deprecation is).
     *
     * @return void
     */
    public function testCipherSeedInRangeNotLogged()
    {
        Configure::write('debug', 0);
        foreach (['1', '4294967295', '3804817265', '0001', 1234, 4294967295] as $seed) {
            Configure::write('Security.cipherSeed', $seed);
            $this->_captureCipherReports();
            Security::cipher('abc', 'my_key');
            $this->assertSame([], $this->_capturedMessages('Security.cipherSeed'), var_export($seed, true));
            $this->assertCount(1, SecurityTestCaptureLog::$messages);
        }
    }

    /**
     * Security::isCipherSeedInRange()
     *
     * @return void
     */
    public function testIsCipherSeedInRange()
    {
        foreach (['1', '9', '0001', '4294967295', '3804817265', 1, 4294967295] as $seed) {
            $this->assertTrue(Security::isCipherSeedInRange($seed), var_export($seed, true));
        }
        foreach (['', '0', '000', '4294967296', '04294967296', '76859309657453542496749683645', ' 1', '1 ', '+1', '-1', '1.0', '1e5', 'abc', 0, -1, 4294967296, 1.0, null, true, []] as $seed) {
            $this->assertFalse(Security::isCipherSeedInRange($seed), var_export($seed, true));
        }
    }

    /**
     * testCipherEmptyKey method
     *
     * @return void
     */
    public function testCipherEmptyKey()
    {
        $warningTriggered = false;
        $warningMessage = '';
        set_error_handler(function ($errno, $errstr) use (&$warningTriggered, &$warningMessage) {
            if ($errno === E_WARNING || $errno === E_USER_WARNING) {
                $warningTriggered = true;
                $warningMessage = $errstr;

                return true;
            }

            return false;
        }, E_WARNING | E_USER_WARNING);

        try {
            $txt = 'some_text';
            $key = '';
            Security::cipher($txt, $key);
        } finally {
            restore_error_handler();
        }

        $this->assertTrue($warningTriggered, 'Expected warning was not triggered');
        $this->assertSame('You cannot use an empty key for Security::cipher()', $warningMessage);
    }

    /**
     * testRijndael method
     *
     * @return void
     */
    public function testRijndael()
    {
        $this->skipIf(!function_exists('mcrypt_encrypt'));
        $txt = 'The quick brown fox jumped over the lazy dog.';
        $key = 'DYhG93b0qyJfIxfs2guVoUubWwvniR2G0FgaC9mi';

        $result = Security::rijndael($txt, $key, 'encrypt');
        $this->assertEquals($txt, Security::rijndael($result, $key, 'decrypt'));

        $result = Security::rijndael($key, $txt, 'encrypt');
        $this->assertEquals($key, Security::rijndael($result, $txt, 'decrypt'));

        $result = Security::rijndael('', $key, 'encrypt');
        $this->assertEquals('', Security::rijndael($result, $key, 'decrypt'));

        $key = 'this is my key of over 32 chars, yes it is';
        $result = Security::rijndael($txt, $key, 'encrypt');
        $this->assertEquals($txt, Security::rijndael($result, $key, 'decrypt'));
    }

    /**
     * Test that rijndael() can still decrypt values with a fixed iv.
     *
     * @return void
     */
    public function testRijndaelBackwardCompatibility()
    {
        $this->skipIf(!function_exists('mcrypt_encrypt'));

        $txt = 'The quick brown fox jumped over the lazy dog.';
        $key = 'DYhG93b0qyJfIxfs2guVoUubWwvniR2G0FgaC9mi';

        // Encrypted before random iv
        $value = base64_decode('1WPjnq96LMzLGwNgmudHF+cAIqVUN5DaUZEpf5tm1EzSgt5iYY9o3d66iRI/fKJLTlTVGsa8HzW0jDNitmVXoQ==');
        $this->assertEquals($txt, Security::rijndael($value, $key, 'decrypt'));
    }

    /**
     * testRijndaelInvalidOperation method
     *
     * @return void
     */
    public function testRijndaelInvalidOperation()
    {
        $warningTriggered = false;
        $warningMessage = '';
        set_error_handler(function ($errno, $errstr) use (&$warningTriggered, &$warningMessage) {
            if ($errno === E_WARNING || $errno === E_USER_WARNING) {
                $warningTriggered = true;
                $warningMessage = $errstr;

                return true;
            }

            return false;
        }, E_WARNING | E_USER_WARNING);

        try {
            $txt = 'The quick brown fox jumped over the lazy dog.';
            $key = 'DYhG93b0qyJfIxfs2guVoUubWwvniR2G0FgaC9mi';
            Security::rijndael($txt, $key, 'foo');
        } finally {
            restore_error_handler();
        }

        $this->assertTrue($warningTriggered, 'Expected warning was not triggered');
        $this->assertSame('You must specify the operation for Security::rijndael(), either encrypt or decrypt', $warningMessage);
    }

    /**
     * testRijndaelInvalidKey method
     *
     * @return void
     */
    public function testRijndaelInvalidKey()
    {
        $warningTriggered = false;
        $warningMessage = '';
        set_error_handler(function ($errno, $errstr) use (&$warningTriggered, &$warningMessage) {
            if ($errno === E_WARNING || $errno === E_USER_WARNING) {
                $warningTriggered = true;
                $warningMessage = $errstr;

                return true;
            }

            return false;
        }, E_WARNING | E_USER_WARNING);

        try {
            $txt = 'The quick brown fox jumped over the lazy dog.';
            $key = 'too small';
            Security::rijndael($txt, $key, 'encrypt');
        } finally {
            restore_error_handler();
        }

        $this->assertTrue($warningTriggered, 'Expected warning was not triggered');
        $this->assertSame('You must use a key larger than 32 bytes for Security::rijndael()', $warningMessage);
    }

    /**
     * Test encrypt/decrypt.
     *
     * @return void
     */
    public function testEncryptDecrypt()
    {
        $this->skipIf(!extension_loaded('mcrypt'), 'This test requires mcrypt to be installed');
        $txt = 'The quick brown fox';
        $key = 'This key is longer than 32 bytes long.';
        $result = Security::encrypt($txt, $key);
        $this->assertNotEquals($txt, $result, 'Should be encrypted.');
        $this->assertNotEquals($result, Security::encrypt($txt, $key), 'Each result is unique.');
        $this->assertEquals($txt, Security::decrypt($result, $key));
    }

    /**
     * Tests that encrypted strings are compatible between the mcrypt and openssl engine.
     *
     * @dataProvider plainTextProvider
     * @param string $txt Plain text to be encrypted.
     * @return void
     */
    public function testEncryptDecryptCompatibility($txt)
    {
        $this->skipIf(!extension_loaded('mcrypt'), 'This test requires mcrypt to be installed');
        $this->skipIf(!extension_loaded('openssl'), 'This test requires openssl to be installed');

        $key = '12345678901234567890123456789012';

        Configure::write('Security.useOpenSsl', false);
        $mcrypt = Security::encrypt($txt, $key);

        Configure::write('Security.useOpenSsl', true);
        $openssl = Security::encrypt($txt, $key);

        $this->assertEquals(strlen($mcrypt), strlen($openssl));

        Configure::write('Security.useOpenSsl', false);
        $this->assertEquals($txt, Security::decrypt($mcrypt, $key));
        $this->assertEquals($txt, Security::decrypt($openssl, $key));

        Configure::write('Security.useOpenSsl', true);
        $this->assertEquals($txt, Security::decrypt($mcrypt, $key));
        $this->assertEquals($txt, Security::decrypt($openssl, $key));
    }

    /**
     * Data provider for testEncryptDecryptCompatibility
     *
     * @return array
     */
    public function plainTextProvider()
    {
        return [
            [''],
            ['abcdefg'],
            ['1234567890123456'],
            ['The quick brown fox'],
            ['12345678901234567890123456789012'],
            ['The quick brown fox jumped over the lazy dog.'],
            ['何らかのマルチバイト文字列'],
        ];
    }

    /**
     * Test that changing the key causes decryption to fail.
     *
     * @return void
     */
    public function testDecryptKeyFailure()
    {
        $this->skipIf(!extension_loaded('mcrypt'), 'This test requires mcrypt to be installed');
        $txt = 'The quick brown fox';
        $key = 'This key is longer than 32 bytes long.';
        Security::encrypt($txt, $key);

        $key = 'Not the same key. This one will fail';
        $this->assertFalse(Security::decrypt($txt, $key), 'Modified key will fail.');
    }

    /**
     * Test that decrypt fails when there is an hmac error.
     *
     * @return void
     */
    public function testDecryptHmacFailure()
    {
        $this->skipIf(!extension_loaded('mcrypt'), 'This test requires mcrypt to be installed');
        $txt = 'The quick brown fox';
        $key = 'This key is quite long and works well.';
        $salt = 'this is a delicious salt!';
        $result = Security::encrypt($txt, $key, $salt);

        // Change one of the bytes in the hmac.
        $result[10] = 'x';
        $this->assertFalse(Security::decrypt($result, $key, $salt), 'Modified hmac causes failure.');
    }

    /**
     * Test that changing the hmac salt will cause failures.
     *
     * @return void
     */
    public function testDecryptHmacSaltFailure()
    {
        $this->skipIf(!extension_loaded('mcrypt'), 'This test requires mcrypt to be installed');
        $txt = 'The quick brown fox';
        $key = 'This key is quite long and works well.';
        $salt = 'this is a delicious salt!';
        $result = Security::encrypt($txt, $key, $salt);

        $salt = 'humpty dumpty had a great fall.';
        $this->assertFalse(Security::decrypt($result, $key, $salt), 'Modified salt causes failure.');
    }

    /**
     * Test that short keys cause errors
     *
     * @return void
     */
    public function testEncryptInvalidKey()
    {
        $this->expectException(CakeException::class);
        $this->expectExceptionMessage('Invalid key for encrypt(), key must be at least 256 bits (32 bytes) long.');
        $txt = 'The quick brown fox jumped over the lazy dog.';
        $key = 'this is too short';
        Security::encrypt($txt, $key);
    }

    /**
     * Test encrypting falsey data
     *
     * @return void
     */
    public function testEncryptDecryptFalseyData()
    {
        $this->skipIf(!extension_loaded('mcrypt'), 'This test requires mcrypt to be installed');
        $key = 'This is a key that is long enough to be ok.';

        $result = Security::encrypt('', $key);
        $this->assertSame('', Security::decrypt($result, $key));

        $result = Security::encrypt(false, $key);
        $this->assertSame('', Security::decrypt($result, $key));

        $result = Security::encrypt(null, $key);
        $this->assertSame('', Security::decrypt($result, $key));

        $result = Security::encrypt(0, $key);
        $this->assertSame('0', Security::decrypt($result, $key));

        $result = Security::encrypt('0', $key);
        $this->assertSame('0', Security::decrypt($result, $key));
    }

    /**
     * Test that short keys cause errors
     *
     * @return void
     */
    public function testDecryptInvalidKey()
    {
        $this->expectException(CakeException::class);
        $this->expectExceptionMessage('Invalid key for decrypt(), key must be at least 256 bits (32 bytes) long.');
        $txt = 'The quick brown fox jumped over the lazy dog.';
        $key = 'this is too short';
        Security::decrypt($txt, $key);
    }

    /**
     * Test that empty data cause errors
     *
     * @return void
     */
    public function testDecryptInvalidData()
    {
        $this->expectException(CakeException::class);
        $this->expectExceptionMessage('The data to decrypt cannot be empty.');
        $txt = '';
        $key = 'This is a key that is long enough to be ok.';
        Security::decrypt($txt, $key);
    }

    /**
     * Test the random method.
     *
     * @return void
     */
    public function testRandomBytes()
    {
        $value = Security::randomBytes(16);
        $this->assertSame(16, strlen($value));

        $value = Security::randomBytes(64);
        $this->assertSame(64, strlen($value));
    }
}
