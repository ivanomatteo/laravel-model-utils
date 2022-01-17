<?php
declare(strict_types=1);

namespace IvanoMatteo\ModelUtils\Traits;

use Exception;
use Illuminate\Support\Collection;

/**
 * @method string getTable()
 * @method \Illuminate\Database\Connection getConnection()
 */
trait DatabaseMetadata
{
    use VisibilityCheck;

    /** @return array[] */
    public function getDatabaseColumns($all = false)
    {
        $table = $this->getTable();

        $schema = $this->getConnection()->getDoctrineSchemaManager();
        $parts = array_reverse(explode('.', $table));


        /** @var Collection<\Doctrine\DBAL\Schema\Column> */
        $columns = collect($schema->listTableColumns($parts[0], $parts[1] ?? null))
            ->mapWithKeys(function (\Doctrine\DBAL\Schema\Column $col) {
                return [
                    $col->getName() => [
                        'name' => $col->getName(),
                        'type' => $col->getType()->getName(),
                        'autoincrement' => $col->getAutoincrement(),
                        'length' => $col->getLength(),
                        'nullable' => !$col->getNotnull(),
                        'default' => $col->getDefault(),
                        'precision' => $col->getPrecision(),
                        'options' => $col->getPlatformOptions(),
                    ]
                ];
            });

        if ($all) {
            return $columns->toArray();
        }

        return collect($columns)->filter(
            function (array $item) {
                return $this->isVisible($item['name']);
            }
        )->toArray();
    }

    /** @return array[] */
    function getDatabaseIndexes()
    {
        $table = $this->getTable();
        $parts = array_reverse(explode('.', $table));

        if ($parts[1] ?? null) {
            // 'unsupported database.table notation'
            return [];
        }

        /** @var \Doctrine\DBAL\Schema\MySQLSchemaManager */
        $schema = $this->getConnection()->getDoctrineSchemaManager();

        return collect($schema->listTableIndexes($table))
            ->mapWithKeys(function (\Doctrine\DBAL\Schema\Index $ind) {
                return [
                    $ind->getName() => [
                        'name' => $ind->getName(),
                        'columns' => $ind->getColumns(),
                        'primary' => $ind->isPrimary(),
                        'unique' => $ind->isUnique(),
                        'options' => $ind->getOptions(),
                        'flags' => $ind->getFlags(),
                    ]
                ];
            })->toArray();
    }
}
