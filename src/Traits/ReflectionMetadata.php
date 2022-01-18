<?php

declare(strict_types=1);

namespace IvanoMatteo\ModelUtils\Traits;

use IvanoMatteo\ModelUtils\ModelMetadata;
use IvanoMatteo\ModelUtils\ReflectionModelMetadata;

trait ReflectionMetadata
{
    protected ?ReflectionModelMetadata $reflectionModelMetadata;

    protected function getReflectionModelMetadata()
    {
        if (! isset($this->reflectionModelMetadata)) {
            $this->reflectionModelMetadata = new ReflectionModelMetadata();
        }

        return $this->reflectionModelMetadata;
    }

    public function getCastPropertiesTypes(): array
    {
        return $this->getReflectionModelMetadata()
            ->getCastPropertiesTypes(
                ($this instanceof ModelMetadata) ? $this->model : $this
            );
    }

    public function getPropertiesFromAccessors($all = false): array
    {
        return collect($this->getReflectionModelMetadata()->getPropertiesFromAccessors(
            ($this instanceof ModelMetadata) ? $this->model : $this
        ))->filter(fn ($p) => ($all || $this->isVisible($p['name'])))->toArray();
    }
}
