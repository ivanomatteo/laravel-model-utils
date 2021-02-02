# Laravel model utils

[![Latest Version on Packagist](https://img.shields.io/packagist/v/ivanomatteo/model-utils.svg?style=flat-square)](https://packagist.org/packages/ivanomatteo/model-utils)

[![Total Downloads](https://img.shields.io/packagist/dt/ivanomatteo/model-utils.svg?style=flat-square)](https://packagist.org/packages/ivanomatteo/model-utils)

This package provide a simple way to retrive usefull metadata from laravel models

-   find all models inside a psr-4 directory structure
-   retrieve all columns and metadata from database
-   retrieve indexes metadata
-   generate basic validation rules using metadata
-   model type hinting removed, you can use https://github.com/barryvdh/laravel-ide-helper

## Installation

If you are using laravel version < 8 then install "doctrine/dbal:^2.6" first

```bash
composer require doctrine/dbal:^2.6
```

You can install the package via composer:

```bash
composer require ivanomatteo/model-utils
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

### Testing

```bash
composer test
```

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email ivanomatteo@gmail.com instead of using the issue tracker.

## Credits

-   [Ivano Matteo](https://github.com/ivanomatteo)
-   Thanks also to [Barry vd. Heuvel](https://github.com/barryvdh) for his libraries, I took some pices of code from ide helper
-   [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Laravel Package Boilerplate

This package was generated using the [Laravel Package Boilerplate](https://laravelpackageboilerplate.com).
