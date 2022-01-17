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


class TypeMapper
{
    public function getGenericTypes(): array
    {
        return $this->genericTypes;
    }

    public function phpToGeneric($type): string
    {
        return $this->phpToGeneric[$type] ?? 'string';
    }

    public function doctrineToGeneric($type): string
    {
        return $this->doctrineToGeneric[$type] ?? 'string';
    }


    protected $phpToGeneric = [
        'mixed' => 'string',
        'string' => 'string',
        'DateTime' => 'datetime',
        'Carbon\\Carbon' => 'datetime',
        'integer' => 'integer',
        'int' => 'integer',
        'float' => 'float',
        'double' => 'float',
        'real' => 'float',
        'bool' => 'boolean',
        'boolean' => 'boolean',
        'object' => 'json',
        'array' => 'json',
        'stdClass' => 'json',
    ];

    protected $doctrineToGeneric = [
        "bigint" => 'integer',
        "integer" => 'integer',
        "smallint" => 'integer',
        "float" => 'float',
        "decimal" => 'float',
        "boolean" => 'boolean',
        "time" => 'time',
        "datetime" => 'datetime',
        "date" => 'date',
        "json" => 'json',
        "text" => 'string',
        "string" => 'string',
        "blob" => 'blob',
    ];

    protected $genericTypes = [
        'integer',
        'float',
        'string',
        'boolean',
        'date',
        'datetime',
        'timestamp',
        'time',
        'year',
        'text',
        'blob',
        'enum',
        'json',
        'relation',
    ];
}
