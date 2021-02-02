<?php

namespace IvanoMatteo\ModelUtils;

use Illuminate\Database\Eloquent\Model;
use ReflectionClass;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Schema\Column;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use ReflectionNamedType;
use phpDocumentor\Reflection\Types\ContextFactory;
use Barryvdh\Reflection\DocBlock\Context;
use Barryvdh\Reflection\DocBlock;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Table;
use Illuminate\Support\Collection;
use PDO;
use ReflectionType;

/**
 * @property Model $model
 */
class ModelUtils
{
    public static $doctrineTypeMap = [
        "bigint" => 'integer',
        "integer" => 'integer',
        "smallint" => 'integer',
        "float" => 'float',
        "decimal" => 'float',
        "boolean" => 'boolean',
        "time" => 'time',
        "datetime" => 'datetime',
        "date" => 'date',
        "json" => 'object',
        "text" => 'text',
        "string" => 'string',
        "blob" => 'blob',
    ];

    public static $types = [
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

    /*
    casts:
    integer, real, float, double, decimal:<digits>, string, boolean,
    object, array,
    collection, date, datetime, and timestamp.
    */

    private $model;
    private $reflectionClass;

    private $dbTableMetadata;

    private $visibleMap;
    private $hiddenMap;


    function __construct($classOrObj)
    {
        $this->setModel($classOrObj);
    }

    private function setModel($classOrObj)
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

        return $this->model;
    }



    function getReflectionClass()
    {
        return $this->reflectionClass;
    }

    /**
     * @return ReflectionClass[]
     */
    static function findModels($basePath = null, $baseNamespace = "App")
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

                $relativeFQCN = $baseNamespace . "$relativeFQCN";

                if (\Str::startsWith($relativeFQCN, "\\")) {
                    $relativeFQCN = substr($relativeFQCN, 1, strlen($relativeFQCN) - 1);
                }

                include_once $item->getRealPath();

                if (class_exists($relativeFQCN, false)) {
                    $rc = new ReflectionClass($relativeFQCN);
                    if ($rc->isSubclassOf(Model::class)) {
                        $out[] = $rc;
                    }
                }
            }
        }
        return $out;
    }





    //new ---------------------------------------

    public function isVisible($f)
    {
        if (!isset($this->hiddenMap)) {
            $this->hiddenMap = empty($this->model->getHidden()) ? false : array_flip($this->model->getHidden());
            $this->visibleMap = empty($this->model->getVisible()) ? false : array_flip($this->model->getVisible());
        }

        $is_visible = true;

        if ($this->visibleMap && !isset($this->visibleMap[$f])) {
            $is_visible = false;
        }
        if ($this->hiddenMap && isset($this->hiddenMap[$f])) {
            $is_visible = false;
        }

        return $is_visible;
    }



    public function getValidationRules()
    {
        /* $this->getDatabaseMetadata()->mapWithKeys(function (Column $col, $name) {
            return [$name => ''];
        }); */
    }


    public function getProperties()
    {
        $accessors = $this->getPropertiesFromMethods();
        $db_meta = $this->getDatabaseMetadata();
        $columns = $db_meta['columns'];
        $indexes = $db_meta['indexes'];

        return compact('accessors', 'columns', 'indexes');
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

        $columns = collect($schema->listTableColumns(
            $table,
            $database
        ))->mapWithKeys(function (Column $col, $name) use ($indexedCols) {
            /** @var Type */
            $type = $col->getType();
            return [$name => [
                'name' => $name,
                'type' => static::$doctrineTypeMap[$type->getName()],
                'phptype' => $this->dbTypeToPhp($type->getName()),
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
                        $type = $this->getReturnType($reflection);
                        $fillable = false;

                        $out[] = compact('name', 'type', 'fillable');
                    }
                }
            }
        }

        return collect($out)->mapWithKeys(function ($col, $i) {
            return [$col['name'] => $col];
        });
    }


    private function getIndexedCols($indexes)
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

    private function mapIndexes($index)
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
}
