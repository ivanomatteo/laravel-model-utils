<?php

namespace IvanoMatteo\ModelUtils;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use IvanoMatteo\ModelUtils\Commands\ModelUtilsCommand;

class ModelUtilsServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('model-utils')
            ->hasConfigFile();
    }
}
