<?php

namespace IvanoMatteo\ModelUtils\Tests;

use Orchestra\Testbench\TestCase;
use IvanoMatteo\ModelUtils\ModelUtilsServiceProvider;

class ExampleTest extends TestCase
{

    protected function getPackageProviders($app)
    {
        return [ModelUtilsServiceProvider::class];
    }
    
    /** @test */
    public function true_is_true()
    {
        $this->assertTrue(true);
    }
}
