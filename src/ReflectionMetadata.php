<?php

declare(strict_types=1);

namespace IvanoMatteo\ModelUtils;

use Barryvdh\Reflection\DocBlock;
use Barryvdh\Reflection\DocBlock\Context;
use Illuminate\Database\Eloquent\Model;
use phpDocumentor\Reflection\Types\ContextFactory;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionObject;
use ReflectionType;
use Reflector;

/**
 * @property Model $model
 */
class ReflectionMetadata
{
    public function getReturnType(ReflectionMethod $refMethod): ?string
    {
        $type = $this->getReturnTypeFromDocBlock($refMethod);
        if ($type) {
            return $type;
        }

        return $this->getReturnTypeFromReflection($refMethod);
    }


    public function getReturnTypeFromDocBlock(ReflectionMethod $reflection, Reflector $reflectorForContext = null): null|string
    {
        $phpDocContext = (new ContextFactory())->createFromReflector($reflectorForContext ?? $reflection);
        $context = new Context(
            $phpDocContext->getNamespace(),
            $phpDocContext->getNamespaceAliases()
        );
        $type = null;
        $phpdoc = new DocBlock($reflection, $context);

        if ($phpdoc->hasTag('return')) {
            // @phpstan-ignore-next-line
            $type = $phpdoc->getTagsByName('return')[0]->getType();
        }

        return $type;
    }

    public function getReturnTypeFromReflection(ReflectionMethod $refMethod): ?string
    {
        $returnType = $refMethod->getReturnType();
        if (! $returnType) {
            return null;
        }

        $types = $this->extractReflectionTypes($returnType);

        $type = implode('|', $types);

        if ($returnType->allowsNull()) {
            $type .= '|null';
        }

        return $type;
    }

    protected function extractReflectionTypes(ReflectionType $reflection_type)
    {
        if ($reflection_type instanceof ReflectionNamedType) {
            $types[] = $this->getReflectionNamedType($reflection_type);
        } else {
            $types = [];
            // @phpstan-ignore-next-line
            foreach ($reflection_type->getTypes() as $named_type) {
                if ($named_type->getName() === 'null') {
                    continue;
                }

                $types[] = $this->getReflectionNamedType($named_type);
            }
        }

        return $types;
    }

    protected function getReflectionNamedType(ReflectionNamedType $paramType): string
    {
        $parameterName = $paramType->getName();
        if (! $paramType->isBuiltin()) {
            $parameterName = '\\' . $parameterName;
        }

        return $parameterName;
    }

    protected function getClassNameInDestinationFile(object $model, string $className): string
    {
        $reflection = $model instanceof ReflectionClass
            ? $model
            : new ReflectionObject($model);

        $className = trim($className, '\\');

        $usedClassNames = $this->getUsedClassNames($reflection);

        return $usedClassNames[$className] ?? ('\\' . $className);
    }

    public function getUsedClassNames(ReflectionClass $reflection): array
    {
        $namespaceAliases = array_flip((new ContextFactory())->createFromReflector($reflection)->getNamespaceAliases());
        $namespaceAliases[$reflection->getName()] = $reflection->getShortName();

        return $namespaceAliases;
    }
}
