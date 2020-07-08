<?php

namespace IvanoMatteo\ModelUtils\Drivers;

use Illuminate\Support\Facades\DB;


class Mysql
{

    private $dbtypeMap = [

        'int' => 'integer',
        'mediumint' => 'integer',
        'bigint' => 'integer',
        'smallint' => 'integer',

        'tinyint' => 'boolean',
        'bit' => 'boolean',

        'float' => 'float',
        'double' => 'float',
        'decimal' => 'float',
        'real' => 'float',

        'date' => 'date',
        'datetime' => 'datetime',
        'timestamp' => 'datetime',
        'time' => 'time',
        'year' => 'year',

        'char' => 'string',
        'nchar' => 'string',
        'varchar' => 'string',
        'nvarchar' => 'string',
        'tynytext' => 'string',

        'text' => 'text',
        'mediumtext' => 'text',
        'longtext' => 'text',

        'json' => 'json',

        'tynyblob' => 'blob',
        'blob' => 'blob',
        'mediumblob' => 'blob',
        'longblob' => 'blob',
        'binary' => 'blob',
        'varbinary' => 'blob',

        'enum' => 'enum',
    ];

    private $typeSizes = [
        'tynyblob' => '255',
        'blob' => '65535',
        'mediumblob' => '16777215',
        'longblob' => '4294967295',

        'tynytext' => '255',
        'text' => '65535',
        'mediumtext' => '16777215',
        'longtext' => '4294967295',
    ];

    private $table;
    private $dbMetadata;

    function __construct($table)
    {
        $this->table = $table;
    }



    function describe()
    {
        if (isset($this->dbMetadata)) {
            return $this->dbMetadata;
        }

        /*

            $dbfld[$i]["Field"] "id",
            $dbfld[$i]["Type"] "bigint(20) unsigned",
            $dbfld[$i]["Null"] "NO",
            $dbfld[$i]["Key"] "PRI" | "UNI",
            $dbfld[$i]["Default"] null,
            $dbfld[$i]["Extra"] "auto_increment"

            */

        $dbfld = \DB::select(\DB::raw("describe " . static::encForMysqlCol($this->table)));

        $len = count($dbfld);

        $this->dbMetadata = [];


        for ($i = 0; $i < $len; $i++) {

            unset($m); // better to unset when using reference inside loop
            preg_match("/([^\\( ]+)(\\(([^\)]+)\\))?/", $dbfld[$i]->Type, $m);

            $dbfld[$i]->TypeOnly = $m[1];
            $dbfld[$i]->SizeOnly = $m[3] ?? $this->typeSizes[$m[1]] ?? null;

            if ($dbfld[$i]->TypeOnly === 'enum' && $dbfld[$i]->SizeOnly) {
                preg_match_all("/'(''|\\\\\\'|[^'])*'/", $dbfld[$i]->Type, $enum_m);
                if(!empty($enum_m[0])){
                    $dbfld[$i]->SizeOnly = json_encode($enum_m[0]);
                }
            }

            $dbfld[$i]->TypeGeneric = $this->dbtypeMap[strtolower($m[1])] ?? null;
            if (empty($dbfld[$i]->TypeGeneric)) {
                abort(500, "type " . $m[1] . " unknown for driver: mysql");
                //\Schema::getColumnType($this->getTable(), $dbfld[$i]->Field);
                //\Schema::getColumnListing()
            }


            $this->dbMetadata[$dbfld[$i]->Field] = [
                'dbtype_full' => $dbfld[$i]->Type,
                'nullable' => $dbfld[$i]->Null === 'YES',
                'key' => empty($dbfld[$i]->Key) ? false : $dbfld[$i]->Key,
                'default' => $dbfld[$i]->Default,
                'dbtype' => $dbfld[$i]->TypeOnly,
                'dbsize' => isset($dbfld[$i]->SizeOnly) ? $dbfld[$i]->SizeOnly : null,
                'type' => $dbfld[$i]->TypeGeneric,
                'extra' => empty($dbfld[$i]->Extra) ? null : $dbfld[$i]->Extra,
            ];
        }

        return $this->dbMetadata;
    }


    public static function encForMysqlCol($name,$as = false) {

        if (empty($name)) {
            return $name;
        }

        if (is_array($name)) {
            foreach (array_keys($name) as $key) {
                $name[$key] = static::encForMysqlCol($name[$key]);
            }
            return $name;
        }

        if (empty($name)) {
            throw new \Exception("invalid column name");
        }
        $len = strlen($name);
        if ($len > 64) {
            throw new \Exception("name is longer than 64 characters");
        }
        $prohibited = [
            '\0' => TRUE, '/' => TRUE, '\\' => TRUE,
            //aggiunti
            '\'' => TRUE,
        ];
        for ($i = 0; $i < $len; $i++) {
            if (!empty($prohibited[$name[$i]])) {
                throw new \Exception("name contains invalid characters characters");
            }
        }


        $tmp = str_replace('`', '', trim($name));
        $tmp = explode('.', $tmp);

        $escaped = "`" . implode('`.`', $tmp) . "`".(($as && count($tmp)>1)?(" as `" . implode('.', $tmp) . "`"):'');
        
        return  $escaped;
    }
    
}
