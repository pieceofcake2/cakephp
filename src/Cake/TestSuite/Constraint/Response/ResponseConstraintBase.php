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
 * @package       Cake.TestSuite.Constraint.Response
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Constraint\Constraint;

/**
 * Base class for constraints that inspect a `CakeResponse`.
 *
 * Provides helpers to read the body, headers, cookies and the file of a CakePHP 2
 * response, which has no PSR-7 accessors.
 *
 * Backport of `Cake\TestSuite\Constraint\Response\ResponseBase` from CakePHP 5.x
 * (https://github.com/cakephp/cakephp/blob/5.x/src/TestSuite/Constraint/Response/ResponseBase.php).
 *
 * @package       Cake.TestSuite.Constraint.Response
 */
abstract class ResponseConstraintBase extends Constraint
{
    /**
     * The response to inspect.
     *
     * @var CakeResponse
     */
    protected CakeResponse $response;

    /**
     * Constructor
     *
     * @param CakeResponse|null $response The response to inspect.
     * @throws \PHPUnit\Framework\AssertionFailedError When there is no response (no request was made).
     */
    public function __construct(?CakeResponse $response)
    {
        if (!$response) {
            throw new AssertionFailedError('No response set, cannot assert content.');
        }
        $this->response = $response;
    }

    /**
     * Get the response body as a string.
     *
     * @return string
     */
    protected function _getBodyAsString(): string
    {
        return (string)$this->response->body();
    }

    /**
     * Checks whether the response has a header (case-insensitive).
     *
     * `Content-Type` is always considered set, as `CakeResponse::send()` always sends it.
     *
     * @param string $name The header name.
     * @return bool
     */
    protected function _hasHeader(string $name): bool
    {
        if (strcasecmp($name, 'Content-Type') === 0) {
            return true;
        }
        foreach (array_keys($this->response->header()) as $header) {
            if (strcasecmp((string)$header, $name) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns the values of a header (case-insensitive) as a comma separated string.
     *
     * If no `Content-Type` header was set explicitly, the value `CakeResponse::send()`
     * would send is returned (e.g. `text/html; charset=UTF-8`).
     *
     * @param string $name The header name.
     * @return string The header value, or an empty string when the header is not set.
     */
    protected function _getHeaderLine(string $name): string
    {
        foreach ($this->response->header() as $header => $value) {
            if (strcasecmp((string)$header, $name) === 0) {
                return implode(', ', (array)$value);
            }
        }
        if (strcasecmp($name, 'Content-Type') === 0) {
            return $this->_contentTypeHeader();
        }

        return '';
    }

    /**
     * Builds the `Content-Type` header like `CakeResponse::_setContentType()` does.
     *
     * @return string
     */
    protected function _contentTypeHeader(): string
    {
        $type = (string)$this->response->type();
        $charset = (string)$this->response->charset();
        $whitelist = ['application/javascript', 'application/json', 'application/xml', 'application/rss+xml'];
        if ($charset !== '' && (str_starts_with($type, 'text/') || in_array($type, $whitelist, true))) {
            return $type . '; charset=' . $charset;
        }

        return $type;
    }

    /**
     * Reads a cookie set in the response.
     *
     * @param string $name The cookie name, e.g. `remember` or `CakeCookie[remember]`.
     * @return array|null The cookie options (`name`, `value`, `expire`, ...), or null when not set.
     */
    protected function _readCookie(string $name): ?array
    {
        $cookie = $this->response->cookie($name);

        return is_array($cookie) ? $cookie : null;
    }

    /**
     * Returns the file set with `CakeResponse::file()`.
     *
     * @return File|null
     */
    protected function _getFile(): ?File
    {
        $property = new ReflectionProperty(CakeResponse::class, '_file');
        $property->setAccessible(true);
        $file = $property->getValue($this->response);

        return $file instanceof File ? $file : null;
    }
}
