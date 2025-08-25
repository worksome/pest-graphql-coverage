# PestPHP GraphQL Coverage

[![Latest Version on Packagist](https://img.shields.io/packagist/v/worksome/pest-graphql-coverage.svg?style=flat-square)](https://packagist.org/packages/worksome/pest-graphql-coverage)
[![Total Downloads](https://img.shields.io/packagist/dt/worksome/pest-graphql-coverage.svg?style=flat-square)](https://packagist.org/packages/worksome/pest-graphql-coverage)

This plugin adds support for showing the coverage of the GraphQL schema (Lighthouse only).

**Supports Pest 3 and Pest 4** - automatically detects and works with both versions.

## Installation

You can install the package via composer:

```bash
composer require --dev worksome/pest-graphql-coverage
```

## Usage

To enable it simply add `--gql-coverage` argument to your test command

```bash
php artisan test --gql-coverage
```

It can even be used together with parallel

```bash
php artisan test --gql-coverage -p
```

### Setting coverage limits

By adding the argument `--gql-min=<percentage>`, we can limit to have a min coverage of x.

```bash
php artisan test --gql-coverage --gql-min=60
```

### Setting the number of output fields

By adding the argument  `--gql-untested-count=<max>`, we can increase or reduce the number of untested fields
that are output.

```shell
php artisan test --gql-coverage --gql-untested-count=25
```

### Changing default schema fetching command

By default, it will fetch the schema using `php artisan lighthouse:print-schema`, however if you have a
custom command for fetching the schema, that can be used instead by adding `--schema-command` argument

```bash
php artisan test --gql-coverage --schema-command="php artisan lighthouse:print-schema-v2"
```

### Excluding nodes from total coverage

By default, all nodes will be included when calculating coverage. However, if you have nodes such as the built-in
Lighthouse pagination types that you do not want to be covered, you can configure ignored fields from your `Pest.php` configuration file.

```php
<?php

declare(strict_types=1);

use Worksome\PestGraphqlCoverage\Config as GraphQLCoverageConfig;

GraphQLCoverageConfig::new()
    ->ignore([
        'PaginatorInfo.count',
        // ...
    ]);

// Exclude all paginator info nodes
GraphQLCoverageConfig::new()
    ->ignorePaginatorInfo();

// Exclude all deprecated fields
GraphQLCoverageConfig::new()
    ->ignoreDeprecatedFields();
```

### Native Pest usage

This also works natively with Pest (without using Artisan), as it is a Pest plugin.

```shell
vendor/bin/pest --gql-coverage
```

## Version Compatibility

This package supports both Pest 3 and Pest 4:

- **Pest 3**: Requires PHP 8.2+
- **Pest 4**: Requires PHP 8.3+

The package will automatically work with whichever version of Pest you have installed. No code changes are required when upgrading from Pest 3 to Pest 4.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
