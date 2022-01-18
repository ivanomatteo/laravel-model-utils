<?php

declare(strict_types=1);

namespace IvanoMatteo\ModelUtils;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Traits\ForwardsCalls;
use IvanoMatteo\ModelUtils\Traits\AttributesMetadata;
use IvanoMatteo\ModelUtils\Traits\BasicValidation;
use IvanoMatteo\ModelUtils\Traits\DatabaseMetadata;
use IvanoMatteo\ModelUtils\Traits\ReflectionMetadata;
use IvanoMatteo\ModelUtils\Traits\VisibilityCheck;

class ModelMetadata
{
    use BasicValidation;
    use ForwardsCalls;


    protected Model $model;

    /**
     * @throws Exception
     */
    public function __construct($model)
    {
        if (is_string($model)) {
            $model = new $model();
        }
        if (!($model instanceof Model)) {
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
