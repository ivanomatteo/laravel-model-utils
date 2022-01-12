<?php

namespace IvanoMatteo\ModelUtils;

use Illuminate\Database\Eloquent\Model;
use ReflectionClass;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Schema\Column;
use Illuminate\Support\Str;
use ReflectionNamedType;
use phpDocumentor\Reflection\Types\ContextFactory;
use Barryvdh\Reflection\DocBlock\Context;
use Barryvdh\Reflection\DocBlock;
use DateTime;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Table;
use ReflectionObject;
use ReflectionType;

/**
 * @property Model $model
 */
class ModelUtils
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

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $basePath
            ),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            /**
             * @var \SplFileInfo $item
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


    protected $model;
    protected $reflectionClass;

    protected $visibleMap;
    protected $hiddenMap;

    protected $metadata;


    public function __construct($classOrObj)
    {
        if (is_string($classOrObj)) {
            $this->model = resolve($classOrObj); // laravel resolve() helper
        } else if (is_a($classOrObj, Model::class)) {
            $this->model = $classOrObj;
        } else if ($classOrObj instanceof ReflectionClass) {
            $this->model = resolve($classOrObj->getName());
        }

        if ($classOrObj instanceof ReflectionClass) {
            $this->reflectionClass = $classOrObj;
        } else {
            $this->reflectionClass = new ReflectionClass($this->model);
        }

        if (!is_a($this->model, Model::class)) {
            throw new \Exception("$classOrObj is not a Model");
        }
    }


    public function getReflectionClass()
    {
        return $this->reflectionClass;
    }
    public function getTypesGeneric()
    {
        return $this->types;
    }


    public function isVisible($f)
    {
        if (!isset($this->hiddenMap)) {
            $this->hiddenMap = empty($this->model->getHidden()) ? false : array_fill_keys($this->model->getHidden(), true);
            $this->visibleMap = empty($this->model->getVisible()) ? false : array_fill_keys($this->model->getVisible(), true);
        }

        $is_visible = true;

        if ($this->visibleMap && empty($this->visibleMap[$f])) {
            $is_visible = false;
        }
        if ($this->hiddenMap && !empty($this->hiddenMap[$f])) {
            $is_visible = false;
        }

        return $is_visible;
    }



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


    public function getMetadata($reload = false)
    {
        if ($reload || !isset($this->metadata)) {

            $accessors = $this->getPropertiesFromMethods();
            $db_meta = $this->getDatabaseMetadata();
            $columns = $db_meta['columns'];
            $indexes = $db_meta['indexes'];

            $this->metadata = compact('accessors', 'columns', 'indexes');
        }

        return $this->metadata;
    }

    public function getDatabaseMetadata()
    {
        $conn = $this->model->getConnection();
        $prefix = $conn->getTablePrefix();

        $table = $prefix . $this->model->getTable();
        $database = null;
        if (strpos($table, '.')) {
            [$database, $table] = explode('.', $table);
        }

        $defDatabaseName = $conn->getDatabaseName();

        if (!empty($database)) {
            $conn->setDatabaseName($database);
        } else {
            $database = $defDatabaseName;
        }


        $schema = $conn->getDoctrineSchemaManager();
        $databasePlatform = $schema->getDatabasePlatform();
        $databasePlatform->registerDoctrineTypeMapping('enum', 'string');


        /** @var Table */
        $tableObj = $schema->listTableDetails($table);

        $indexes = $this->mapIndexes($tableObj->getIndexes());
        $indexedCols = $this->getIndexedCols($indexes);

        $castTypes = [];
        if (method_exists($this->model, 'getCasts')) {
            $castTypes = $this->getCastPropertiesTypes();
        }

        $columns = collect($schema->listTableColumns(
            $table,
            $database
        ))->mapWithKeys(function (Column $col, $name) use ($indexedCols, $castTypes) {
            /** @var Type */
            $type = $col->getType();
            $castType = $castTypes[$name] ?? null;
            return [$name => [
                'name' => $name,
                'type' => isset($castType) ? $this->phpTypeToGeneric($castType) : $this->doctrineTypeMap[$type->getName()],
                'srvtype' => $castType ?? $this->dbTypeToPhp($type->getName()),
                'dbtype' => $type->getName(),
                'length' => $col->getLength(),
                'nullable' => !$col->getNotnull(),
                'default' => $col->getDefault(),
                'autoincrement' => $col->getAutoincrement(),
                'unsigned' => $col->getUnsigned(),
                'visible' => $this->isVisible($name),
                'indexed' => $indexedCols[$name] ?? null,
                'fillable' => $this->model->isFillable($name),
            ]];
        });

        $conn->setDatabaseName($defDatabaseName);

        return compact('columns', 'indexes');
    }




    /**
     * @param \Illuminate\Database\Eloquent\Model $model
     */
    public function getPropertiesFromMethods()
    {
        $out = [];
        $methods = get_class_methods($this->model);
        if ($methods) {
            sort($methods);
            foreach ($methods as $method) {
                if (
                    Str::startsWith($method, 'get') &&
                    Str::endsWith($method, 'Attribute') &&
                    $method !== 'getAttribute'
                ) {
                    $name = Str::snake(substr($method, 3, -9));
                    if (!empty($name)) {
                        $reflection = new \ReflectionMethod($this->model, $method);
                        $srvtype = $this->getReturnType($reflection);
                        $type = $this->phpTypeToGeneric($srvtype);
                        $fillable = false;

                        $out[] = compact('name', 'type', 'srvtype', 'fillable');
                    }
                }
            }
        }

        return collect($out)->mapWithKeys(function ($col, $i) {
            return [$col['name'] => $col];
        });
    }


    protected function getIndexedCols($indexes)
    {
        return $indexes->reduce(function ($carry, $item) {
            if (empty($item['flags']['fulltext']) && count($item['columns']) === 1) {
                foreach ($item['columns'] as $col => $bool) {
                    $carry[$col] = $item['primary'] ? 'primary' : ($item['unique'] ? 'key' : 'index');
                }
            }
            return $carry;
        }, []);
    }

    protected function mapIndexes($index)
    {
        if ($index instanceof Index) {
            return [
                'name' => $index->getName(),
                'primary' => $index->isPrimary(),
                'unique' => $index->isUnique(),
                'flags' => array_fill_keys($index->getFlags(), true),
                'columns' => array_fill_keys($index->getColumns(), true),
            ];
        } else if (is_array($index)) {
            return collect($index)->map(function ($item) {
                return $this->mapIndexes($item);
            });
        } else {
            return collect([]);
        }
    }



    protected function getReturnType(\ReflectionMethod $reflection): ?string
    {
        $type = $this->getReturnTypeFromDocBlock($reflection);
        if ($type) {
            return $type;
        }

        return $this->getReturnTypeFromReflection($reflection);
    }

    /**
     * Get method return type based on it DocBlock comment
     *
     * @param \ReflectionMethod $reflection
     *
     * @return null|string
     */
    protected function getReturnTypeFromDocBlock(\ReflectionMethod $reflection)
    {
        $phpDocContext = (new ContextFactory())->createFromReflector($reflection);
        $context = new Context(
            $phpDocContext->getNamespace(),
            $phpDocContext->getNamespaceAliases()
        );
        $type = null;
        $phpdoc = new DocBlock($reflection, $context);

        if ($phpdoc->hasTag('return')) {
            $type = $phpdoc->getTagsByName('return')[0]->getType();
        }

        return $type;
    }

    protected function getReturnTypeFromReflection(\ReflectionMethod $reflection): ?string
    {
        $returnType = $reflection->getReturnType();
        if (!$returnType) {
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
        if (!$paramType->isBuiltin()) {
            $parameterName = '\\' . $parameterName;
        }

        return $parameterName;
    }


    function phpTypeToGeneric($type)
    {
        switch ($type) {
            case 'mixed':
            case 'string':
                return 'string';
            case 'DateTime';
            case 'Carbon\\Carbon';
                return 'datetime';
            case 'integer':
                return 'integer';
            case 'float':
                return 'float';
            case 'boolean':
                return 'boolean';
            case 'boolean':
                return 'boolean';
        }
        return 'json';
    }

    function dbTypeToPhp($type)
    {
        switch ($type) {
            case 'string':
            case 'text':
            case 'date':
            case 'time':
            case 'guid':
            case 'datetimetz':
            case 'datetime':
            case 'decimal':
                $type = 'string';
                break;
            case 'integer':
            case 'bigint':
            case 'smallint':
                $type = 'integer';
                break;
            case 'boolean':
                switch (config('database.default')) {
                    case 'sqlite':
                    case 'mysql':
                        $type = 'integer';
                        break;
                    default:
                        $type = 'boolean';
                        break;
                }
                break;
            case 'float':
                $type = 'float';
                break;
            default:
                $type = 'mixed';
                break;
        }

        return $type;
    }

    /**
     * cast the properties's type from $casts.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     */
    protected function getCastPropertiesTypes()
    {
        $props = [];
        $casts = $this->model->getCasts();

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
            $props[$name] = $this->getTypeInModel($this->model, $realType);
        }

        return $props;
    }

    /**
     * @param  string  $type
     * @return string|null
     * @throws \ReflectionException
     */
    protected function checkForCustomLaravelCasts(string $type): ?string
    {
        if (!class_exists($type) || !interface_exists(CastsAttributes::class)) {
            return $type;
        }

        $reflection = new \ReflectionClass($type);

        if (!$reflection->implementsInterface(CastsAttributes::class)) {
            return $type;
        }

        $methodReflection = new \ReflectionMethod($type, 'get');

        $reflectionType = $this->getReturnTypeFromReflection($methodReflection);

        if ($reflectionType === null) {
            $reflectionType = $this->getReturnTypeFromDocBlock($methodReflection);
        }

        if ($reflectionType === 'static' || $reflectionType === '$this') {
            $reflectionType = $type;
        }

        return $reflectionType;
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

    protected function getClassNameInDestinationFile(object $model, string $className): string
    {
        $reflection = $model instanceof ReflectionClass
            ? $model
            : new ReflectionObject($model);

        $className = trim($className, '\\');

        return  $className;
    }


    protected $doctrineTypeMap = [
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

    protected $types = [
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
