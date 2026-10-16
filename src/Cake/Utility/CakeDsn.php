<?php
/**
 * DSN (URL) parsing.
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @package       Cake.Utility
 * @since         2.12.7
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

/**
 * Splits a DSN such as `mysql://user:secret@localhost:3306/app?encoding=utf8mb4`
 * into its parts.
 *
 * This is the generic part of `ConnectionManager::parseDsn()` and
 * `CakeEmail::parseDsn()`, which map the parts to configuration keys with
 * their DSN class maps (see `toConfig()`). It follows the pattern of
 * `StaticConfigTrait::parseDsn()` of CakePHP 3.x/4.x/5.x: IPv6 hosts are
 * written in brackets, and a password may contain unencoded `:`, `@`, `/` and
 * `#` characters (the last `@` before the host ends it), but not `?`. A user
 * name may contain `@` (`me@example.com:secret@smtp.example.com`). Encoding
 * reserved characters (`%40` for `@`) always works.
 *
 * @package       Cake.Utility
 */
class CakeDsn
{
    /**
     * Parses a DSN into its parts.
     *
     * The returned array has the key `scheme` and, when they are present in the
     * DSN, `username`, `password`, `host`, `port` and `path`. The user info, host
     * and path are URL-decoded with `rawurldecode()` (so `+` stays `+`), and the
     * port is an integer. `query` is always set: the query string parsed with
     * `parse_str()`, where the values `true`, `false` and `null` become the
     * matching PHP values. An IPv6 host keeps its brackets (`[::1]`).
     *
     * @param string $dsn The DSN to parse.
     * @return array{scheme: string, username?: string, password?: string, host?: string, port?: int, path?: string, query: array<string, mixed>}
     * @throws CakeException When the DSN cannot be parsed.
     */
    public static function parse($dsn)
    {
        $pattern = <<<'REGEXP'
{
    ^
    (?P<scheme>[\w\\\\+.-]+)://
    (?:
        (?P<username>[^:/?#]*?)
        (?::(?P<password>[^?]*?))?
        @
    )?
    (?P<host>\[[^]]+]|[^?#/:@]+)?
    (?::(?P<port>\d+))?
    (?P<path>/[^?#]*)?
    (?:\?(?P<query>[^#]*))?
    (?:\#.*)?
    $
}x
REGEXP;
        if (!is_string($dsn) || !preg_match($pattern, $dsn, $matches, PREG_UNMATCHED_AS_NULL)) {
            throw new CakeException(sprintf('The DSN string "%s" could not be parsed.', static::redact((string)$dsn)));
        }

        $parsed = ['scheme' => strtolower((string)$matches['scheme'])];
        foreach (['username', 'password', 'host', 'port', 'path'] as $key) {
            if ($key === 'port') {
                if (isset($matches['port'])) {
                    $parsed['port'] = (int)$matches['port'];
                }
            } elseif (isset($matches[$key]) && ($key !== 'host' || $matches[$key] !== '')) {
                $parsed[$key] = rawurldecode($matches[$key]);
            }
        }

        $query = [];
        if (isset($matches['query']) && $matches['query'] !== '') {
            parse_str($matches['query'], $query);
        }
        foreach ($query as $key => $value) {
            if ($value === 'true') {
                $query[$key] = true;
            } elseif ($value === 'false') {
                $query[$key] = false;
            } elseif ($value === 'null') {
                $query[$key] = null;
            }
        }
        $parsed['query'] = $query;

        return $parsed;
    }

    /**
     * Hides the user info of a DSN (`scheme://****@host`), for error messages.
     *
     * @param string $dsn The DSN.
     * @return string The DSN without user name and password.
     */
    public static function redact($dsn)
    {
        $at = strrpos($dsn, '@');
        if ($at === false) {
            return $dsn;
        }
        $start = strpos($dsn, '//');
        if ($start === false || $start > $at) {
            $start = strpos($dsn, ':');
            $start = $start === false || $start > $at ? 0 : $start + 1;
        } else {
            $start += 2;
        }

        return substr($dsn, 0, $start) . '****' . substr($dsn, $at);
    }

    /**
     * Builds configuration keys from a DSN with a DSN class map.
     *
     * The scheme of the DSN is looked up in `$classMap`:
     *
     * - A string is a class name in plugin dot syntax (e.g.
     *   `'MyPlugin.Database/MyDb'`). `$generic` maps the parsed parts,
     *   including the query parameters, to the usual configuration keys of
     *   the kind, and the class name is set in `$classKey` unless the query
     *   sets it (so `?datasource=`, `?transport=` and `?engine=` override the
     *   class).
     * - A callable that is not a string (a closure, `[$class, 'method']` or an
     *   invokable object) is called as `function (array $parts, array $config): array`
     *   with the parts returned by `parse()` and the configuration without the
     *   `'dsn'` key, and returns the configuration keys (including the class
     *   and the query parameters it wants to use).
     *
     * A scheme that is not in the map is accepted only when the query sets
     * `$classKey`; `$generic` is used then.
     *
     * @param string $dsn The DSN.
     * @param array<string, mixed> $classMap The DSN class map (scheme => class name or callable).
     * @param string $classKey The configuration key of the class (`datasource`, `transport`, `engine`).
     * @param callable $generic Maps the parts of a DSN, including the query parameters, to configuration keys for a class name: `function (array $parts): array`.
     * @param array $config The configuration the DSN is merged into, for callables.
     * @return array The configuration keys from the DSN.
     * @throws CakeException When the DSN cannot be parsed, its scheme is not supported or a callable does not return an array.
     */
    public static function toConfig($dsn, $classMap, $classKey, $generic, $config = [])
    {
        $parts = static::parse($dsn);
        $scheme = $parts['scheme'];
        $target = $classMap[$scheme] ?? null;
        if ($target !== null && !is_string($target)) {
            if (!is_callable($target)) {
                throw new CakeException(sprintf('The DSN class map entry of the scheme "%s" is neither a class name nor a callable.', $scheme));
            }
            $result = call_user_func($target, $parts, $config);
            if (!is_array($result)) {
                throw new CakeException(sprintf('The DSN class map callable of the scheme "%s" must return an array.', $scheme));
            }

            return $result;
        }
        if ($target === null && empty($parts['query'][$classKey])) {
            throw new CakeException(sprintf(
                'The DSN scheme "%s" is not supported. Use one of %s, register it with setDsnClassMap(), or set the class with a "%s" query parameter.',
                $scheme,
                implode(', ', array_keys($classMap)),
                $classKey,
            ));
        }
        $result = call_user_func($generic, $parts);
        if ($target !== null && !isset($parts['query'][$classKey])) {
            $result[$classKey] = $target;
        }

        return $result;
    }

    /**
     * Normalizes the schemes of a DSN class map to lower case and merges it
     * into an existing map. A `null` value removes the scheme.
     *
     * @param array<string, mixed> $map The schemes to add.
     * @param array<string, mixed> $current The current map.
     * @return array<string, mixed> The merged map.
     */
    public static function mergeClassMap($map, $current)
    {
        foreach ($map as $scheme => $target) {
            $scheme = strtolower((string)$scheme);
            if ($target === null) {
                unset($current[$scheme]);
            } else {
                $current[$scheme] = $target;
            }
        }

        return $current;
    }

    /**
     * Merges the DSN in the `'dsn'` key of a configuration into it.
     *
     * The keys parsed from the DSN replace the same keys of the configuration
     * (as `StaticConfigTrait::setConfig()` of CakePHP 3.x and later does), and
     * the other keys are kept. The `'dsn'` key is removed. An empty `'dsn'`
     * (`null`, `''` or `false`, e.g. `env('DATABASE_URL')` when the variable
     * is not set) is ignored.
     *
     * @param mixed $config The configuration.
     * @param callable $parser Parses the DSN: `function (string $dsn, array $config): array`.
     * @param string $description What the configuration is, for error messages (e.g. `the "default" datasource configuration`).
     * @param string $exceptionClass The exception class to throw.
     * @return mixed The configuration without the `'dsn'` key.
     * @throws CakeException When the DSN is invalid (an instance of `$exceptionClass`).
     */
    public static function expandConfig($config, $parser, $description, $exceptionClass = 'CakeException')
    {
        if (!is_array($config) || !array_key_exists('dsn', $config)) {
            return $config;
        }
        $dsn = $config['dsn'];
        unset($config['dsn']);
        if ($dsn === null || $dsn === '' || $dsn === false) {
            return $config;
        }
        try {
            $parsed = call_user_func($parser, (string)$dsn, $config);
        } catch (CakeException $e) {
            throw new $exceptionClass(sprintf('Invalid "dsn" of %s: %s', $description, $e->getMessage()));
        }

        return $parsed + $config;
    }
}
