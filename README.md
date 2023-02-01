# PestPHP GraphQl Coverage

[![Latest Version on Packagist](https://img.shields.io/packagist/v/worksome/pest-graphql-coverage.svg?style=flat-square)](https://packagist.org/packages/worksome/pest-graphql-coverage)
[![Total Downloads](https://img.shields.io/packagist/dt/worksome/pest-graphql-coverage.svg?style=flat-square)](https://packagist.org/packages/worksome/pest-graphql-coverage)

This plugin adds supports for showing the coverage of the GraphQL schema (Lighthouse only).

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
php artisan test -p --gql-coverage
```

### Setting coverage limits
By adding the argument `--gql-min=<percentage>`, we can limit to have a min coverage of x.

```bash
php artisan test --gql-coverage --gql-min=60
```

### Changing default schema fetching command
By default it will fetch the schema using `php artisan lighthouse:print-schema`, however if you have a
custom command for fetching the schema, that can be used instead by adding `--schema-command` argument


```bash
php artisan test --gql-coverage --schema-command="php artisan lighthouse:print-schema-v2"
```

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
