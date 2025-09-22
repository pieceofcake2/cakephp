# CakePHP 2.x - Community Maintained Fork

[![GitHub License](https://img.shields.io/github/license/friendsofcake2/cakephp?label=License)](LICENSE)
[![Packagist Version](https://img.shields.io/packagist/v/friendsofcake2/cakephp?label=Packagist)](https://packagist.org/packages/friendsofcake2/cakephp)
[![Packagist Dependency Version](https://img.shields.io/packagist/dependency-v/friendsofcake2/cakephp/php?logo=php&logoColor=%23FFFFFF&label=PHP&labelColor=%23777BB4&color=%23FFFFFF)](https://packagist.org/packages/friendsofcake2/cakephp)
[![Tests](https://img.shields.io/github/actions/workflow/status/friendsofcake2/cakephp/tests.yml?label=Tests)](https://github.com/friendsofcake2/cakephp/actions/workflows/tests.yml)
[![Codecov](https://img.shields.io/codecov/c/gh/friendsofcake2/cakephp?label=Coverage)](https://codecov.io/gh/friendsofcake2/cakephp)

This is a community-maintained fork of CakePHP 2.x that provides compatibility with PHP 8.0 and newer versions.
The original CakePHP 2.x branch [reached end-of-life in June 2021](https://bakery.cakephp.org/2021/10/02/cakephp_2_eol.html).

> [!IMPORTANT]
> This fork is based on CakePHP 2.10.24. Earlier versions are not supported.

## Supported Versions

| PHP Version | Support Status |
|------------|----------------|
| 8.0 | ✅ Fully Supported |
| 8.1 | ✅ Fully Supported |
| 8.2 | ✅ Fully Supported |
| 8.3 | ✅ Fully Supported |
| 8.4 | ✅ Fully Supported |

## Test Coverage

Tests are actively maintained and run on GitHub Actions with PHPUnit 9.6 and the following database engines:

- MySQL 5.6
- PostgreSQL 9.4
- SQLite

All tests pass on all supported PHP versions (8.0 - 8.4) with all database engines.

## Installation

Install via Composer:

```json
{
    "require": {
        "friendsofcake2/cakephp": "^2.10"
    }
}
```

Then run:
```bash
composer update
```

> [!NOTE]
> This package automatically replaces `cakephp/cakephp` through Composer's `replace` directive, so you don't need to manually remove the original package.
> Simply adding `friendsofcake2/cakephp` to your `composer.json` will seamlessly replace the original CakePHP 2.x installation.

## Security

### Known Vulnerabilities in Original CakePHP 2.10.24

The following security vulnerabilities have been reported in the original CakePHP 2.10.24:

| CVE | Description | Status in this Fork |
|-----|-------------|-------------------|
| [CVE-2015-8379](https://nvd.nist.gov/vuln/detail/CVE-2015-8379) | CSRF protection bypass via _method parameter | ✅ Fixed in [c0fb45e](https://github.com/friendsofcake2/cakephp/commit/c0fb45e79) (*) |
| [CVE-2020-15400](https://nvd.nist.gov/vuln/detail/CVE-2020-15400) | CSRF token fixation (exploitable with XSS) | ❌ Not yet fixed |

> [!WARNING]
> - **CVE-2015-8379**: The fix has been applied, but additional tests from [original commit](https://github.com/cakephp/cakephp/commit/0f818a23a876c01429196bf7623e1e94a50230f0) should be added.
> - **CVE-2020-15400**: Requires HMAC-signed CSRF tokens to prevent token fixation attacks. This fix needs to be backported from [CakePHP 4.x PR #14431](https://github.com/cakephp/cakephp/pull/14431) and [CakePHP 3.x PR #16481](https://github.com/cakephp/cakephp/pull/16481).

## Migration Guide

### Prerequisites

Before migrating to this fork, ensure:
- Your application is running on PHP 7.4
- You're using CakePHP 2.10.24 (earlier versions are not supported)
- Your application uses Composer for dependency management

### From Original CakePHP 2.x

1. **Update to CakePHP 2.10.24 first**: If you're using an earlier version, update to `cakephp/cakephp:2.10.24` on PHP 7.4 first
2. **Ensure PHP 7.4 Compatibility**: Your application must be fully working on PHP 7.4 before migrating to PHP 8.x
3. **Update Composer**: Replace `cakephp/cakephp` with `friendsofcake2/cakephp` in your `composer.json`
4. **Upgrade PHP**: Update your PHP version to 8.0 or newer
5. **Test Thoroughly**: Run your application's test suite to ensure compatibility

### Breaking Changes

#### strftime() Replacement
- `strftime()` function has been deprecated in PHP 8.1 and removed in PHP 8.2
- This fork uses `IntlDateFormatter` via Symfony's ICU Polyfill
- For backward compatibility, `PHP81_BC\strftime` is used as a fallback
- Most date formatting will work identically, but edge cases may produce slightly different output

#### PHPUnit Compatibility
- Framework tests have been migrated to PHPUnit 9.6
- Test methods using deprecated PHPUnit features have been updated:
  - `at()` → `willReturnCallback()` or `willReturnOnConsecutiveCalls()`
  - `expectError()` → `expectException()` with error handlers
  - Data provider methods must not have "test" prefix

## Running Tests

### Using Docker (Recommended)

```bash
# Set up database configuration
cp app/Config/database.php.default app/Config/database.php

# Install dependencies
docker-compose exec web composer install

# Start services
docker-compose up -d

# Run tests with specific database
DB=mysql docker-compose exec web ./vendors/bin/phpunit
DB=pgsql docker-compose exec web ./vendors/bin/phpunit
DB=sqlite docker-compose exec web ./vendors/bin/phpunit
```

### Local Installation

```bash
# Install dependencies
composer install

# Set up database configuration
cp app/Config/database.php.default app/Config/database.php
# Edit database.php with your database credentials

# Run tests
./vendors/bin/phpunit
```

## Contributing

We welcome contributions! Please:

1. Fork the repository
2. Create a feature branch
3. Ensure all tests pass
4. Submit a pull request

### Reporting Issues

If you find any PHP 8.x compatibility issues, please:
- Create an issue with a clear description
- Include PHP version and error messages
- Provide minimal code to reproduce the issue

## Project Goals

This fork aims to:
- ✅ Maintain PHP 8.x compatibility
- ✅ Fix critical bugs and security issues
- ✅ Keep tests passing on all supported platforms
- ❌ Add new features (focus is on compatibility only)

## License

This project maintains the original MIT License from CakePHP. See [LICENSE](LICENSE) for details.

## Acknowledgments

- Original CakePHP 2.x framework by [CakePHP](https://github.com/cakephp/cakephp/tree/2.10.24)
- Initial PHP 8 compatibility work by [kamilwylegala/cakephp2-php8](https://github.com/kamilwylegala/cakephp2-php8)
- All contributors who help maintain this fork
