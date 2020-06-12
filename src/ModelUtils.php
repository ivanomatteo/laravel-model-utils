<?php

namespace IvanoMatteo\ModelUtils;

use Illuminate\Database\Eloquent\Model;
use ReflectionClass;



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

        if ($classOrObj instanceof ReflectionClass) {
            $this->reflectionClass = $classOrObj;
        }else{
            $this->reflectionClass = new ReflectionClass($this->model);
        }

        if (is_string($classOrObj)) {
            $this->model = resolve($classOrObj); // laravel resolve() helper
        } else if (is_a($classOrObj, Model::class)) {
            $this->model = $classOrObj;
        } else if ($classOrObj instanceof ReflectionClass) {
            $this->model = resolve($classOrObj->getName());
          
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
    static function findModels($path = null, $baseNamespace = "App")
    {
        if(!isset($path)){
            $path = app_path('');
        }
        $baseNamespace = preg_replace("/^\\\\/", '', $baseNamespace);
        $baseNamespace = preg_replace("/\\\\$/", '', $baseNamespace);

        $out = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $path
            ),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($iterator as $item) {
            /**
             * @var \SplFileInfo $item
             */
            if ($item->isReadable() && $item->isFile() && mb_strtolower($item->getExtension()) === 'php') {
                $c = str_replace(
                    "/",
                    "\\",
                    mb_substr($item->getRealPath(), mb_strlen($path), -4)
                );

                $c = $baseNamespace . "$c";

                if (\Str::startsWith($c, "\\")) {
                    $c = substr($c, 1, strlen($c) - 1);
                }

                include_once $item->getRealPath();

                if (class_exists($c, false)) {
                    $rc = new ReflectionClass($c);
                    if ($rc->isSubclassOf(Model::class)) {
                        $out[] = $rc;
                    }
                }
            }
        }
        return $out;
    }
}
