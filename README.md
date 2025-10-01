# CakePHP 2.x - Community Maintained Fork

[![GitHub License](https://img.shields.io/github/license/friendsofcake2/cakephp?label=License)](LICENSE)
[![Packagist Version](https://img.shields.io/packagist/v/friendsofcake2/cakephp?label=Packagist)](https://packagist.org/packages/friendsofcake2/cakephp)
[![Packagist Dependency Version](https://img.shields.io/packagist/dependency-v/friendsofcake2/cakephp/php?logo=php&logoColor=%23FFFFFF&label=PHP&labelColor=%23777BB4&color=%23FFFFFF)](https://packagist.org/packages/friendsofcake2/cakephp)
[![CI](https://img.shields.io/github/actions/workflow/status/friendsofcake2/cakephp/CI.yml?label=CI)](https://github.com/friendsofcake2/cakephp/actions/workflows/CI.yml)
[![Codecov](https://img.shields.io/codecov/c/gh/friendsofcake2/cakephp?label=Coverage)](https://codecov.io/gh/friendsofcake2/cakephp)

This is a community-maintained fork of CakePHP 2.x that provides compatibility with PHP 8.0 and newer versions.
The original CakePHP 2.x branch [reached End of Life in June 2021](https://bakery.cakephp.org/2021/10/02/cakephp_2_eol.html).

> [!IMPORTANT]
> This fork is based on CakePHP 2.10.24. Earlier versions are not supported.

> [!WARNING]
> **Do not use CakePHP 2.x for new projects!** This fork is only for maintaining existing legacy applications.
> For new projects, please use [CakePHP 5.x](https://cakephp.org/) which has modern PHP support, better performance, and active development.

[CakePHP 2.x Documentation](https://book.cakephp.org/2/en/) | [CHANGELOG](CHANGELOG.md)

## Requirements & Compatibility

### PHP Versions

- PHP 8.0, 8.1, 8.2, 8.3, 8.4, 8.5

### Database Support

- MySQL 5.6, 5.7, 8.0+ (with `pdo_mysql` extension)
- PostgreSQL 9.4+ (with `pdo_pgsql` extension)
- SQLite 3 (with `pdo_sqlite` extension)
- Microsoft SQL Server 2022+ (with `pdo_sqlsrv` extension)

### Required PHP Extensions

- `mbstring` - Multi-byte string support
- `intl` - Internationalization support (optional, uses Symfony polyfill as fallback)
- `openssl` - OpenSSL support (optional, required for SSL/TLS connections and encryption)
- `mcrypt` - Mcrypt support (optional, deprecated in PHP 7.1+, only for legacy AES encryption)

### Testing

- All tests pass with PHPUnit 9.6 across all supported PHP versions and databases

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
> This package uses Composer's `replace` directive to replace `cakephp/cakephp`.
> This ensures that all plugins and packages that depend on `cakephp/cakephp:^2.x` will continue to work correctly with this fork.

## Security

### Known Vulnerabilities in Original CakePHP 2.10.24

The following security vulnerabilities have been reported in the original CakePHP 2.10.24:

| CVE | Description | Status in this Fork |
|-----|-------------|-------------------|
| [CVE-2015-8379](https://nvd.nist.gov/vuln/detail/CVE-2015-8379) | CSRF protection bypass via _method parameter | ✅ Fixed in [c0fb45e](https://github.com/friendsofcake2/cakephp/commit/c0fb45e79), tests in [PR #6](https://github.com/friendsofcake2/cakephp/pull/6) |
| [CVE-2020-15400](https://nvd.nist.gov/vuln/detail/CVE-2020-15400) | CSRF token fixation (exploitable with XSS) | ✅ Fixed in [PR #5](https://github.com/friendsofcake2/cakephp/pull/5) |

> [!NOTE]
> - **CVE-2015-8379**: The fix has been fully applied with comprehensive test coverage for `_method` parameter handling and custom HTTP methods.
> - **CVE-2020-15400**: Fixed by implementing HMAC-signed CSRF tokens that are cryptographically bound to the application. Tokens are now signed with the application's Security.salt, preventing token fixation attacks while maintaining backward compatibility with existing tokens.

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

#### 1. Cache Engines Removed ([PR #4](https://github.com/friendsofcake2/cakephp/pull/4))

**Breaking Change:**
- **Xcache** support has been removed (not compatible with PHP 7.0+)
- **Wincache** support has been removed (not actively maintained for PHP 8.x)

**Migration:**
- If using these cache engines, migrate to Redis, Memcached, or APCu

#### 2. Database Driver Methods Added ([PR #3](https://github.com/friendsofcake2/cakephp/pull/3))

**Breaking Change:**
- New methods added to database drivers (may cause issues if you have custom driver implementations)

**MySQL Driver (Mysql.php):**
- `getVersion(): string` - Returns MySQL/MariaDB/Aurora MySQL version
- `getServerType(): string` - Returns 'MySQL', 'MariaDB', or 'Aurora MySQL'
- `utf8mb4Supported(): bool` - Checks utf8mb4 character set support
- `integerDisplayWidthDeprecated(): bool` - Checks if integer display width is deprecated (MySQL 8.0.17+)

**PostgreSQL Driver (Postgres.php):**
- `getVersion(): string` - Returns PostgreSQL version

**Migration:**
- If you have custom database drivers extending these classes, implement these methods

#### 3. Database Charset Configuration Changes ([PR #11](https://github.com/friendsofcake2/cakephp/pull/11))

**Breaking Change:**
- Character set configuration moved from `SET NAMES` to DSN connection options
- **MySQL**: Charset now in DSN (e.g., `mysql:...;charset=utf8`)
- **PostgreSQL**: Client encoding in DSN options (e.g., `pgsql:...;options='--client_encoding=UTF8'`)
- **PostgreSQL**: `sslmode` parameter is now optional in DSN

**Migration:**
- No action required - changes are backward compatible
- `setEncoding()` methods still work for runtime changes
- More efficient connection setup with charset in DSN

#### 4. SQL Server Driver Updates ([PR #9](https://github.com/friendsofcake2/cakephp/pull/9))

**Breaking Changes:**

**4.1 Configuration Format**
- **Schema-based configuration**: Use schema mapping instead of multiple databases
  ```php
  // Old approach (still works)
  'database' => 'cakephp_test2'

  // New recommended approach
  'database' => 'cakephp_test',
  'schema' => [
      'default' => 'dbo',
      'test2' => 'test2',
      'test_database_three' => 'test3',
  ]
  ```

- **Connection options**: SSL/TLS options now in `options` array
  ```php
  'options' => [
      'TrustServerCertificate' => 'yes',
      'Encrypt' => 'no',
  ]
  ```

- **Port configuration**: Specify port separately (automatically appended to server)

**4.2 Method Signature Changes**
- `describe($model): array` - Now has explicit return type
- `insertMulti()` - Now returns `bool` instead of `void`

**Migration:**
- Update SQL Server configuration to use schema mapping (optional but recommended)
- Move SSL/TLS options to `options` array if using inline DSN
- If extending Sqlserver class, update method signatures to match

#### 5. Mail Function Updates ([PR #10](https://github.com/friendsofcake2/cakephp/pull/10))

**Breaking Change:**
- `MailTransport::_mail()` method signature changed with strict types
- Old: `protected function _mail($to, $subject, $message, $headers, $params = null)`
- New: `protected function _mail(string $to, string $subject, string $message, array|string $headers = [], string $params = ''): void`

**Migration:**
- No action required unless you've extended `MailTransport` class
- If extending, update method signature to match strict types

#### 6. CSRF Token Security Enhancement ([PR #5](https://github.com/friendsofcake2/cakephp/pull/5))

**Breaking Change:**
- New CSRF tokens use HMAC-SHA1 signatures (prevents CVE-2020-15400)
- Token format changed to base64-encoded (16-byte value + 20-byte HMAC)

**Migration:**
- **No action required** - automatic and backward compatible
- Existing tokens continue to work
- New tokens generated with enhanced security

#### 7. strftime() Replacement

**Breaking Change:**
- `strftime()` deprecated in PHP 8.1, removed in PHP 8.2
- Now uses `IntlDateFormatter` via Symfony's ICU Polyfill
- Fallback to `PHP81_BC\strftime` for compatibility

**Migration:**
- Most date formatting works identically
- Edge cases may produce slightly different output
- Test date formatting in your application

#### 8. Development Tools Updates

**8.1 PHP CodeSniffer ([PR #8](https://github.com/friendsofcake2/cakephp/pull/8))**
- Updated from 1.0.0 to 5.3
- Applied automatic formatting fixes

**Migration:**
- Development-time change only
- Update `phpcs.xml` if you have custom coding standards

**8.2 PHPUnit Compatibility**
- Framework tests migrated to PHPUnit 9.6
- All deprecated PHPUnit features fixed

**Migration:**
- Update your tests if using deprecated PHPUnit features

#### 9. PHP 8 Syntax Modernization ([PR #7](https://github.com/friendsofcake2/cakephp/pull/7))

**Breaking Change:**
- Codebase modernized to PHP 8 syntax

**Changes:**
- `array()` → `[]`
- `get_class()` → `::class`
- `list()` → `[]` for array destructuring
- `dirname(__FILE__)` → `__DIR__`
- Added null coalescing operators
- Native `str_contains()`, `str_starts_with()`, `str_ends_with()`

**Migration:**
- **No action required** - syntax changes only, no functionality changes

## Running Tests

### Using Docker (Recommended)

```bash
# Copy test database configuration
cp lib/Cake/Test/Config/database.php app/Config/database.php

# Start services
docker-compose up -d

# Install dependencies
docker-compose exec web composer install

# Run tests with specific database
DB=mysql docker-compose exec web ./vendors/bin/phpunit
DB=mysql80 docker-compose exec web ./vendors/bin/phpunit
DB=pgsql docker-compose exec web ./vendors/bin/phpunit
DB=sqlite docker-compose exec web ./vendors/bin/phpunit
DB=sqlsrv docker-compose exec web ./vendors/bin/phpunit
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
- Provide minimal code to reproduce the issue (if possible)

## Project Goals

This fork aims to:
- ✅ Maintain PHP 8.x compatibility
- ✅ Fix critical bugs and security issues
- ✅ Keep tests passing on all supported platforms
- ❌ Add new features (focus is on compatibility only)

## License

This project maintains the original MIT License from CakePHP. See [LICENSE](LICENSE) for details.

## Acknowledgments

- Original CakePHP 2.x framework by [cakephp/cakephp](https://github.com/cakephp/cakephp/tree/2.10.24)
- Initial PHP 8 compatibility work by [kamilwylegala/cakephp2-php8](https://github.com/kamilwylegala/cakephp2-php8)
- All contributors who help maintain this fork
