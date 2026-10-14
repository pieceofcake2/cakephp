## CHANGELOG

## Unreleased

### PHPStan Level 5 (docblocks only) ([PR #56](https://github.com/pieceofcake2/cakephp/pull/56))

- **PHPStan**: `src/Cake` (including `TestSuite`) now passes PHPStan level 5 with `pieceofcake2/phpstan-cakephp2` ^0.2.4, without a baseline. CI runs PHPStan on every pull request
- **No signature changes**: Only docblocks were corrected (`@param`, `@return`, `@var`, `@property`, `@method`, `@param-out`, `@phpstan-impure`). No native parameter, return or property types were added, and no public or protected method or property signature changed, so subclasses in applications and plugins keep working. The only declaration changes are the properties listed below, which were previously created dynamically
- **Internal clean-ups without behavior change**: `extract()` calls were replaced by explicit variables, variables that were only set on some code paths are initialised, `return voidMethod()` statements were split, always-true/false conditions were removed, and internal functions receive values of the documented type (e.g. `ini_set()` values as strings)

### Bug Fixes ([PR #56](https://github.com/pieceofcake2/cakephp/pull/56))

- **Dynamic properties**: Declare `ConsoleOptionParser::$_tokens`, `HelpFormatter::$_parser`, `Scaffold::$redirect` / `$modelClass` / `$modelKey` / `$ScaffoldModel` / `$scaffoldActions`, `MemcachedEngine::$_compiledGroupNames`, `CakeFixtureManager::$_processed`, `CakeTestFixture::$Schema`, `CakeTestSuiteCommand::$_params` and `ControllerTestDispatcher::$response`, which were created dynamically (deprecated as of PHP 8.2). `CakeFixtureInjector` no longer adds `$fixtureManager` to plain PHPUnit test cases, and `Sqlserver::_execute()` throws `PDOExceptionWithQueryString` instead of adding `$queryString` to a `PDOException`
- **Null arguments**: `ConsoleLog`, `CakeText::toList()` and `Shell::shortPath()` no longer pass `null` to internal functions (deprecated as of PHP 8.1)
- **HttpSocketResponse**: Trailer headers after a chunked body no longer cause a fatal error (call to the undefined `_buildHeader()`); they are merged into the response headers
- **App**: `App::import()` with several search paths no longer stops looking after the first path (it could include a directory instead of the file)
- **ExtractTask**: A literal count argument of `__dcn()` / `__dxcn()` no longer overwrites the parser position, which skipped the following calls
- **PaginatorComponent**: `mergeOptions()` ignores the request parameters for an unknown `paramType` instead of throwing a `TypeError`
- **FlashComponent**: The constructor calls `Component::__construct()`, so `$settings` is set
- **PrivateActionException**: The previous exception passed to the constructor is kept
- **AclNode**: The "Model class not found" exception of `node()` has a formatted message
- **CakeNumber**: `fromReadableSize()` treats a non-numeric value with a unit (e.g. `'XMB'`) as invalid (returns `$default` or throws) instead of throwing a `TypeError`
- **Validation**: The deep `luhn()` check returns `false` for values with non-digit characters instead of throwing a `TypeError`, and accepts integers
- **Mysql** / **Sqlserver**: `listSources()` and `Mysql::listDetailedSources()` return an empty list when the query fails instead of calling `closeCursor()` on `false`
- **TestSuite**: `assertTags()` prints the `$fullDebug` output before the assertion fails; `ConsoleOutputStub::write()` returns the number of bytes written like `ConsoleOutput::write()`; `CakeHtmlReporter` / `CakeTextReporter` code coverage output works with php-code-coverage 9

## v2.12.5 (2026-10-15)

> [!IMPORTANT]
> This release fixes three security vulnerabilities. Upgrade as soon as possible. See [UPGRADE.md](UPGRADE.md#v2125):
> - Email header injection in `SmtpTransport` / `MailTransport` ([GHSA-4f9q-3c4x-52cv](https://github.com/pieceofcake2/cakephp/security/advisories/GHSA-4f9q-3c4x-52cv))
> - Path traversal in view, layout and element names ([GHSA-w2mh-x92p-5p95](https://github.com/pieceofcake2/cakephp/security/advisories/GHSA-w2mh-x92p-5p95))
> - Open redirect after login in `AuthComponent` ([GHSA-x4p9-p4cx-gxwx](https://github.com/pieceofcake2/cakephp/security/advisories/GHSA-x4p9-p4cx-gxwx))
>
> Invalid `ORDER BY` directions now trigger an `E_USER_DEPRECATED` error; the generated SQL is unchanged.

### Security Fixes

- **SmtpTransport** / **MailTransport**: Strip CR and LF from email header names and values (folded header lines are kept), so header values built from user input can no longer inject headers or start the body ([GHSA-4f9q-3c4x-52cv](https://github.com/pieceofcake2/cakephp/security/advisories/GHSA-4f9q-3c4x-52cv), the CakePHP 2 equivalent of CVE-2026-77634)
- **View**: View, layout and element file names containing `..` must resolve inside the view paths; files outside them are treated as missing ([GHSA-w2mh-x92p-5p95](https://github.com/pieceofcake2/cakephp/security/advisories/GHSA-w2mh-x92p-5p95), the CakePHP 2 equivalent of CVE-2026-48820)
- **AuthComponent**: `redirectUrl()` collapses protocol-relative URLs (`//host`, `/\host`, `\\host`) stored in `Auth.redirect` to a local path, so a crafted request URL can no longer redirect to another site after login ([GHSA-x4p9-p4cx-gxwx](https://github.com/pieceofcake2/cakephp/security/advisories/GHSA-x4p9-p4cx-gxwx))


### Deprecation: invalid ORDER BY directions ([PR #54](https://github.com/pieceofcake2/cakephp/pull/54))

- **DboSource::order()**: The sort direction is now checked. Valid directions are `ASC` or `DESC`, optionally followed by `NULLS FIRST` / `NULLS LAST`, matched case-insensitively and with any surrounding whitespace (`'asc'`, `' Desc '`, `'desc  nulls  last'`). This applies to array values (`['Post.title' => 'desc']`), the `$direction` argument and a direction written after the field in a string key (`'Post.title DESC'`)
- Any other direction (for example `"='test' DESC"`, `'ASC; ...'`, `'DESC LIMIT 1'`, `'NULLS FIRST'`, or a non-string value) triggers an `E_USER_DEPRECATED` error. The generated SQL is exactly the same as before; invalid directions will throw an `InvalidArgumentException` in a future version (see [UPGRADE.md](UPGRADE.md#unreleased))
- Field / expression handling is unchanged, and `DboSource::expression()` objects are used verbatim without the check

## v2.12.4 (2026-10-14)

> [!NOTE]
> No behavior changes. This release adds the `IntegrationTestTrait` / `EmailTrait` backport for request-level controller tests (see [UPGRADE.md](UPGRADE.md#v2124)) and fixes docblock types and flaky tests.

### Integration Testing Backport ([PR #51](https://github.com/pieceofcake2/cakephp/pull/51))

- **IntegrationTestTrait**: New test trait, a backport of `Cake\TestSuite\IntegrationTestTrait` from CakePHP 5.x ([src/TestSuite/IntegrationTestTrait.php](https://github.com/cakephp/cakephp/blob/5.x/src/TestSuite/IntegrationTestTrait.php)) adapted to CakePHP 2. Dispatches requests through the real `Dispatcher` and captures the `CakeResponse`; usable from any test case (no `ControllerTestCase` needed)
  - Requests: `get()`, `post()`, `put()`, `patch()`, `delete()`, `head()`, `options()` with string URLs, full URLs or routing arrays, array data or a raw body
  - Setup: `configRequest()`, `replaceRequest()`, `requestAsJson()`, `session()`, `cookie()`, `cookieEncrypted()` (`CookieComponent` format), `enableSecurityToken()` / `setUnlockedFields()` (valid `SecurityComponent` `_Token` fields and CSRF token), `enableCsrfToken()`, `enableRetainFlashMessages()`, `disableErrorHandlerMiddleware()` (throw exceptions instead of rendering the error page)
  - Assertions on the status, redirect, headers, content type, body, template, layout, session, flash messages, cookies and sent files (`assertResponseOk()`, `assertRedirect()`, `assertHeader()`, `assertResponseContains()`, `assertTemplate()`, `assertSession()`, `assertFlashMessage()`, `assertCookieEncrypted()`, `assertFileResponse()`, ...), and `viewVariable()`
  - `exit` after a sent response (`Controller::redirect()`, `AuthComponent`, `AssetDispatcher`, redirect routes) is replaced by `IntegrationTestStop`, thrown by the test response `IntegrationTestResponse`; `IntegrationTestDispatcher` dispatches without sending the response
- **Constraints**: PHPUnit constraints backported from [src/TestSuite/Constraint](https://github.com/cakephp/cakephp/tree/5.x/src/TestSuite/Constraint) (`Response/*`, `Session/*`, `View/*`, `Email/*`), named with a `Constraint` suffix (e.g. `StatusOkConstraint`, `FlashParamEqualsConstraint`, `MailSentToConstraint`)
- **EmailTrait** / **TestEmailTransport**: Backport of [src/TestSuite/EmailTrait.php](https://github.com/cakephp/cakephp/blob/5.x/src/TestSuite/EmailTrait.php) and [src/TestSuite/TestEmailTransport.php](https://github.com/cakephp/cakephp/blob/5.x/src/TestSuite/TestEmailTransport.php). Every `CakeEmail` sends with `TestEmailTransport` during the test, and `assertMailCount()`, `assertNoMailSent()`, `assertMailSentTo()`, `assertMailSentFrom()`, `assertMailSentWith()`, `assertMailContains()`, `assertMailContainsText()`, `assertMailContainsHtml()`, `assertMailSubjectContains()`, `assertMailContainsAttachment()` (and `...At()` variants) check the captured emails
- **CakeEmail**: New `CakeEmail::overrideTransport($name)` / `CakeEmail::transportOverride()` to make every instance use one transport (used by `TestEmailTransport::replaceAllTransports()`); no change unless called
- **CookieCryptTrait** (TestSuite): Encrypts and decrypts values in the `CookieComponent` format, backport of `Cake\Utility\CookieCryptTrait`

### Docblock Types ([PR #49](https://github.com/pieceofcake2/cakephp/pull/49))

- **ErrorHandler** / **ExceptionRenderer**: Document the `$exception` parameters of `handleException()`, `_getMessage()`, `_log()`, `ExceptionRenderer::__construct()` and `_getController()` as `Throwable`, matching what `set_exception_handler()` passes on PHP 8 (no behavior change)
- **View**: Document `$subDir` as `string|null` (its default is `null`)
- **AclComponent**: Document `$Aro` / `$Aco` as the ACL adapter's node objects (`Aro|PhpAro|null` / `Aco|PhpAco|null`) instead of `string`
- **Tests**: Fix flaky `testConfigurationConflict` in `MemcacheEngineTest` / `MemcachedEngineTest` (one-second expiry granularity), and `CakeTimeTest::testWasWithinLast` / `testTimeAgoInWords` (the clock can tick between the relative input and "now")

## v2.12.3 (2026-10-14)

> [!IMPORTANT]
> This release contains a security fix and two fixes that change behavior. See [UPGRADE.md](UPGRADE.md#v2123) before upgrading:
> - `CookieComponent` now honors the `'type'` setting in `$components`. Applications that already set it will write cookies with that type, and cookies written before the upgrade can no longer be read.
> - `cake server` now listens on port `8765` by default instead of `80`.

### Xml::build() SSRF Fix ([PR #33](https://github.com/pieceofcake2/cakephp/pull/33))

- **Xml**: `Xml::build()` no longer fetches `https://` URLs when `'readFile' => false` (operator precedence bug that allowed SSRF)

### CookieComponent `'type'` Setting ([PR #34](https://github.com/pieceofcake2/cakephp/pull/34))

- **CookieComponent**: Honor the `'type'` setting (e.g. `'Cookie' => ['type' => 'aes']`), which was previously ignored so cookies were always encrypted with the default `'cipher'` type
- **Behavior change**: Applications that already set `'type'` in `$components` now write cookies with that type, so cookies written before the upgrade (with `'cipher'`) can no longer be read. See UPGRADE.md

### CakeTestLoader Modern Layout ([PR #35](https://github.com/pieceofcake2/cakephp/pull/35))

- **CakeTestLoader**: List test cases from `tests/TestCase` (app and plugins), falling back to `Test/Case`, so `cake test app` without a test case and the web test runner no longer show an empty list for the modern layout

### Sqlserver Default PDO Flags ([PR #36](https://github.com/pieceofcake2/cakephp/pull/36))

- **Sqlserver**: Fix operator precedence in `connect()` so `PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION` is merged into the `'flags'` setting

### ServerShell Default Port ([PR #37](https://github.com/pieceofcake2/cakephp/pull/37))

- **ServerShell**: Change the default port of `cake server` from `80` (requires root) to `8765`, and show the default host and port in the help

### TestShell PHPUnit Version Message ([PR #38](https://github.com/pieceofcake2/cakephp/pull/38))

- **TestShell**: Ask for PHPUnit 9.6 (instead of v3.7) when PHPUnit cannot be loaded

### Documentation Fixes ([PR #39](https://github.com/pieceofcake2/cakephp/pull/39))

- **README.md**: Use `^2.12` in the Composer install example, and link to the Piece of Cake 2 book (https://pieceofcake2.github.io/docs/)
- **UPGRADE.md**: Show the SQL Server `schema` setting as a string per connection (the driver does not accept an array mapping)

## v2.12.2 (2026-10-14)

### PHP 8.6 Support

- **Session handlers**: Support `session.use_strict_mode`, which is enabled by default since PHP 8.6
  - `SessionHandlerAdapter` implements `SessionIdInterface` and `SessionUpdateTimestampHandlerInterface` (PHP 8.6 deprecates session handlers without `create_sid()`, `validateId()` and `updateTimestamp()`)
  - `CacheSession::destroy()` and `DatabaseSession::destroy()` succeed for sessions that were never written, as with PHP's files handler
- **CI**: Add PHP 8.6 to the test matrix as an allowed failure until it is released, and update GitHub Actions to the latest major versions

## v2.12.1 (2026-10-14)

### Bug Fixes

- **ControllerTestCase**: Fix the `@method` annotation of `testAction()` so that IDEs can resolve its parameters and return type

## v2.12.0 (2025-10-09)

### Composer Autoloading Migration ([PR #22](https://github.com/pieceofcake2/cakephp/pull/22))

- **Composer Classmap Autoloading**: Migrated from include-path to Composer classmap autoloading for core and application classes
  - Removed all top-level `App::uses()` declarations from framework core (798 occurrences removed)
  - Framework and application classes are now automatically loaded via Composer's classmap autoloader
  - **App Base Classes**: For backward compatibility, framework classes extending application base classes still use `App::uses()`:
    - `App::uses('AppController', 'Controller')` - for CakeErrorController
    - `App::uses('AppModel', 'Model')` - for I18nModel, AcoAction, Permission
    - `App::uses('AppShell', 'Console/Command')` - for all core shell commands
    - `App::uses('AppHelper', 'View/Helper')` - for all core helpers
    - **Note**: These `App::uses()` calls are unnecessary if your application's `composer.json` includes classmap autoloading configuration similar to [pieceofcake2/app](https://github.com/pieceofcake2/app)
  - **Plugin Classes**: Plugins must continue using `App::uses()` for their classes
    - Example: `App::uses('MyPluginHelper', 'MyPlugin.View/Helper')`
  - **Custom Autoloader Override**: If you need to override autoloading behavior, register your custom autoloader with prepend flag (see UPGRADE.md for details)

## ｖ2.11.0 (2025-10-09)

### Directory Structure Modernization ([PR #21](https://github.com/pieceofcake2/cakephp/pull/21))

- **Modern Directory Layout**: Restructured directory layout to modern standards
  - Moved `lib/Cake/` → `src/Cake/` (framework source code)
  - Moved `lib/Cake/Test/Case/` → `tests/TestCase/` (framework tests)
  - Moved `lib/Cake/Test/Fixture/` → `tests/Fixture/` (test fixtures)
  - Moved `lib/Cake/Console/cake` → `bin/cake` (console executable)
  - Moved `lib/Cake/Config/` → `config/` (framework configuration files at root level)
  - Moved `lib/Cake/VERSION.txt` → `VERSION.txt` (framework version file at root level)
  - Moved template files from `lib/Cake/View/` and `lib/Cake/Templates/` → `templates/` (framework default templates at root level)
  - **Modern Test Directory Support**: Applications and plugins can now use `tests/TestCase/` directory structure
    - Test suite automatically detects both `Test/Case/` (traditional) and `tests/TestCase/` (modern) directories
    - Fixture loader supports both `Test/Fixture/` and `tests/Fixture/` locations
    - Enables gradual migration to modern directory structure for application tests
  - Updated all path references in configuration files (phpcs.xml, phpstan.neon, phpunit.xml.dist, composer.json)
  - Updated all test suite and fixture loader paths to use new directory structure
  - Updated CI workflow and documentation paths
  - Removed obsolete composer scripts (cs-check, test)

- **Framework Configuration Separation**: Separated framework-level configuration from application code
  - Added `CORE_ROOT` constant to reference framework root directory
  - Updated all config file path references from `CAKE . 'Config'` to `CORE_ROOT . DS . 'config'`
  - Moved framework config files (config.php, routes.php, unicode casefolding tables) to root-level `config/` directory
  - Follows modern PHP project structure conventions where `config/` exists at project root level

- **Smart Console Executable**: Enhanced `bin/cake` with intelligent path detection
  - Automatically detects project root by locating `composer.json` from Composer's autoloader
  - When installed as dependency (`vendor/bin/cake`), intelligently determines the application directory:
    - Auto-detects modern `src/` or traditional `app/` directory structure
    - Falls back to `vendor/pieceofcake2/app` for plugin testing scenarios
  - No manual configuration needed
  - Backward compatible with existing installations
  - Simplifies console command execution across different project structures

### Developer Experience Improvements

- **PHPDoc Generic Type Annotations**: Added generic type annotations for better IDE support
  - Added `@template T` to `ClassRegistry::init()` with detailed array shape types for parameters
  - Added `@template T` to `Controller::loadModel()` with PHPStan assertion for dynamic properties
  - Improves type inference and autocompletion for model instantiation in modern IDEs and static analysis tools
  - Supports PHPStan and Psalm for better static analysis

### Legacy Upgrade Plugin Extraction ([PR #19](https://github.com/pieceofcake2/cakephp/pull/19))

- **Legacy Upgrade Functionality Separation**: Extracted legacy upgrade functionality to separate plugin ([pieceofcake2/upgrade](https://github.com/pieceofcake2/upgrade))
  - Removed `UpgradeShell` from core (located at `lib/Cake/Console/Command/UpgradeShell.php`)
  - Updated test files to remove references to `upgrade` command from shell lists
  - The Upgrade plugin is only relevant for upgrading legacy CakePHP 1.3 applications to 2.0, which is extremely rare in 2025
  - **Migration**: Install Upgrade plugin separately if needed: `composer require --dev pieceofcake2/upgrade`
  - **Note**: Most users will never need this plugin

## v2.10.24.8 (2025-10-06)

### Email Header Encoding Compatibility

- **mbstring Extension Fallback**: Added automatic fallback for email header encoding when `mbstring` extension is not loaded ([PR #18](https://github.com/pieceofcake2/cakephp/pull/18))
  - `CakeEmail::_encode()` now checks if `mbstring` extension is loaded using `extension_loaded('mbstring')`
  - Uses native `mb_encode_mimeheader()` when `mbstring` is available (recommended)
  - Automatically falls back to `Multibyte::mimeEncode()` when `mbstring` is not available
  - Removed `mb_encode_mimeheader()` polyfill from `bootstrap.php`
  - Updated `MailTransportTest` to use same fallback logic for test compatibility
  - **Important**: `mb_encode_mimeheader()` is **not available** in Symfony's mbstring polyfill
  - **Strongly recommended**: Install the `mbstring` extension for better compatibility and performance
  - **Note**: While Symfony polyfill provides most mbstring functions, `mb_encode_mimeheader()` requires the native extension

### Bake Plugin Extraction

- **Bake Functionality Separation**: Extracted all Bake-related code to separate plugin ([pieceofcake2/bake](https://github.com/pieceofcake2/bake)) ([PR #17](https://github.com/pieceofcake2/cakephp/pull/17))
  - Removed `BakeShell` and all Bake tasks: `BakeTask`, `ModelTask`, `ControllerTask`, `ViewTask`, `FixtureTask`, `TestTask`, `TemplateTask`, `ProjectTask`, `PluginTask`, `DbConfigTask`, `CommandTask`
  - Removed Bake templates from `lib/Cake/Console/Templates/` directory
  - Removed application skeleton templates from `lib/Cake/Console/Templates/skel/` directory
  - Moved default view templates to `lib/Cake/Templates/` for core framework usage
  - Removed `DbConfigTask` dependency from `AclShell` and `I18nShell` (now shows error message instead)
  - Removed all Bake-related test files and fixtures
  - Updated `.gitignore` and `codecov.yml` to remove Bake-specific entries
  - Allows independent development and versioning of Bake functionality
  - **Migration**: Install Bake plugin separately: `composer require pieceofcake2/bake:^2.0`
  - **Note**: Dispatcher files (`index.php`, `test.php`, `cake`) now available in Bake plugin at `plugins/Bake/Console/Templates/skel/`

## v2.10.24.7 (2025-10-06)

### Application Skeleton Extraction

- **App Skeleton Separation**: Extracted application skeleton to separate package ([pieceofcake2/app](https://github.com/pieceofcake2/app))
  - Removed `app/` directory from core repository
  - Removed `plugins/` directory from core repository (not required for framework testing)
  - Allows independent versioning and modernization of application structure
  - Updated `.gitignore` to remove `/app/Config/database.php` entry

### CakePHP 5.x Migration Support

- **Modern Directory Structure Support**: Added support for CakePHP 5.x-style directory layouts
  - All tests pass with CakePHP 5.x-style directory structure (verified with pieceofcake2/app)
  - Improved flexibility of `APP_DIR` and `WEBROOT_DIR` constants for custom directory layouts
  - Supports both traditional CakePHP 2.x and modern CakePHP 5.x-style directory layouts
  - Enables gradual migration path: modernize folder structure while on CakePHP 2.x, then focus solely on code changes when upgrading to CakePHP 5.x

### Internal Improvements

- **Organization Rename**: Renamed GitHub organization from `friendsofcake2` to `pieceofcake2`
  - Updated all repository references and URLs
  - Updated composer package namespace
  - Does not impact user applications

- **Vendor Directory Naming**: Standardized vendor directory name from `vendors/` to `vendor/` ([PR #16](https://github.com/pieceofcake2/cakephp/pull/16))
  - Changed default `VENDORS` constant to point to `vendor/` (standard Composer convention)
  - Updated all references in test bootstrap files and CI configuration
  - Updated `.gitignore`, `composer.json`, `phpcs.xml`, `phpstan.neon`, and `phpunit.xml.dist`
  - This change only affects CakePHP core testing infrastructure
  - Does not impact user applications

### Documentation

- **README Improvements**: Enhanced documentation for migration path clarity
  - Added visual comparison of traditional vs gradual migration approaches
  - Clarified 3-step migration process with benefits
  - Updated Project Goals to include gradual migration support

## v2.10.24.6 (2025-10-03)

### Bug Fixes

- **Dispatcher**: Fixed `$_SERVER['PHP_SELF']` to be set for all SAPI modes (Apache, nginx, etc.)
  - Previously only set when using PHP's built-in server
  - Ensures correct routing and URL generation across all web server configurations

- **Autoloader**: Prepended App autoloader to ensure priority over Composer autoloader
  - Changed `spl_autoload_register(['App', 'load'])` to `spl_autoload_register(['App', 'load'], true, true)`
  - Added throw flag (second parameter) to throw exception if registration fails
  - Added prepend flag (third parameter) to place App::load() at the front of the autoloader queue
  - Ensures CakePHP's class loading conventions (App::uses, etc.) work correctly

## v2.10.24.5 (2025-10-03)

### SSL/TLS Certificate Handling

- **CA Bundle Modernization**: Replaced bundled cacert.pem with composer/ca-bundle ([PR #15](https://github.com/pieceofcake2/cakephp/pull/15))
  - Added `composer/ca-bundle` ^1.5 as dependency
  - Replaced hardcoded `CAKE/Config/cacert.pem` with `CaBundle::getSystemCaRootBundlePath()`
  - Uses system CA certificate bundle when available (OpenSSL default cert dir/file)
  - Falls back to Mozilla CA bundle provided by composer/ca-bundle
  - Supports both `cafile` (single bundle file) and `capath` (certificate directory) configurations
  - Automatically updated and maintained by Composer ecosystem
  - Removed outdated `lib/Cake/Config/cacert.pem` file (last updated 2016)
  - Updated `FolderTest` to reflect removed cacert.pem file

### PHPUnit Integration

- **PHPUnit 9+ Migration**: Complete migration to PHPUnit 9+ ([PR #14](https://github.com/pieceofcake2/cakephp/pull/14))
  - Refactored `CakeTestLoader` to implement `TestSuiteLoader` interface with composition pattern
  - Fixed critical infinite recursion bug in `CakeTestLoader::reload()` method
  - Updated `CakeBaseReporter` from extending `PHPUnit_TextUI_ResultPrinter` to implementing `ResultPrinter` interface
  - Added missing interface methods: `write()`, `addWarning()`, `addRiskyTest()` to `CakeBaseReporter`
  - Added type declarations throughout TestSuite classes for PHP 8.0+ compatibility
  - Added `run()` method to `CakeTestSuiteCommand` with proper test file resolution
  - Updated all PHPUnit class references from `PHPUnit_Framework_*` to `PHPUnit\Framework\*` namespace
  - Removed legacy `CakeTestRunner` class (functionality moved to `CakeTestSuiteCommand`)
  - Updated test assertions to use `withConsecutive()` and callback-based output verification
  - Fixed `HtmlCoverageReportTest` to use `CakeHtmlReporter` instead of `CakeBaseReporter`
  - All tests now pass with PHPUnit 9.6+

### Code Modernization

- **Legacy PHP Version Checks Removal**: Removed compatibility code for PHP < 8.0 ([PR #14](https://github.com/pieceofcake2/cakephp/pull/14))
  - Removed PHP 5.3/5.4 version checks from `ErrorHandler`, `Debugger`, `CakeSocket`
  - Removed PHP 5.4 fallback logic in `CakeNumber::_numberFormat()`
  - Removed PHP version checks from `CakeTimeTest`
  - Simplified SSL/TLS configuration in `CakeSocket` (removed PHP 5.3.2/5.6.0 checks)
  - Removed obsolete Debugger workarounds for PHP < 5.3
  - Updated `strpos()` to `str_ends_with()` in `CakeTestLoader`
  - Changed `include` to `require_once` in bootstrap files for consistency
  - Fixed indentation in `basics.php` to standardize whitespace

### Performance Improvements

- **JSON Output Optimization**: Improved JSON rendering performance ([PR #14](https://github.com/pieceofcake2/cakephp/pull/14))
  - `JsonView` now only applies `JSON_PRETTY_PRINT` when debug mode is enabled
  - Reduces JSON encoding overhead in production environments

### Autoloading Improvements

- **App::load() Enhancement**: Added class existence check ([PR #14](https://github.com/pieceofcake2/cakephp/pull/14))
  - `App::load()` now returns early if class already exists
  - Prevents unnecessary file loading and improves performance
  - Replaced direct `ShellDispatcher` require with `App::load()` in test bootstrap
  - Added `class_exists()` guard in test bootstrap to prevent double definition errors

### Code Quality

- **Coding Standards**: Applied phpcs fixes and removed deprecated exclusions ([PR #14](https://github.com/pieceofcake2/cakephp/pull/14))
  - Removed `get_magic_quotes_gpc` exclusion from phpcs.xml (function removed in PHP 7.4)
  - Added trailing commas to `trigger_error()` calls for consistency
  - Added `CakeHtmlReporter` to unused variable exclusion list

### Documentation

- **GitHub URLs Update**: Updated all GitHub organization references ([PR #14](https://github.com/pieceofcake2/cakephp/pull/14))
  - Changed GitHub URLs from `cakephp` to `pieceofcake2` in `home.ctp` templates
  - Updated changelog, issues, wiki, and plugin repository links
  - Removed upstream `CONTRIBUTING.md` (fork has different contribution guidelines)

### Dispatcher Improvements

- **Simplified Dispatcher Entry Points**: Streamlined autoload handling in dispatcher files
  - Removed manual `CAKE_CORE_INCLUDE_PATH` detection from dispatcher files (set automatically in bootstrap.php)
  - Removed `include_path` manipulation logic (Composer autoload handles this)
  - Removed legacy `app/Vendor/cakephp/cakephp` path detection
  - Added clear error messages when Composer vendors directory or autoload.php is missing
  - Added clear error messages when CakePHP core is not installed via Composer
  - **Breaking Change**: Projects must use Composer for installation (non-Composer installation no longer supported)
  - **Migration**: If upgrading, copy updated dispatcher files from `vendors/pieceofcake2/cakephp/lib/Cake/Console/Templates/skel/` to your project:
    - `skel/webroot/index.php` → `app/webroot/index.php`
    - `skel/webroot/test.php` → `app/webroot/test.php`
    - `skel/Console/cake` → `app/Console/cake`

### PHP Support

- **PHP 8.5 Support**: Added PHP 8.5 to CI testing matrix ([PR #12](https://github.com/pieceofcake2/cakephp/pull/12))
  - All tests pass on PHP 8.5
  - Confirmed compatibility across all database types (MySQL, PostgreSQL, SQLite, SQL Server)
  - Fixed int cast warnings for values outside int range:
    - `Security::cipher()` - Suppress cipherSeed cast warning (maintains encrypted data compatibility)
    - `PaginatorComponent::paginate()` - Suppress page number cast warning (maintains pagination behavior)
    - `DboSource::limit()` - Suppress limit/offset sprintf warnings (maintains query generation)
    - `Postgres::limit()` - Suppress limit/offset sprintf warnings (maintains query generation)
    - `Sqlite::limit()` - Suppress limit/offset sprintf warnings (maintains query generation)
  - Added return type hints to database `limit()` methods: `?string`

### Dependencies

- **Mbstring Extension**: Made mbstring extension optional with Symfony polyfill fallback
  - Moved `ext-mbstring` from `require` to `suggest` in composer.json
  - Added `symfony/polyfill-mbstring` as required dependency
  - Replaced most `Multibyte` methods with direct `mb_*` function calls (stripos, stristr, strlen, strpos, strrchr, strrichr, strripos, strrpos, strstr, substrCount, substr)
  - Kept original implementations for `strtolower` and `strtoupper` due to compatibility differences with `mb_*` functions
  - Symfony polyfill provides automatic fallback when native mbstring extension is not available

### CI/CD Improvements

- **Qlty Coverage Integration**: Added Qlty code coverage reporting to CI workflow

### PHP 8.0+ Compatibility

- **Mail Function Compatibility**: Updated mail transport for PHP 8.0+ strict typing ([PR #10](https://github.com/pieceofcake2/cakephp/pull/10))
  - Changed `mail()` function parameter defaults from `null` to empty string
  - Added strict type declarations to `MailTransport::_mail()` method
  - Updated method signature: `_mail(string $to, string $subject, string $message, array|string $headers = [], string $params = ''): void`
  - Replaced `@` error suppression with `set_error_handler()` for better error handling
  - Updated `CakeEmail::send()` default parameter from `null` to empty string
  - Removed deprecated `safe_mode` checks (removed in PHP 7.2.0)
  - Removed `MailTransport.php` from phpcs exclusions (now coding standards compliant)

### Database Support

- **Database Charset Configuration**: Moved character set configuration from `SET NAMES` to DSN connection options ([PR #11](https://github.com/pieceofcake2/cakephp/pull/11))
  - **MySQL**: Charset now added directly to DSN (e.g., `mysql:...;charset=utf8`)
  - **PostgreSQL**: Client encoding added to DSN via options parameter (e.g., `pgsql:...;options='--client_encoding=UTF8'`)
  - **PostgreSQL**: `sslmode` parameter is now optional in DSN
  - `setEncoding()` methods still use `SET NAMES` for runtime changes
  - More efficient and reliable than executing `SET NAMES` after connection

- **SQL Server 2022 Support**: Added comprehensive SQL Server 2022 support for testing and development ([PR #9](https://github.com/pieceofcake2/cakephp/pull/9))
  - **Docker Infrastructure**:
    - Added SQL Server 2022 container to docker-compose.yml with automatic database initialization
    - Created custom entrypoint script for automatic database and schema creation (cakephp_test with schemas: dbo, test2, test3)
    - Configured health checks using mssql-tools18 for SQL Server 2022 compatibility
    - Added pdo_sqlsrv extension and Microsoft ODBC Driver installation to Docker web container
  - **SQL Server Driver Modernization**:
    - Implemented schema-based configuration system (replaces cross-database approach)
    - Added default schema support with 'dbo' fallback
    - Improved DSN connection string building with support for port and SSL/TLS options via `options` array
    - Fixed encoding configuration to properly map to PDO constants (e.g., 'utf8' → PDO::SQLSRV_ENCODING_UTF8)
    - Fixed macOS locale issues that caused "collate_byname failed to construct for UTF-8" errors
    - Enhanced NULL length handling for column descriptions
    - Fixed COUNT(DISTINCT field) to properly generate field aliases
    - Implemented XOR-based NOT operator for bit fields (replaces subtraction approach)
    - Improved IDENTITY_INSERT handling with primary key detection
    - Added PRIMARY KEY inline constraint to column definitions
    - Added explicit return type declaration to `describe()` method: `describe($model): array`
  - **CI/CD Integration**:
    - Added SQL Server to GitHub Actions CI matrix
    - Automated ODBC driver installation in CI workflow
    - Streamlined database initialization using Docker volumes
  - **Test Improvements**:
    - Removed obsolete SQL Server workarounds in TranslateBehaviorTest
    - Added SQL Server incompatibility skips for cache query tests
    - Fixed assertion order in CakeSchemaTest for better compatibility
    - Updated SqlserverTest to expect PRIMARY KEY in column definitions
  - **Configuration Changes**:
    - Schema configuration now uses array mapping: `'schema' => ['default' => 'dbo', 'test2' => 'test2', ...]`
    - Connection options (SSL/TLS) now configured via `options` array instead of inline DSN

## v2.10.24.4 (2025-09-25)

### Code Quality

- **PHP CodeSniffer Update**: Upgraded to CakePHP CodeSniffer 5.3 standards ([PR #8](https://github.com/pieceofcake2/cakephp/pull/8))
  - Updated `cakephp/cakephp-codesniffer` from 1.0.0 to 5.3
  - Created comprehensive `phpcs.xml` configuration for CakePHP 2.x compatibility
  - Applied automatic code formatting fixes across 683 files using phpcbf
  - Added exclusion rules for CakePHP 2.x specific patterns (App::uses, double underscore properties, etc.)
  - Fixed switch statement fall-through issues with explicit comments
  - Standardized array alignment using Generic.Formatting.MultipleStatementAlignment
  - Added proper documentation to SessionHandlerAdapter class
  - Configured appropriate exclusions for test files and deprecated functions

### Code Modernization

- **PHP 8 Syntax Modernization**: Complete codebase modernization to PHP 8 syntax ([PR #7](https://github.com/pieceofcake2/cakephp/pull/7))
  - Converted all `array()` syntax to short array syntax `[]`
  - Implemented PHP 8 native string functions (`str_contains()`, `str_starts_with()`, `str_ends_with()`)
  - Replaced `get_class()` with `::class` constant
  - Updated array destructuring from `list()` to `[]`
  - Applied null coalescing operators where appropriate
  - Replaced `dirname(__FILE__)` with `__DIR__` magic constant
  - Converted `dirname(__FILE__, n)` to `dirname(__DIR__, n)` for multi-level parent directories
  - Modernized 600+ files across lib/Cake, app, Test, TestSuite, and Templates directories

### Development Tools

- Added PHPStan for static analysis with CakePHP 2.x specific extension
- Added Rector for automated PHP syntax modernization
- Upgraded PHP CodeSniffer with CakePHP 5.3 standards
- Configured development tools to be excluded from version control

## v2.10.24.3 (2025-09-24)

### Security Fixes

- **CVE-2020-15400**: Fix CSRF token fixation vulnerability by implementing HMAC-signed tokens ([PR #5](https://github.com/pieceofcake2/cakephp/pull/5))
  - Tokens are now cryptographically signed with the application's Security.salt
  - Prevents attackers from fixating tokens through XSS or physical access
  - Maintains backward compatibility with existing legacy tokens

### Security Enhancements

- **CVE-2015-8379**: Added comprehensive test coverage for CSRF protection bypass prevention ([PR #6](https://github.com/pieceofcake2/cakephp/pull/6))
  - Tests for `_method` parameter override handling
  - Tests for custom/invalid HTTP methods requiring CSRF validation
  - Tests confirming safe methods (GET, HEAD, OPTIONS) are exempt
  - Validates that all non-safe methods require CSRF tokens

### Breaking Changes

- Remove Xcache cache engine support (Xcache is not compatible with PHP 7.0+) ([PR #4](https://github.com/pieceofcake2/cakephp/pull/4))
- Remove Wincache cache engine support (Wincache is not actively maintained for PHP 8.x) ([PR #4](https://github.com/pieceofcake2/cakephp/pull/4))
- Add new `getVersion()` method to MySQL and PostgreSQL drivers (returns string) ([PR #3](https://github.com/pieceofcake2/cakephp/pull/3))
- Add new `getServerType()` method to MySQL driver (returns string: 'MySQL', 'MariaDB', or 'Aurora MySQL') ([PR #3](https://github.com/pieceofcake2/cakephp/pull/3))
- Add new `utf8mb4Supported()` method to MySQL driver (returns bool) ([PR #3](https://github.com/pieceofcake2/cakephp/pull/3))
- Add new `integerDisplayWidthDeprecated()` method to MySQL driver (returns bool) ([PR #3](https://github.com/pieceofcake2/cakephp/pull/3))

### PHPUnit Compatibility Fixes

- Fix PHPUnit deprecation warnings for `at()` method usage ([PR #2](https://github.com/pieceofcake2/cakephp/pull/2))
- Fix PHPUnit deprecation for `expectError()`, `expectWarning()`, `expectNotice()` methods ([PR #2](https://github.com/pieceofcake2/cakephp/pull/2))
- Fix PHPUnit data provider naming issue in ExceptionRendererTest ([PR #2](https://github.com/pieceofcake2/cakephp/pull/2))
- Fix PHPUnit risky tests by adding missing assertions ([PR #2](https://github.com/pieceofcake2/cakephp/pull/2))
- Fix output buffering issues in tests ([PR #2](https://github.com/pieceofcake2/cakephp/pull/2))
- Skip CookieComponent AES tests when mcrypt extension is not available ([PR #2](https://github.com/pieceofcake2/cakephp/pull/2))

### Test Isolation Improvements

- Fix test isolation issues by properly cleaning up global state ([PR #2](https://github.com/pieceofcake2/cakephp/pull/2)):
  - BasicsTest: Clean up Config.language setting in tearDown
  - CakeRequestTest: Clean up HTTP_ACCEPT_LANGUAGE server variable
  - L10nTest: Add tearDown to clean up HTTP_ACCEPT_LANGUAGE
  - I18nTest: Improve state management and simplify clear() method
- Standardize tearDown method pattern across all test files to call parent::tearDown() at the end ([PR #2](https://github.com/pieceofcake2/cakephp/pull/2))

### PHP 8.0+ Compatibility

- Fix MysqlTest for PHP 8.0+ by removing version check and fixing float assertions ([PR #2](https://github.com/pieceofcake2/cakephp/pull/2))
- Fix PostgreSQL `preg_replace()` with null parameter ([PR #2](https://github.com/pieceofcake2/cakephp/pull/2))
- Fix "Trying to access array offset on value of type bool" error in L10n.php ([PR #2](https://github.com/pieceofcake2/cakephp/pull/2))
- Fix I18n and L10n locale handling issues ([PR #2](https://github.com/pieceofcake2/cakephp/pull/2))
- Fix PostgreSQL alterSchema null array offset error when field doesn't exist in schema ([PR #3](https://github.com/pieceofcake2/cakephp/pull/3))

### CI/CD Improvements

- Add MySQL 8.0 support to GitHub Actions workflow ([PR #3](https://github.com/pieceofcake2/cakephp/pull/3))
- Add Docker Compose configuration for local testing ([PR #3](https://github.com/pieceofcake2/cakephp/pull/3))
- Replace GitHub Actions services with Docker commands for databases ([PR #3](https://github.com/pieceofcake2/cakephp/pull/3))

### Other Fixes

- Fix controller tests by setting `autoRender` property ([PR #2](https://github.com/pieceofcake2/cakephp/pull/2))

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
