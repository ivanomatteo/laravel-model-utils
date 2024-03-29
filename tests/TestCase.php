<?php

declare(strict_types=1);

namespace IvanoMatteo\ModelUtils\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use IvanoMatteo\ModelUtils\ModelUtilsServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'IvanoMatteo\\ModelUtils\\Database\\Factories\\' . class_basename($modelName) . 'Factory'
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            ModelUtilsServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');

        $migration = include __DIR__.'/../database/migrations/test_create_foo_classes.php.stub';
        $migration->up();
    }
}
