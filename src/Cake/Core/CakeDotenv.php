<?php

declare(strict_types=1);

/**
 * CakeDotenv
 *
 * Loads environment variables from `.env` files.
 *
 * This is a backport of the `.env` support of later CakePHP versions. The CakePHP 3.x,
 * 4.x and 5.x application skeletons (cakephp/app) ship `config/.env.example` and an
 * opt-in block in `config/bootstrap.php` that loads `config/.env` with
 * josegonzalez/dotenv. For CakePHP 2 the loader is part of the core and has no
 * dependencies, and it can also load a `.env` file in the project root.
 *
 * The syntax is compatible with vlucas/phpdotenv v5, the de facto standard `.env`
 * format in PHP (close to the formats of Docker Compose and Node's dotenv):
 *
 *     # Comment
 *     APP_NAME=my_app
 *     export DEBUG=true                      # optional `export ` prefix
 *     SECURITY_SALT='literal $value \n'      # single quotes: no escapes, no expansion
 *     DATABASE_URL="mysql://${DB_HOST}/app"  # double quotes: escapes and ${NAME} expansion
 *     WELCOME_TEXT="first line
 *     second line"                           # double quoted values can span lines
 *     EMPTY_VALUE=                           # empty string
 *     UNSET_ME                               # no `=`: unsets the variable (see load())
 *
 * - Values are always strings. Nothing is converted to booleans, numbers or null
 *   (`007` stays `007`, long digit strings do not overflow).
 * - Names consist of letters, digits, `_` and `.` (Unicode letters allowed) and may be
 *   quoted (`"NAME"=value`). Whitespace around names and values is trimmed.
 * - Unquoted values must not contain whitespace. `#` starts a comment in an unquoted
 *   value (`A=foo#bar` is `foo`) and after a closing quote. `#` inside quotes is literal.
 * - `${NAME}` is expanded in unquoted and double quoted values, using variables defined
 *   earlier in the file or already present in the environment. Undefined variables are
 *   left as is. `$NAME` without braces is not expanded. Use `\$` in double quotes for a
 *   literal `$`.
 * - Escape sequences in double quotes: `\"`, `\\`, `\$`, `\n`, `\r`, `\t`, `\f` and `\v`.
 *   Any other escape sequence is an error. Backslashes in unquoted and single quoted
 *   values are literal.
 * - A UTF-8 byte order mark at the start of the file is ignored. Files must be UTF-8.
 * - Malformed lines throw a CakeDotenvException with the file name and line number.
 *
 * Intentional differences from phpdotenv: error messages contain the file name and the
 * line number; an unterminated double quoted value is an error (phpdotenv silently
 * ignores it and the lines after it); a value containing a NUL byte is an error; there
 * is no option to read files in encodings other than UTF-8. See load() for the loading
 * rules.
 *
 * Loading is opt-in. Call `CakeDotenv::loadDefaults();` at the top of `config/core.php`
 * (`app/Config/core.php` in the legacy layout).
 *
 * @package       Cake.Core
 * @link          https://github.com/vlucas/phpdotenv
 * @link          https://github.com/cakephp/app/blob/5.x/config/bootstrap.php
 */
class CakeDotenv
{
    /**
     * Parser state: start of a value
     *
     * @var int
     */
    protected const STATE_INITIAL = 0;

    /**
     * Parser state: inside an unquoted value
     *
     * @var int
     */
    protected const STATE_UNQUOTED = 1;

    /**
     * Parser state: inside a single quoted value
     *
     * @var int
     */
    protected const STATE_SINGLE_QUOTED = 2;

    /**
     * Parser state: inside a double quoted value
     *
     * @var int
     */
    protected const STATE_DOUBLE_QUOTED = 3;

    /**
     * Parser state: after a backslash inside a double quoted value
     *
     * @var int
     */
    protected const STATE_ESCAPE = 4;

    /**
     * Parser state: after whitespace or a closing quote
     *
     * @var int
     */
    protected const STATE_WHITESPACE = 5;

    /**
     * Parser state: inside a comment
     *
     * @var int
     */
    protected const STATE_COMMENT = 6;

    /**
     * Returns the default `.env` file locations, in order of precedence.
     *
     * 1. `CONFIG . '.env'` - the config directory (`config/.env`, or `app/Config/.env`
     *    in the legacy layout), the location used by the CakePHP 3.x+ app skeleton.
     * 2. `ROOT . DS . '.env'` - the project root.
     *
     * @return array<string> Absolute file paths.
     */
    public static function defaultFiles(): array
    {
        $files = [];
        if (defined('CONFIG')) {
            $files[] = CONFIG . '.env';
        }
        if (defined('ROOT')) {
            $files[] = ROOT . DS . '.env';
        }

        return $files;
    }

    /**
     * Loads the `.env` files returned by defaultFiles().
     *
     * Precedence: real environment variables > config directory `.env` > project root `.env`.
     * Missing files are skipped.
     *
     * @param bool $overwrite Whether to overwrite variables that already exist in the environment.
     * @return array<string, string|null> The variables that were set (null for unset ones).
     * @throws CakeDotenvException When a file cannot be parsed.
     */
    public static function loadDefaults(bool $overwrite = false): array
    {
        return static::load(static::defaultFiles(), $overwrite);
    }

    /**
     * Loads variables from one or more `.env` files into the environment.
     *
     * Each variable is written to putenv(), `$_ENV` and `$_SERVER`, so it is available
     * through env(), getenv(), `$_ENV` and `$_SERVER`.
     *
     * - Missing files are skipped silently.
     * - Variables that already exist in the environment (getenv(), `$_ENV` or `$_SERVER`)
     *   before the call are not changed, unless `$overwrite` is true.
     * - When several files define the same variable, the first file wins. Within a file,
     *   a later definition replaces an earlier one.
     * - A line with a name and no `=` (e.g. `NAME`) unsets the variable, under the same rules.
     * - `${NAME}` references are resolved against the environment at the time the line is
     *   loaded, so they see real environment variables and variables loaded before.
     *
     * @param array<string>|string $files File path or list of file paths, in order of precedence.
     * @param bool $overwrite Whether to overwrite variables that already exist in the environment.
     * @return array<string, string|null> The variables that were set (null for unset ones).
     * @throws CakeDotenvException When a file cannot be read or parsed.
     */
    public static function load(array|string $files, bool $overwrite = false): array
    {
        $loaded = [];
        foreach ((array)$files as $file) {
            if (!is_file($file)) {
                continue;
            }
            $entries = static::_parseEntries(static::_read($file), $file);
            $own = [];
            foreach ($entries as [$name, $value]) {
                $name = (string)$name;
                if (array_key_exists($name, $loaded) && !isset($own[$name])) {
                    // Defined by a file with higher precedence.
                    continue;
                }
                if (!$overwrite && !isset($own[$name]) && static::_exists($name)) {
                    continue;
                }
                if ($value === null) {
                    static::_unset($name);
                    $loaded[$name] = null;
                } else {
                    $value = static::_resolve($value, [static::class, '_get']);
                    static::_set($name, $value);
                    $loaded[$name] = $value;
                }
                $own[$name] = true;
            }
        }

        return $loaded;
    }

    /**
     * Parses a `.env` file without changing the environment.
     *
     * `${NAME}` references are resolved only against variables defined earlier in the file.
     *
     * @param string $file File path.
     * @return array<string, string|null> Variables in file order (null for a name without `=`).
     * @throws CakeDotenvException When the file cannot be read or parsed.
     */
    public static function parseFile(string $file): array
    {
        return static::_parseToArray(static::_read($file), $file);
    }

    /**
     * Parses the contents of a `.env` file without changing the environment.
     *
     * `${NAME}` references are resolved only against variables defined earlier in `$contents`.
     *
     * @param string $contents The `.env` contents.
     * @return array<string, string|null> Variables in order (null for a name without `=`).
     * @throws CakeDotenvException When the contents cannot be parsed.
     */
    public static function parse(string $contents): array
    {
        return static::_parseToArray($contents, null);
    }

    /**
     * Parses contents into an array, resolving references against earlier entries.
     *
     * @param string $contents The `.env` contents.
     * @param string|null $file File path used in error messages.
     * @return array<string, string|null>
     */
    protected static function _parseToArray(string $contents, ?string $file): array
    {
        $result = [];
        foreach (static::_parseEntries($contents, $file) as [$name, $value]) {
            $result[$name] = $value === null ? null : static::_resolve(
                $value,
                function (string $name) use (&$result): ?string {
                    return $result[$name] ?? null;
                },
            );
        }

        return $result;
    }

    /**
     * Reads a file.
     *
     * @param string $file File path.
     * @return string
     * @throws CakeDotenvException When the file cannot be read.
     */
    protected static function _read(string $file): string
    {
        $contents = is_file($file) && is_readable($file) ? file_get_contents($file) : false;
        if ($contents === false) {
            throw new CakeDotenvException(sprintf('Unable to read dotenv file "%s".', $file));
        }

        return $contents;
    }

    /**
     * Splits contents into entries.
     *
     * Each entry is a list of the name and either null (no `=`) or an array with the
     * unresolved characters (`chars`) and the byte offsets of `$` signs that may start
     * a `${NAME}` reference (`vars`).
     *
     * @param string $contents The `.env` contents.
     * @param string|null $file File path used in error messages.
     * @return array<array{0: string, 1: array{chars: string, vars: array<int>}|null}>
     * @throws CakeDotenvException When the contents cannot be parsed.
     */
    protected static function _parseEntries(string $contents, ?string $file): array
    {
        if (str_starts_with($contents, "\xEF\xBB\xBF")) {
            $contents = substr($contents, 3);
        }
        $lines = preg_split('/\r\n|\n|\r/', $contents) ?: [];

        $entries = [];
        $buffer = [];
        $startLine = 0;
        foreach ($lines as $index => $line) {
            if ($buffer === []) {
                $startLine = $index + 1;
                if (static::_isMultilineStart($line)) {
                    $buffer[] = $line;
                    continue;
                }
            } else {
                $buffer[] = $line;
                if (!static::_isMultilineEnd($line, false)) {
                    continue;
                }
                $line = implode("\n", $buffer);
                $buffer = [];
            }

            $trimmed = trim($line, " \n\r\t\0\x0B");
            if ($trimmed === '' || $trimmed[0] === '#') {
                continue;
            }
            $entries[] = static::_parseEntryAt($line, $file, $startLine);
        }
        if ($buffer !== []) {
            // An unterminated double quoted value runs to the end of the contents.
            $entries[] = static::_parseEntryAt(implode("\n", $buffer), $file, $startLine);
        }

        return $entries;
    }

    /**
     * Parses one entry and adds the file name and line number to errors.
     *
     * @param string $entry The entry.
     * @param string|null $file File path used in error messages.
     * @param int $line Line number of the first line of the entry.
     * @return array{0: string, 1: array{chars: string, vars: array<int>}|null}
     * @throws CakeDotenvException When the entry is malformed.
     */
    protected static function _parseEntryAt(string $entry, ?string $file, int $line): array
    {
        try {
            return static::_parseEntry($entry);
        } catch (CakeDotenvException $e) {
            throw new CakeDotenvException(sprintf(
                'Failed to parse dotenv %s on line %d. %s',
                $file === null ? 'contents' : sprintf('file "%s"', $file),
                $line,
                $e->getMessage(),
            ));
        }
    }

    /**
     * Whether a line starts a double quoted value that continues on the next line.
     *
     * @param string $line The line.
     * @return bool
     */
    protected static function _isMultilineStart(string $line): bool
    {
        $pos = strpos($line, '="');
        if ($pos === false) {
            return false;
        }
        $hash = strpos($line, '#');
        if ($hash !== false && $hash < $pos) {
            return false;
        }

        return !static::_isMultilineEnd($line, true);
    }

    /**
     * Whether a line closes a double quoted value.
     *
     * @param string $line The line.
     * @param bool $started Whether the value starts on this line.
     * @return bool
     */
    protected static function _isMultilineEnd(string $line, bool $started): bool
    {
        if ($line === '"') {
            return true;
        }
        $count = preg_match_all('/(?=([^\\\\]"))/', str_replace('\\\\', '', $line));

        return $started ? $count > 1 : $count >= 1;
    }

    /**
     * Parses one entry (`NAME=value`, possibly spanning several lines).
     *
     * @param string $entry The entry.
     * @return array{0: string, 1: array{chars: string, vars: array<int>}|null}
     * @throws CakeDotenvException When the entry is malformed.
     */
    protected static function _parseEntry(string $entry): array
    {
        $parts = explode('=', $entry, 2);
        if (isset($parts[1])) {
            $name = trim($parts[0], " \n\r\t\0\x0B");
            $value = trim($parts[1], " \n\r\t\0\x0B");
        } else {
            $name = $entry;
            $value = null;
        }
        if ($name === '') {
            throw static::_error('an unexpected equals', $entry);
        }

        if (strlen($name) > 6 && str_starts_with($name, 'export') && ctype_space($name[6])) {
            $name = ltrim(substr($name, 6), " \t\n\r\v\f");
        }
        $length = strlen($name);
        if ($length > 2 && ($name[0] === '"' || $name[0] === "'") && $name[0] === $name[$length - 1]) {
            $name = substr($name, 1, -1);
        }
        if (
            preg_match('/\A[a-zA-Z0-9_.]+\z/', $name) !== 1
            && preg_match('/\A[\p{Ll}\p{Lu}\p{M}\p{N}_.]+\z/u', $name) !== 1
        ) {
            throw static::_error('an invalid name', $name);
        }

        return [$name, $value === null ? null : static::_parseValue($value)];
    }

    /**
     * Parses a value.
     *
     * @param string $value The trimmed value.
     * @return array{chars: string, vars: array<int>}
     * @throws CakeDotenvException When the value is malformed.
     */
    protected static function _parseValue(string $value): array
    {
        if ($value === '') {
            return ['chars' => '', 'vars' => []];
        }
        if (str_contains($value, "\0")) {
            // putenv() cannot store NUL bytes.
            throw static::_error('a NUL byte', $value);
        }
        if (preg_match('/\A(?|([^\s\\\\\'"#$]+)|\'([^\']*)\'|"([^"\\\\$]*)")\z/', $value, $matches) === 1) {
            return ['chars' => $matches[1], 'vars' => []];
        }

        preg_match_all('/[\r\n]+|[^\S\r\n]+|\\\\|\'|"|#|\$|[^\s\\\\\'"#$]+/', $value, $tokens);

        $chars = '';
        $vars = [];
        $state = static::STATE_INITIAL;
        foreach ($tokens[0] as $token) {
            switch ($state) {
                case static::STATE_INITIAL:
                case static::STATE_UNQUOTED:
                    if ($token === "'" && $state === static::STATE_INITIAL) {
                        $state = static::STATE_SINGLE_QUOTED;
                    } elseif ($token === '"' && $state === static::STATE_INITIAL) {
                        $state = static::STATE_DOUBLE_QUOTED;
                    } elseif ($token === '#') {
                        $state = static::STATE_COMMENT;
                    } elseif (ctype_space($token) && $state === static::STATE_UNQUOTED) {
                        $state = static::STATE_WHITESPACE;
                    } else {
                        if ($token === '$') {
                            $vars[] = strlen($chars);
                        }
                        $chars .= $token;
                        $state = static::STATE_UNQUOTED;
                    }
                    break;
                case static::STATE_SINGLE_QUOTED:
                    if ($token === "'") {
                        $state = static::STATE_WHITESPACE;
                    } else {
                        $chars .= $token;
                    }
                    break;
                case static::STATE_DOUBLE_QUOTED:
                    if ($token === '"') {
                        $state = static::STATE_WHITESPACE;
                    } elseif ($token === '\\') {
                        $state = static::STATE_ESCAPE;
                    } else {
                        if ($token === '$') {
                            $vars[] = strlen($chars);
                        }
                        $chars .= $token;
                    }
                    break;
                case static::STATE_ESCAPE:
                    if ($token === '"' || $token === '\\' || $token === '$') {
                        $chars .= $token;
                    } elseif (in_array($token[0], ['f', 'n', 'r', 't', 'v'], true)) {
                        $chars .= stripcslashes('\\' . $token[0]) . substr($token, 1);
                    } else {
                        throw static::_error('an unexpected escape sequence', $value);
                    }
                    $state = static::STATE_DOUBLE_QUOTED;
                    break;
                case static::STATE_WHITESPACE:
                    if ($token === '#') {
                        $state = static::STATE_COMMENT;
                    } elseif (!ctype_space($token)) {
                        throw static::_error('unexpected whitespace', $value);
                    }
                    break;
                case static::STATE_COMMENT:
                    break 2;
            }
        }
        if (in_array($state, [static::STATE_SINGLE_QUOTED, static::STATE_DOUBLE_QUOTED, static::STATE_ESCAPE], true)) {
            throw static::_error('a missing closing quote', $value);
        }

        return ['chars' => $chars, 'vars' => $vars];
    }

    /**
     * Resolves `${NAME}` references in a parsed value.
     *
     * References are resolved from the last one to the first, so nested references
     * such as `${${NAME}}` work.
     *
     * @param array{chars: string, vars: array<int>} $value Parsed value.
     * @param callable(string): (string|null) $lookup Returns the value of a variable or null if it is not defined.
     * @return string
     */
    protected static function _resolve(array $value, callable $lookup): string
    {
        $chars = $value['chars'];
        if ($value['vars'] === []) {
            return $chars;
        }
        $vars = $value['vars'];
        rsort($vars);

        $tail = '';
        $cut = strlen($chars);
        foreach ($vars as $offset) {
            $segment = substr($chars, $offset, $cut - $offset) . $tail;
            $tail = (string)preg_replace_callback(
                '/\A\$\{([a-zA-Z0-9_.]+)\}/',
                function (array $matches) use ($lookup): string {
                    return $lookup($matches[1]) ?? $matches[0];
                },
                $segment,
                1,
            );
            $cut = $offset;
        }

        return substr($chars, 0, $cut) . $tail;
    }

    /**
     * Creates a parse error.
     *
     * @param string $cause The cause.
     * @param string $subject The part of the entry that caused the error.
     * @return CakeDotenvException
     */
    protected static function _error(string $cause, string $subject): CakeDotenvException
    {
        $line = strtok($subject, "\n");
        if ($line === false) {
            $line = '';
        }
        if (strlen($line) > 80) {
            $line = mb_strcut($line, 0, 80, 'UTF-8') . '...';
        }

        return new CakeDotenvException(sprintf(
            'Encountered %s at [%s].',
            $cause,
            addcslashes($line, "\0..\37\177"),
        ));
    }

    /**
     * Whether a variable exists in getenv(), `$_ENV` or `$_SERVER`.
     *
     * @param string $name Variable name.
     * @return bool
     */
    protected static function _exists(string $name): bool
    {
        return array_key_exists($name, $_SERVER) || array_key_exists($name, $_ENV) || getenv($name) !== false;
    }

    /**
     * Returns the value of a variable from `$_SERVER`, `$_ENV` or getenv(), as env() does.
     *
     * @param string $name Variable name.
     * @return string|null
     */
    protected static function _get(string $name): ?string
    {
        foreach ([$_SERVER, $_ENV] as $source) {
            if (isset($source[$name]) && is_scalar($source[$name])) {
                return (string)$source[$name];
            }
        }
        $value = getenv($name);

        return $value === false ? null : $value;
    }

    /**
     * Sets a variable in putenv(), `$_ENV` and `$_SERVER`.
     *
     * @param string $name Variable name.
     * @param string $value Value.
     * @return void
     */
    protected static function _set(string $name, string $value): void
    {
        putenv($name . '=' . $value);
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }

    /**
     * Removes a variable from putenv(), `$_ENV` and `$_SERVER`.
     *
     * @param string $name Variable name.
     * @return void
     */
    protected static function _unset(string $name): void
    {
        putenv($name);
        unset($_ENV[$name], $_SERVER[$name]);
    }
}
