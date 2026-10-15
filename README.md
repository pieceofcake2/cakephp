# CakePHP 2.x - Community Maintained Fork

[![GitHub License](https://img.shields.io/github/license/pieceofcake2/cakephp?label=License)](LICENSE)
[![Packagist Version](https://img.shields.io/packagist/v/pieceofcake2/cakephp?label=Packagist)](https://packagist.org/packages/pieceofcake2/cakephp)
[![PHP](https://img.shields.io/packagist/dependency-v/pieceofcake2/cakephp/php?logo=php&logoColor=%23FFFFFF&label=PHP&labelColor=%23777BB4&color=%23FFFFFF)](https://packagist.org/packages/pieceofcake2/cakephp)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%205-brightgreen?label=PHPStan)](https://github.com/pieceofcake2/cakephp/actions/workflows/CI.yml)
[![CI](https://img.shields.io/github/actions/workflow/status/pieceofcake2/cakephp/CI.yml?label=CI)](https://github.com/pieceofcake2/cakephp/actions/workflows/CI.yml)
[![Codecov](https://img.shields.io/codecov/c/gh/pieceofcake2/cakephp?label=Coverage)](https://codecov.io/gh/pieceofcake2/cakephp)

This is a community-maintained fork of CakePHP 2.x that provides compatibility with PHP 8.0 and newer versions.
The original CakePHP 2.x branch [reached End of Life in June 2021](https://bakery.cakephp.org/2021/10/02/cakephp_2_eol.html).
The fork keeps CakePHP 2 working on current PHP versions and fixes bugs and security issues; it does not add new features.

> [!IMPORTANT]
> This fork is based on CakePHP 2.10.24. Earlier versions are not supported.

> [!WARNING]
> **Do not use CakePHP 2.x for new projects!** This fork is only for maintaining existing legacy applications.
> For new projects, please use [CakePHP 5.x](https://cakephp.org/) which has modern PHP support, better performance, and active development.

[Website](https://pieceofcake2.github.io/) | [CHANGELOG](CHANGELOG.md) | [UPGRADE](UPGRADE.md)

## Documentation

- Website: https://pieceofcake2.github.io/docs/
- Markdown: [docs/en/index.md in pieceofcake2/docs](https://github.com/pieceofcake2/docs/blob/2.x/docs/en/index.md) (for AI tools and offline reading)

For AI coding tools, install the Markdown docs of the CakePHP 2 book as a dev dependency: `composer require --dev pieceofcake2/docs` (files in `vendor/pieceofcake2/docs/docs/en/`).

Start with [Getting Started](https://pieceofcake2.github.io/docs/getting-started.html)
([Markdown](https://github.com/pieceofcake2/docs/blob/2.x/docs/en/getting-started.md)), which summarizes what
is different from CakePHP 2.10.

## Requirements

- PHP 8.0 to 8.5
- MySQL 5.6+, PostgreSQL 9.4+, SQLite 3 or Microsoft SQL Server 2022+ (PDO)
- The `intl` extension; `mbstring` is strongly recommended

See [Requirements](https://pieceofcake2.github.io/docs/installation.html#requirements)
([Markdown](https://github.com/pieceofcake2/docs/blob/2.x/docs/en/installation.md#requirements)) in the book for the details.

## Installation

This fork is installed with Composer only. It declares `replace` for `cakephp/cakephp`, so plugins that
require `cakephp/cakephp` 2.x keep working.

```bash
# New application, from the application skeleton
composer create-project pieceofcake2/app my_app

# Existing application
composer remove --no-update cakephp/cakephp
composer require pieceofcake2/cakephp
```

An existing application also needs updated dispatcher files and `composer.json` settings. See
[Installation](https://pieceofcake2.github.io/docs/installation.html)
([Markdown](https://github.com/pieceofcake2/docs/blob/2.x/docs/en/installation.md)) in the book.

## Upgrading

See [Migrating from CakePHP 2.10](https://pieceofcake2.github.io/docs/appendices/migrating-from-cakephp-2-10.html)
([Markdown](https://github.com/pieceofcake2/docs/blob/2.x/docs/en/appendices/migrating-from-cakephp-2-10.md))
in the book for the migration steps and breaking changes, and [UPGRADE.md](UPGRADE.md) for the changes of each release.

To prepare for CakePHP 5, you can move to the CakePHP 5-style directory layout while you stay on CakePHP 2. See
[Migrating to the Modern Layout](https://pieceofcake2.github.io/docs/appendices/migrating-to-the-modern-layout.html)
([Markdown](https://github.com/pieceofcake2/docs/blob/2.x/docs/en/appendices/migrating-to-the-modern-layout.md))
and the [pieceofcake2/app](https://pieceofcake2.github.io/app/) skeleton.

## Integration Testing

`IntegrationTestTrait` and `EmailTrait` are backported from CakePHP 5. See
[Integration Testing](https://pieceofcake2.github.io/docs/development/integration-testing.html)
([Markdown](https://github.com/pieceofcake2/docs/blob/2.x/docs/en/development/integration-testing.md)) in the book.

## Security

Do not report vulnerabilities in public issues; follow the
[security policy](https://github.com/pieceofcake2/.github/blob/master/SECURITY.md).

The vulnerabilities fixed in this fork, including the known vulnerabilities of the original CakePHP 2.10.24,
and other security changes such as SSL/TLS certificate validation with `composer/ca-bundle` are listed in
[Security Fixes](https://pieceofcake2.github.io/docs/security/fixes.html)
([Markdown](https://github.com/pieceofcake2/docs/blob/2.x/docs/en/security/fixes.md)). See
[Security](https://pieceofcake2.github.io/docs/security/)
([Markdown](https://github.com/pieceofcake2/docs/blob/2.x/docs/en/security/index.md)) in the book for the
supported versions and how fixes are announced.

## Contributing

See the [contribution guidelines](https://github.com/pieceofcake2/.github/blob/master/CONTRIBUTING.md), including
[running the tests](https://github.com/pieceofcake2/.github/blob/master/CONTRIBUTING.md#running-the-tests) and
[reporting issues](https://github.com/pieceofcake2/.github/blob/master/CONTRIBUTING.md#reporting-issues).

## License

This project maintains the original MIT License from CakePHP. See [LICENSE](LICENSE) for details.

## Acknowledgments

- Original CakePHP 2.x framework by [cakephp/cakephp](https://github.com/cakephp/cakephp/tree/2.10.24)
- Initial PHP 8 compatibility work by [kamilwylegala/cakephp2-php8](https://github.com/kamilwylegala/cakephp2-php8)
- All contributors who help maintain this fork
