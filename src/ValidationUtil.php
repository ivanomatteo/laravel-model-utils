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
class ValidationUtil
{

    public function getValidationRules($alsoNotFillables = false)
    {
        ['accessors' => $accessors, 'columns' => $columns] = $this->getMetadata();

        $tmp = $accessors->merge($columns);

        if ($alsoNotFillables) {
            $tmp = $tmp->where('fillable', '=', true);
        }

        return $tmp->map(function ($item, $key) {
            $rules = [];
            switch ($item['type']) {
                case 'integer':
                    $rules[] = 'integer';

                    break;
                case 'float':
                    $rules[] = 'numeric';

                    break;
                case 'string':
                case 'blob':
                    $rules[] = 'string';
                    $rules[] = "max:" . $item['length'];

                    break;
                case 'date':
                    $rules[] = 'date_format:Y-m-d';

                    break;
                case 'datetime':
                    $rules[] = 'date_format:Y-m-d H:i:s';

                    break;
                case 'time':
                    $rules[] = 'date_format:H:i:s';

                    break;
                case 'json':
                    $rules[] = 'json';

                    break;
                default:

                    break;
            }

            return $rules;
        });
    }

}
