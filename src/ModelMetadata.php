<?php

declare(strict_types=1);

namespace IvanoMatteo\ModelUtils;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Traits\ForwardsCalls;
use IvanoMatteo\ModelUtils\Traits\DatabaseMetadata;
use IvanoMatteo\ModelUtils\Traits\ModelMetadataTrait;
use IvanoMatteo\ModelUtils\Traits\ReflectionMetadata;

class ModelMetadata
{
    use ModelMetadataTrait;
    use ForwardsCalls;


    protected Model $model;

    public function __construct($model)
    {
        if (is_string($model)) {
            $model = new $model();
        }

        if (! ($model instanceof Model)) {
            throw new Exception("\$model is not a Illuminate\Database\Eloquent\Model");
        }

        $this->model = $model;
    }

    public function getModel(): Model
    {
        return $this->model;
    }

    public function __call($method, $parameters)
    {
        return $this->forwardCallTo($this->model, $method, $parameters);
    }
}

