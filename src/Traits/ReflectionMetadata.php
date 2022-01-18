<?php

declare(strict_types=1);

namespace IvanoMatteo\ModelUtils\Traits;

use IvanoMatteo\ModelUtils\ModelMetadata;
use IvanoMatteo\ModelUtils\ReflectionModelMetadata;

trait ReflectionMetadata
{
    protected ?ReflectionModelMetadata $reflectionModelMetadata;

    protected function getReflectionModelMetadata(): ?ReflectionModelMetadata
    {
        if (! isset($this->reflectionModelMetadata)) {
            $this->reflectionModelMetadata = new ReflectionModelMetadata();
        }

        return $this->reflectionModelMetadata;
    }

    public function getCastAttributesTypes(): array
    {
        return $this->getReflectionModelMetadata()
            ->getCastAttributesTypes(
                ($this instanceof ModelMetadata) ? $this->model : $this
            );
    }

    public function getAccessorsMetadata($all = false): array
    {
        return collect($this->getReflectionModelMetadata()->getAccessorsMetadata(
            ($this instanceof ModelMetadata) ? $this->model : $this
        ))->filter(fn ($p) => ($all || $this->isVisible($p['name'])))->toArray();
    }
}
