# Laravel model utils

[![Latest Version on Packagist](https://img.shields.io/packagist/v/ivanomatteo/model-utils.svg?style=flat-square)](https://packagist.org/packages/ivanomatteo/model-utils)

[![Total Downloads](https://img.shields.io/packagist/dt/ivanomatteo/model-utils.svg?style=flat-square)](https://packagist.org/packages/ivanomatteo/model-utils)

- Generate Type Hinting for Models 
- Provide a simple way to retrive usefull metadata from laravel models


## Installation

You can install the package via composer:

```bash
composer require ivanomatteo/model-utils
```

## Usage

Generate Type Hinting for Models:

```bash
php artisan hint:models
```
Result:
``` php
/**

 * @property int $id
 * @property string $name
 * @property string $email
 * @property mixed $email_verified_at
 * @property string $password
 * @property string $remember_token
 * @property mixed $created_at
 * @property mixed $updated_at
*/
class User extends Authenticatable
{
 // .......
}
```

Extract metadata from Models:

``` php
use IvanoMatteo\ModelUtils\ModelUtils;

$mu = new ModelUtils(\App\User::class);

echo $mu->isVisible('id')?'y':'n'; // out: y
echo $mu->isVisible('password')?'y':'n'; // out: n

// retrive db table metadata
// for mysql is avaible a specific driver that retrive more data
// in other cases will be used DB::getSchemaBuilder()
echo json_encode($mu->getDBMetadata(),JSON_PRETTY_PRINT);
/**
 {
    "id": {
        "dbtype_full": "bigint unsigned",
        "nullable": false,
        "key": "PRI",
        "default": null,
        "dbtype": "bigint",
        "dbsize": null,
        "type": "integer",
        "extra": "auto_increment"
    },
    .......
}
 */


// return a ReflectionClass array of found models
$modelsRefClasses = ModelUtils::findModels(/* $path = null, $baseNamespace = "App" */);

```



### Testing

``` bash
composer test
```

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email ivanomatteo@gmail.com instead of using the issue tracker.

## Credits

- [Ivano Matteo](https://github.com/ivanomatteo)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Laravel Package Boilerplate

This package was generated using the [Laravel Package Boilerplate](https://laravelpackageboilerplate.com).