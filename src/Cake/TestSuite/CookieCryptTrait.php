<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @package       Cake.TestSuite
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

App::uses('Security', 'Utility');

/**
 * Encrypts and decrypts cookie values like `CookieComponent`.
 *
 * Backport of `Cake\Utility\CookieCryptTrait` from CakePHP 5.x
 * (https://github.com/cakephp/cakephp/blob/5.x/src/Utility/CookieCryptTrait.php), adapted
 * to the format of the CakePHP 2 `CookieComponent`: arrays are JSON encoded, encrypted
 * values are prefixed with `Q2FrZQ==.` and base64 encoded, and the modes are `cipher`
 * (the `CookieComponent` default), `aes` and `rijndael`.
 *
 * Used by `IntegrationTestTrait::cookieEncrypted()` and
 * `CookieEncryptedEqualsConstraint`. Classes using this trait must implement
 * `_getCookieEncryptionKey()`.
 *
 * @package       Cake.TestSuite
 */
trait CookieCryptTrait
{
    /**
     * Valid cipher names for encrypted cookies.
     *
     * @var array<string>
     */
    protected array $_validCiphers = ['cipher', 'aes', 'rijndael'];

    /**
     * Returns the encryption key to be used.
     *
     * @return string
     */
    abstract protected function _getCookieEncryptionKey(): string;

    /**
     * Encrypts a value like `CookieComponent::_encrypt()`.
     *
     * @param array|string $value Value to encrypt. Arrays are JSON encoded first.
     * @param string|false $encrypt Encryption mode (`cipher`, `aes` or `rijndael`), or
     *   `false` to only encode arrays without encrypting.
     * @param string|null $key The encryption key. Defaults to `_getCookieEncryptionKey()`.
     * @return string The encoded value.
     * @throws \InvalidArgumentException When the encryption mode is not supported.
     */
    protected function _encrypt(array|string $value, string|false $encrypt, ?string $key = null): string
    {
        if (is_array($value)) {
            $value = (string)json_encode($value);
        }
        if ($encrypt === false) {
            return $value;
        }
        $this->_checkCipher($encrypt);
        $key ??= $this->_getCookieEncryptionKey();
        $cipher = match ($encrypt) {
            'rijndael' => Security::rijndael($value, $key, 'encrypt'),
            'aes' => Security::encrypt($value, $key),
            default => Security::cipher($value, $key),
        };

        return 'Q2FrZQ==.' . base64_encode((string)$cipher);
    }

    /**
     * Decrypts a value like `CookieComponent::_decrypt()`.
     *
     * @param array|string $values Value or values to decrypt.
     * @param string|false $mode Encryption mode (`cipher`, `aes` or `rijndael`), or `false`
     *   when the value is not encrypted.
     * @param string|null $key The encryption key. Defaults to `_getCookieEncryptionKey()`.
     * @return array|string The decrypted value. JSON encoded arrays are decoded.
     * @throws \InvalidArgumentException When the encryption mode is not supported.
     */
    protected function _decrypt(array|string $values, string|false $mode, ?string $key = null): array|string
    {
        if (is_string($values)) {
            return $this->_decode($values, $mode, $key);
        }

        $decrypted = [];
        foreach ($values as $name => $value) {
            $decrypted[$name] = $this->_decrypt($value, $mode, $key);
        }

        return $decrypted;
    }

    /**
     * Decodes and decrypts a single value.
     *
     * @param string $value The value to decode & decrypt.
     * @param string|false $encrypt The encryption mode, or `false`.
     * @param string|null $key The encryption key. Defaults to `_getCookieEncryptionKey()`.
     * @return array|string Decoded value.
     * @throws \InvalidArgumentException When the encryption mode is not supported.
     */
    protected function _decode(string $value, string|false $encrypt, ?string $key): array|string
    {
        $prefix = 'Q2FrZQ==.';
        if ($encrypt === false || !str_starts_with($value, $prefix)) {
            return $this->_explode($value);
        }
        $this->_checkCipher($encrypt);
        $key ??= $this->_getCookieEncryptionKey();
        $value = (string)base64_decode(substr($value, strlen($prefix)));
        $plain = match ($encrypt) {
            'rijndael' => Security::rijndael($value, $key, 'decrypt'),
            'aes' => Security::decrypt($value, $key),
            default => Security::cipher($value, $key),
        };

        return $this->_explode((string)$plain);
    }

    /**
     * Checks that the encryption mode is supported.
     *
     * @param string $encrypt The encryption mode.
     * @return void
     * @throws \InvalidArgumentException When the encryption mode is not supported.
     */
    protected function _checkCipher(string $encrypt): void
    {
        if (!in_array($encrypt, $this->_validCiphers, true)) {
            $msg = sprintf(
                'Invalid encryption cipher. Must be one of %s or false.',
                implode(', ', $this->_validCiphers),
            );
            throw new InvalidArgumentException($msg);
        }
    }

    /**
     * Explodes a string like `CookieComponent::_explode()`.
     *
     * @param string $string A string containing JSON encoded data, or a bare string.
     * @return array|string Map of key and values
     */
    protected function _explode(string $string): array|string
    {
        $first = substr($string, 0, 1);
        if ($first === '{' || $first === '[') {
            $ret = json_decode($string, true);

            return $ret ?? $string;
        }
        $array = [];
        foreach (explode(',', $string) as $pair) {
            $key = explode('|', $pair);
            if (!isset($key[1])) {
                return $key[0];
            }
            $array[$key[0]] = $key[1];
        }

        return $array;
    }
}
