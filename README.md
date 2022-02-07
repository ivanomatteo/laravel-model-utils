# Extract attributes metadata from model

[![Latest Version on Packagist](https://img.shields.io/packagist/v/ivanomatteo/laravel-model-utils.svg?style=flat-square)](https://packagist.org/packages/ivanomatteo/laravel-model-utils)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/ivanomatteo/laravel-model-utils/run-tests?label=tests)](https://github.com/ivanomatteo/laravel-model-utils/actions?query=workflow%3Arun-tests+branch%3Amaster)
[![GitHub Code Style Action Status](https://img.shields.io/github/workflow/status/ivanomatteo/laravel-model-utils/Check%20&%20fix%20styling?label=code%20style)](https://github.com/ivanomatteo/laravel-model-utils/actions?query=workflow%3A"Check+%26+fix+styling"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/ivanomatteo/laravel-model-utils.svg?style=flat-square)](https://packagist.org/packages/ivanomatteo/laravel-model-utils)

This package provide a simple way to retrive usefull metadata from laravel models

-   find all models inside a psr-4 directory structure
-   retrieve all columns and metadata from database
-   retrieve indexes metadata
-   generate basic validation rules using metadata
-   model type hinting removed, you can use https://github.com/barryvdh/laravel-ide-helper


## Installation

You can install the package via composer:

```bash
composer require ivanomatteo/laravel-model-utils
```

## Usage


```php
use IvanoMatteo\ModelUtils\ModelUtils;

dump(ModelUtils::findModels());

$mu = new ModelUtils(\App\User::class);

dump('id visible:',$mu->isVisible('id'));
dump('password visible:',$mu->isVisible('password'));

dump($mu->getValidationRules());
dump($mu->getValidationRules(true)); //also for not fillable fields

dump($mu->getMetadata());


```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Ivano Matteo](https://github.com/ivanomatteo)
- Thanks also to [Barry vd. Heuvel](https://github.com/barryvdh) for his libraries, i took some pices of code from ide helper
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
