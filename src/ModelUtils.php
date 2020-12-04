<?php

namespace IvanoMatteo\ModelUtils;

use Illuminate\Database\Eloquent\Model;
use ReflectionClass;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Schema\Column;
use Illuminate\Support\Facades\DB;

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

    public function getDBMetadata($describerDriver = null)
    {
        if (!isset($this->dbTableMetadata)) {

            if ($describerDriver) {
                $dri = resolve($describerDriver, [$this->model->getTable()]);
            } else {

                if ($describerDriver === 'default') {
                    $this->dbTableMetadata = $this->getDoctrineSchemaInfo();
                } else {
                    $db_driver = \DB::getDriverName();
                    $dri = null;
                    $libDriver = __NAMESPACE__ . "\\Drivers\\" . ucfirst($db_driver);

                    if (class_exists($libDriver)) {
                        $dri = new $libDriver($this->model->getTable());
                        $this->dbTableMetadata = $dri->describe();
                    } else {
                        $this->dbTableMetadata = $this->getDoctrineSchemaInfo();
                    }
                }
            }
        }
        return $this->dbTableMetadata;
    }


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

    public function getColumns()
    {
        $conn = $this->model->getConnection();
        $prefix = $conn->getTablePrefix();
        $schema = $conn->getDoctrineSchemaManager();
        $databasePlatform = $schema->getDatabasePlatform();
        $databasePlatform->registerDoctrineTypeMapping('enum', 'string');

        $table = $prefix . $this->model->getTable();
        $database = null;
        if (strpos($table, '.')) {
            [$database, $table] = explode('.', $table);
        }

        return collect($schema->listTableColumns(
            $table,
            $database
        ))
            ->mapWithKeys(function (Column $col, $name) {
                /** @var Type */
                $type = $col->getType();
                return [$name => [
                    'type' => static::$doctrineTypeMap[$type->getName()],
                    'length' => $col->getLength(),
                    'nullable' => !$col->getNotnull(),
                    'default' => $col->getDefault(),
                    'autoincrement' => $col->getAutoincrement(),
                    'unsigned' => $col->getUnsigned(),
                    'visible' => $this->isVisible($name),
                    'fillable' => $this->model->isFillable($name),
                ]];
            });
    }

    public function getValidationRules(){
        $this->getColumns()->mapWithKeys(function(Column $col, $name){
            return [$name => ''];
        });
    }


    private function getDoctrineSchemaInfo()
    {
        $b = \DB::getSchemaBuilder();
        $cols = $b->getColumnListing($this->model->getTable());
        $res = [];
        foreach ($cols as $key => $value) {
            $res[$value] = [
                'type' => static::$doctrineTypeMap[$b->getColumnType($this->model->getTable(), $value)]
            ];
        }
        return $res;
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
}
