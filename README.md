# Fork of CakePHP 2 with support for PHP8

[![GitHub License](https://img.shields.io/github/license/friendsofcake2/cakephp?label=License)](LICENSE)
[![Packagist Version](https://img.shields.io/packagist/v/friendsofcake2/cakephp?label=Packagist)](https://packagist.org/packages/friendsofcake2/cakephp)
[![Packagist Dependency Version](https://img.shields.io/packagist/dependency-v/friendsofcake2/cakephp/php?logo=php&logoColor=%23FFFFFF&label=PHP&labelColor=%23777BB4&color=%23FFFFFF)](https://packagist.org/packages/friendsofcake2/cakephp)
[![Tests](https://img.shields.io/github/actions/workflow/status/friendsofcake2/cakephp/tests.yml?label=Tests)](https://github.com/friendsofcake2/cakephp/actions/workflows/tests.yml)
[![Codecov](https://img.shields.io/codecov/c/gh/friendsofcake2/cakephp?label=Coverage)](https://codecov.io/gh/friendsofcake2/cakephp)

**This repository is fork forked for the purpose of registering on Packagist.**

~~For original README content please check original repository: https://github.com/cakephp/cakephp/tree/2.x~~

Unfortunately branch 2.x in original repository was taken down.

## Why I created this fork? 🤔

CakePHP 2 stopped getting updates in the end of 2019 (AFAIR). Unfortunately in my case it's too expensive to migrate to newer versions of CakePHP. I started migrating to Symfony framework, but I still use ORM from CakePHP (and actually I like it). So in order to keep up with the newest PHP versions I decided to create fork of the framework.

## Why you should NOT use? ⛔

- Intention of this fork is to support PHP 8.*. Fork is not going to receive new features. Instead, fork is going to get minimal set of patches to comply with newer versions of PHP.
- If for example you're still on 5.6 or 7.0, you should **not** use this fork. Original `cakephp/cakephp` works perfectly fine on all PHP 7.* versions. You should migrate to newer versions of PHP and keep using original code. Once your application is battle tested on production I suggest migrating to PHP 8.

## When you could use this fork? ✅

Only prerequisite is to have your application already on PHP 7.4. Upgrade project to PHP 8.0 and replace CakePHP with this fork.

### Migration

Here are steps I took to migrate my project through all versions to PHP 8.1, maybe it can inspire you:

1. Decouple your tests from `CakeTestCase` and other utilities that are coupled to old PHPUnit version.
2. Once decoupled you can upgrade PHPUnit to the newest version accordingly to your PHP version.
3. Start upgrading gradually to newer versions of PHP. CakePHP 2 works perfectly fine on 7.0 - 7.4.
4. Once you're on 7.4 you can switch to 8 and this fork.

## Before using this fork ⚠️

- ~~Tests of CakePHP framework aren't refactored yet to support PHP 8. Main issue is old version of PHPUnit that is tightly coupled to framework's tests. Issue for fixing this situation is here: https://github.com/kamilwylegala/cakephp2-php8/issues/7~~ Framework tests are migrated to PHPUnit 9.*. Github actions are running tests on PHP 8.0, 8.1.
- ~~Due to lack of tests ☝️~~ - **you also need to rely** on tests in your application after integrating with this fork.
- If after integration you spot any issues related to framework please let me know by creating an issue or pull request with fix.

### Breaking changes

- In order to get rid of `strftime()` deprecation notices, it's required to switch to `IntlDateFormatter` class. This class is available in `intl` extension. Fork doesn't require it explicitly but to be able to use its functions Symfony ICU Polyfill is installed. To provide `strftime` behavior compatibility, `PHP81_BC\strftime` is used. `PHP81_BC` doesn't fully cover strftime, your code should work but there is a chance you'll get slightly different results. Discussed (here)[https://github.com/kamilwylegala/cakephp2-php8/pull/64] and (here)[https://github.com/kamilwylegala/cakephp2-php8/issues/65].

## Installation

This repository **is** available in packagist, therefore your project's `composer.json` must be changed to point to custom repository.

Example configuration:
```
{
	"require": {
		"friendsofcake2/cakephp": "^2.10",
	}
}
```

