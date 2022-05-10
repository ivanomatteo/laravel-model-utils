<?php

declare(strict_types=1);

namespace IvanoMatteo\ModelUtils;

class TypeMapper
{
    public function getGenericTypes(): array
    {
        return $this->genericTypes;
    }

    public function phpToGeneric($type): string
    {
        return $this->phpToGeneric[trim($type," \t\n\r\0\x0B/")] ?? 'string';
    }

    public function doctrineToGeneric($type): string
    {
        return $this->doctrineToGeneric[$type] ?? 'string';
    }


    protected array $phpToGeneric = [
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

    protected array $doctrineToGeneric = [
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

    protected array $genericTypes = [
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
