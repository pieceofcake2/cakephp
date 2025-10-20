# CakePHP 2.x - Community Maintained Fork

[![GitHub License](https://img.shields.io/github/license/pieceofcake2/cakephp?label=License)](LICENSE)
[![Packagist Version](https://img.shields.io/packagist/v/pieceofcake2/cakephp?label=Packagist)](https://packagist.org/packages/pieceofcake2/cakephp)
[![PHP](https://img.shields.io/packagist/dependency-v/pieceofcake2/cakephp/php?logo=php&logoColor=%23FFFFFF&label=PHP&labelColor=%23777BB4&color=%23FFFFFF)](https://packagist.org/packages/pieceofcake2/cakephp)
[![CI](https://img.shields.io/github/actions/workflow/status/pieceofcake2/cakephp/CI.yml?label=CI)](https://github.com/pieceofcake2/cakephp/actions/workflows/CI.yml)
[![Codecov](https://img.shields.io/codecov/c/gh/pieceofcake2/cakephp?label=Coverage)](https://codecov.io/gh/pieceofcake2/cakephp)

This is a community-maintained fork of CakePHP 2.x that provides compatibility with PHP 8.0 and newer versions.
The original CakePHP 2.x branch [reached End of Life in June 2021](https://bakery.cakephp.org/2021/10/02/cakephp_2_eol.html).

> [!IMPORTANT]
> This fork is based on CakePHP 2.10.24. Earlier versions are not supported.

> [!WARNING]
> **Do not use CakePHP 2.x for new projects!** This fork is only for maintaining existing legacy applications.
> For new projects, please use [CakePHP 5.x](https://cakephp.org/) which has modern PHP support, better performance, and active development.

[CakePHP 2.x Documentation](https://book.cakephp.org/2/en/) | [CHANGELOG](CHANGELOG.md) | [UPGRADE](UPGRADE.md)

## Requirements & Compatibility

### PHP Versions

- PHP 8.0, 8.1, 8.2, 8.3, 8.4, 8.5

### Database Support

- MySQL 5.6, 5.7, 8.0+ (with `pdo_mysql` extension)
- PostgreSQL 9.4+ (with `pdo_pgsql` extension)
- SQLite 3 (with `pdo_sqlite` extension)
- Microsoft SQL Server 2022+ (with `pdo_sqlsrv` extension)

### Required PHP Extensions

- `mbstring` - Multi-byte string support (**strongly recommended**, uses Symfony polyfill as fallback)
  - **Important**: The `mb_encode_mimeheader()` function is **not available** in the Symfony polyfill
  - If `mbstring` extension is not loaded, CakePHP will automatically use `Multibyte::mimeEncode()` as a fallback for email header encoding
  - However, **we strongly recommend installing the `mbstring` extension** for better compatibility and performance
- `intl` - Internationalization support (optional, uses Symfony polyfill as fallback)
- `openssl` - OpenSSL support (optional, required for SSL/TLS connections and encryption)
- `mcrypt` - Mcrypt support (optional, deprecated in PHP 7.1+, only for legacy AES encryption)

### Testing

- All tests pass with PHPUnit 9.6 across all supported PHP versions and databases

## Installation

> [!IMPORTANT]
> This fork requires Composer for installation. Manual installation is not supported.

Install via Composer:

```json
{
    "require": {
        "pieceofcake2/cakephp": "^2.10"
    }
}
```

Then run:
```bash
composer update
```

After installation, copy dispatcher files from the package to your application:

```bash
# Copy web dispatcher files
cp plugins/Bake/Console/Templates/skel/webroot/index.php app/webroot/index.php
cp plugins/Bake/Console/Templates/skel/webroot/test.php app/webroot/test.php

# Copy console dispatcher
cp plugins/Bake/Console/Templates/skel/Console/cake app/Console/cake
chmod +x app/Console/cake
```

> [!NOTE]
> - This package uses Composer's `replace` directive to replace `cakephp/cakephp`.
> - This ensures that all plugins and packages that depend on `cakephp/cakephp:^2.x` will continue to work correctly with this fork.
> - Dispatcher files provide better error messages and simplified autoload handling.

### Application Skeleton

The application skeleton has been extracted to a separate package: [`pieceofcake2/app`](https://github.com/pieceofcake2/app)

#### Planning to migrate to CakePHP 5.x?

If you're planning to upgrade to CakePHP 5.x in the future, you can **prepare now** by adopting the modern directory structure while still on CakePHP 2.x:

**Traditional migration approach (harder):**
```
CakePHP 2.x → CakePHP 5.x
(change everything at once: code + folder structure + APIs)
```

**New gradual migration approach (easier):**
```
Step 1: CakePHP 2.x with traditional structure (non-namespaced)
        ↓ (modernize folder structure only)
Step 2: CakePHP 2.x with CakePHP 5.x-style structure (non-namespaced) ← You can stop here
        ↓ (adopt namespaces only)
Step 3: CakePHP 2.x with CakePHP 5.x-style structure (namespaced) ← Or here
        ↓ (upgrade framework only)
Step 4: CakePHP 5.x with CakePHP 5.x-style structure (namespaced)
```

**Benefits:**
- ✅ **Smaller, manageable changes**: Separate folder restructuring, namespace adoption, and framework upgrade
- ✅ **Test incrementally**: Verify each step works before moving to the next
- ✅ **Reduced risk**: You can stop at Step 2 (modern structure) or Step 3 (with namespaces) indefinitely
- ✅ **Team-friendly**: Easier for teams to understand and review smaller changes
- ✅ **Namespace preparation**: Adopt CakePHP 5.x-compatible namespaces while still on 2.x

See [`pieceofcake2/app`](https://github.com/pieceofcake2/app) for the modern directory structure compatible with both CakePHP 2.x and 5.x.

## Security

### SSL/TLS Certificate Validation

This fork uses [`composer/ca-bundle`](https://github.com/composer/ca-bundle) for SSL/TLS certificate validation ([PR #15](https://github.com/pieceofcake2/cakephp/pull/15)):

- **System CA certificates**: Uses OpenSSL's default certificate bundle when available (`openssl.cafile` or `openssl.capath`)
- **Fallback bundle**: Falls back to Mozilla's CA certificate bundle maintained by composer/ca-bundle
- **Automatic updates**: CA certificates are kept up-to-date through Composer ecosystem
- **No manual maintenance**: Removed the outdated static `lib/Cake/Config/cacert.pem` file (last updated in 2016)

This approach ensures that HTTPS connections made by `CakeSocket` (e.g., for external API calls) properly validate SSL/TLS certificates using current, trusted root certificates.

### XML External Entity (XXE) Protection

This fork has **removed** the `loadEntities` option from `Xml::build()` for enhanced security:

- **External entity loading is now permanently disabled** to prevent XXE (XML External Entity) attacks
- Uses `libxml_set_external_entity_loader(null)` on PHP 8.0+ (deprecated `libxml_disable_entity_loader()` removed)
- No configuration option to re-enable external entities - this is a security hardening measure

**Breaking Change**: If your application previously used `Xml::build($input, ['loadEntities' => true])`, this option is now ignored and external entities will not be loaded. This is intentional for security reasons.

### Known Vulnerabilities in Original CakePHP 2.10.24

The following security vulnerabilities have been reported in the original CakePHP 2.10.24:

| CVE | Description | Status in this Fork |
|-----|-------------|-------------------|
| [CVE-2015-8379](https://nvd.nist.gov/vuln/detail/CVE-2015-8379) | CSRF protection bypass via _method parameter | ✅ Fixed in [c0fb45e](https://github.com/pieceofcake2/cakephp/commit/c0fb45e79), tests in [PR #6](https://github.com/pieceofcake2/cakephp/pull/6) |
| [CVE-2020-15400](https://nvd.nist.gov/vuln/detail/CVE-2020-15400) | CSRF token fixation (exploitable with XSS) | ✅ Fixed in [PR #5](https://github.com/pieceofcake2/cakephp/pull/5) |

> [!NOTE]
> - **CVE-2015-8379**: The fix has been fully applied with comprehensive test coverage for `_method` parameter handling and custom HTTP methods.
> - **CVE-2020-15400**: Fixed by implementing HMAC-signed CSRF tokens that are cryptographically bound to the application. Tokens are now signed with the application's Security.salt, preventing token fixation attacks while maintaining backward compatibility with existing tokens.

## Migration Guide

For detailed information about prerequisites, migration steps, and breaking changes, see [UPGRADE.md](UPGRADE.md).

## Running Tests

### Using Docker (Recommended)

```bash
# Copy test database configuration
cp tests/Config/database.php app/Config/database.php

# Start services
docker-compose up -d

# Install dependencies
docker-compose exec web composer install

# Run tests with specific database
DB=mysql docker-compose exec web ./vendor/bin/phpunit
DB=mysql80 docker-compose exec web ./vendor/bin/phpunit
DB=pgsql docker-compose exec web ./vendor/bin/phpunit
DB=sqlite docker-compose exec web ./vendor/bin/phpunit
DB=sqlsrv docker-compose exec web ./vendor/bin/phpunit
```

### Local Installation

```bash
# Install dependencies
composer install

# Set up database configuration
cp app/Config/database.php.default app/Config/database.php
# Edit database.php with your database credentials

# Run tests
./vendor/bin/phpunit
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
- ✅ Support gradual migration path to CakePHP 5.x
- ❌ Add new features (focus is on compatibility and migration only)

## License

This project maintains the original MIT License from CakePHP. See [LICENSE](LICENSE) for details.

## Acknowledgments

- Original CakePHP 2.x framework by [cakephp/cakephp](https://github.com/cakephp/cakephp/tree/2.10.24)
- Initial PHP 8 compatibility work by [kamilwylegala/cakephp2-php8](https://github.com/kamilwylegala/cakephp2-php8)
- All contributors who help maintain this fork
