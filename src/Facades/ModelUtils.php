<?php

namespace IvanoMatteo\ModelUtils\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \IvanoMatteo\ModelUtils\ModelUtils
 */
class ModelUtils extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'model-utils';
    }
}
