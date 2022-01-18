<?php

declare(strict_types=1);

namespace IvanoMatteo\ModelUtils;

use DateTime;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;

/**
 * @property Model $model
 */
class ReflectionModelMetadata extends ReflectionMetadata
{
    public function getAccessorsMetadata($class): array
    {
        $refClass = new ReflectionClass($class);
        $methods = collect($refClass->getMethods());

        $accessors = $this->filterModifier($methods);
        $mutators = $this->filterModifier($methods);

        return $accessors->map(function (array $accessor) use ($mutators) {
            unset($accessor['method']);

            return [
                ...$accessor,
                'has_mutator' => ! empty($mutators[$accessor['name']]),
                'is_accessor' => true,
            ];
        })->toArray();
    }

    protected function filterModifier(Collection $methods, $prefix = 'get'): Collection
    {
        return $methods->filter(
            fn (ReflectionMethod $method) => (Str::startsWith($method->getShortName(), $prefix) &&
                Str::endsWith($method->getShortName(), 'Attribute') &&
                $method !== ($prefix . "Attribute"))
        )->filter(function (ReflectionMethod $method) use ($prefix) {
            if ($prefix === 'set') {
                $params = $method->getParameters();

                return count($params) === 1 && ! $params[0]->isOptional();
            }

            return true;
        })->mapWithKeys(function (ReflectionMethod $method) {
            $name = Str::snake(substr($method->getShortName(), 3, -9));
            if (empty($name)) {
                return [];
            }

            return [
                $name => [
                    'method' => $method,
                    'name' => $name,
                    'type' => $this->getReturnType($method),
                ],
            ];
        });
    }

    /**
     * @throws ReflectionException
     */
    public function getCastAttributesTypes(Model $model): array
    {
        $props = [];
        $casts = $model->getCasts();

        foreach ($casts as $name => $type) {
            switch ($type) {
                case 'boolean':
                case 'bool':
                    $realType = 'boolean';

                    break;
                case 'string':
                    $realType = 'string';

                    break;
                case 'array':
                case 'json':
                    $realType = 'array';

                    break;
                case 'object':
                    $realType = 'object';

                    break;
                case 'int':
                case 'integer':
                case 'timestamp':
                    $realType = 'integer';

                    break;
                case 'real':
                case 'double':
                case 'float':
                    $realType = 'float';

                    break;
                case 'date':
                case 'datetime':
                    $realType = DateTime::class;

                    break;
                case 'collection':
                    $realType = '\Illuminate\Support\Collection';

                    break;
                default:
                    // In case of an optional custom cast parameter , only evaluate
                    // the `$type` until the `:`
                    $type = strtok($type, ':');
                    $realType = class_exists($type) ? ('\\' . $type) : 'mixed';

                    break;
            }

            $realType = $this->checkForCustomLaravelCasts($realType);
            $props[$name] = $this->getTypeInModel($model, $realType);
        }

        return $props;
    }

    protected function getTypeInModel(object $model, ?string $type): ?string
    {
        if ($type === null) {
            return null;
        }

        if (class_exists($type)) {
            $type = $this->getClassNameInDestinationFile($model, $type);
        }

        return $type;
    }

    /**
     * @throws ReflectionException
     */
    protected function checkForCustomLaravelCasts(string $type): ?string
    {
        if (! class_exists($type) || ! interface_exists(CastsAttributes::class)) {
            return $type;
        }

        $reflection = new ReflectionClass($type);

        if (! $reflection->implementsInterface(CastsAttributes::class)) {
            return $type;
        }

        $methodReflection = new ReflectionMethod($type, 'get');

        $reflectionType = $this->getReturnTypeFromReflection($methodReflection);

        if ($reflectionType === null) {
            $reflectionType = $this->getReturnTypeFromDocBlock($methodReflection);
        }

        if ($reflectionType === 'static' || $reflectionType === '$this') {
            $reflectionType = $type;
        }

        return $reflectionType;
    }
}
