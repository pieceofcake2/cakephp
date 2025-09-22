## CHANGELOG

## v2.10.24.3 (Unreleased)

### Breaking Changes

- Remove Xcache cache engine support (Xcache is not compatible with PHP 7.0+)
- Remove Wincache cache engine support (Wincache is not actively maintained for PHP 8.x)

### PHPUnit Compatibility Fixes

- Fix PHPUnit deprecation warnings for `at()` method usage (#2)
- Fix PHPUnit deprecation for `expectError()`, `expectWarning()`, `expectNotice()` methods (#2)
- Fix PHPUnit data provider naming issue in ExceptionRendererTest (#2)
- Fix PHPUnit risky tests by adding missing assertions (#2)
- Fix output buffering issues in tests (#2)
- Skip CookieComponent AES tests when mcrypt extension is not available (#2)

### Test Isolation Improvements

- Fix test isolation issues by properly cleaning up global state (#2):
  - BasicsTest: Clean up Config.language setting in tearDown
  - CakeRequestTest: Clean up HTTP_ACCEPT_LANGUAGE server variable
  - L10nTest: Add tearDown to clean up HTTP_ACCEPT_LANGUAGE
  - I18nTest: Improve state management and simplify clear() method
- Standardize tearDown method pattern across all test files to call parent::tearDown() at the end (#2)

### PHP 8.0+ Compatibility

- Fix MysqlTest for PHP 8.0+ by removing version check and fixing float assertions (#2)
- Fix PostgreSQL `preg_replace()` with null parameter (#2)
- Fix "Trying to access array offset on value of type bool" error in L10n.php (#2)
- Fix I18n and L10n locale handling issues (#2)

### CI/CD Improvements

- Add MySQL 8.0 support to GitHub Actions workflow (#3)
- Add Docker Compose configuration for local testing (#3)

### Other Fixes

- Fix controller tests by setting `autoRender` property (#2)

## v2.10.24.2

### 2025-09-19

- Fix tests for pgsql / sqlite on PHP 8.3, 8.4
- Tests pass for mysql / pgsql / sqlite on PHP 8.0, 8.1, 8.2, 8.3, 8.4
- Fix Sqlite `strtoupper` and `trim` with null.

## v2.10.24.1

### 2025-02-04

- Fixes for PHP 8.4: `session_set_save_handler` accepts object, removed `E_STRICT` reference.
- Removed github action with php code sniffer. It's quite painful to work with. Need to migrate to something newer, that will affect code base as little as possible.

### 2024-11-16

- Inflector fix: str_place with null.

### 2024-09-21

- Added wrapper for PDOException to avoid creating dynamic property `queryString`.

### 2024-07-24

- Csrf vulnerabity fix back ported from Cake PHP 3
- Explicit type hint definition of nullable parameters.

### 2024-06-05

- Removed usage of `strftime`, replaced with Intl extension.

### 2024-05-24

- Fix deprecation error in Model: `Automatic conversion of false to array is deprecated`

### 2024-05-22

- Fix deprecation error in I18n: `Automatic conversion of false to array is deprecated`

### 2024-02-02

- `str_len` deprecation warning fix in CakeResponse (passing null instead of `string`)

### 2024-01-19

- `strotime()` and `preg_split()` in CakeResponse deprecation warning fixes (passing null)

### 2024-01-11

- `preg_replace` deprecation warning fixes (passing null instead of `string`)

### 2023-12-22

- `preg_quote()` passing null fix

### 2023-12-19

- Muted dynamic property creation warnings in Controller.php
- Fix passing a null input to h function (PR kamilwylegala/cakephp2-php8#56)
- Fix Hash class callback callable pattern deprecated (PR kamilwylegala/cakephp2-php8#58)

### 2023-11-13

- Silence dynamic property creation warning in Model.php

### 2023-11-02

- Fixed: unitialized property in Debugger.php

### 2023-10-20

- Fallback to empty string from `env()` in basics.php and request handler.

### 2023-10-19

- Removed usage of deprecated `redis->getKeys()` in favor of `redis->keys()`.
- Added docker-compose setup to run tests locally.

### 2023-09-18

- Fix for `ShellDispatcher` where `null` was passed to `strpos` function.

### 2023-08-18

- Fixed PHP8 deprecation notices. Related mostly to passing null as a `$haystack` value.

### 2023-06-02

- Fixed PHP 8.2 deprecation notices in CakeEvent: `Creation of dynamic property ... is deprecated.`

### 2023-02-19

- Fixed PHP 8.1 MySQL test suite.

### 2023-02-11

- Fixed PostgreSQL test suite.

### 2023-01-30

- `PaginatorHelper` fix.

### 2023-01-22

- Fixed views cache when relative time is specified.

### 2023-01-11

- Fixed test suite to run under PHPUnit 9.5 and PHP8. Big kudos to @tenkoma :clap:

### 2022-10-20

- `MailTransport` fix.

### 2022-10-08

- Support for `full_path` when uploading a file, PHP 8.1 only.

### 2022-09-27

- Fixed multiple `CREATE UNIQUE INDEX` statements from schema shell that did not work on PostgreSQL.

### 2022-03-08

- Fixed passing `params["pass"]` argument to `invokeArgs` when resolving controller action - `array_values` used to avoid problems with named parameters.

### 2022-03-03

- Removed `String` class.

### 2022-03-02

- Fixed `ConsoleErrorHandler::handleError` to respect error suppression.

### 2022-01-31

- Fixed `Folder->read`, `array_values` is used to remove keys to prevent usign named arguments in `call_user_func_array`

### 2022-01-16

- Fix Shell `ReflectionMethod::__construct` default null argument in hasMethod

### 2022-01-15

- Readme file update - more explicit content.

### 2022-01-04

- Fixed more deprecation notices
	- `strtoupper` + `converting false to array` in Mysql.php
	- `preg_match` where `$subject = null` in CakeRoute.php
	- `strtoupper` in DboSource.php
	- Check history for details ☝️


### 2021-12-20

- Fixed deprecation notices in PHP 8.1 for production code implementations:
	- `ArrayAccess`
	- `Countable`
	- `IteratorAggregate`
- PHP 8.0 requirement in composer.json
- **Warning:** Tests are not updated, Cake's tests rely on old version of PHPUnit so running them may show a lot of deprecations notices. Added issue to cover it: kamilwylegala/cakephp2-php8#7

### 2021-02-24

- Fixed ErrorHandler accordingly to PHP8 migration guide. Otherwise, error handler is logging too much and doesn't respect configured `error_reporting`.
