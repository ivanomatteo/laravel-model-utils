<?php

declare(strict_types=1);

namespace IvanoMatteo\ModelUtils;

use Barryvdh\Reflection\DocBlock;
use Barryvdh\Reflection\DocBlock\Context;
use Barryvdh\Reflection\DocBlock\Tag;
use DateTime;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use phpDocumentor\Reflection\Types\ContextFactory;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionObject;
use ReflectionType;
use SplFileInfo;

/**
 * @property Model $model
 */
class PathUtil
{

    /**
     * @return string[]
     */
    public static function findModels($basePath = null, $baseNamespace = "App")
    {
        if (!isset($basePath)) {
            $basePath = app_path('');
        }
        $baseNamespace = preg_replace("/^\\\\/", '', $baseNamespace);
        $baseNamespace = preg_replace("/\\\\$/", '', $baseNamespace);

        $out = [];

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $basePath
            ),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            /**
             * @var SplFileInfo $item
             */
            if ($item->isReadable() && $item->isFile() && mb_strtolower($item->getExtension()) === 'php') {
                $relativeFQCN = str_replace(
                    "/",
                    "\\",
                    mb_substr($item->getRealPath(), mb_strlen($basePath), -4)
                );

                $fqcn = $baseNamespace . "$relativeFQCN";

                if (Str::startsWith($fqcn, "\\")) {
                    $fqcn = substr($fqcn, 1, strlen($fqcn) - 1);
                }

                if (!class_exists($fqcn, false)) {
                    include_once $item->getRealPath();
                }

                if (class_exists($fqcn, false)) {
                    if (is_subclass_of($fqcn, Model::class)) {
                        $out[] = $fqcn;
                    }
                }
            }
        }

        return $out;
    }
}
