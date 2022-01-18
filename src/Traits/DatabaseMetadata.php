<?php

declare(strict_types=1);

namespace IvanoMatteo\ModelUtils\Traits;

use Doctrine\DBAL\Schema\Column;
use Illuminate\Support\Collection;

/**
 * @method string getTable()
 * @method \Illuminate\Database\Connection getConnection()
 */
trait DatabaseMetadata
{
    use VisibilityCheck;

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function getDatabaseColumns($all = false): array
    {
        $table = $this->getTable();

        $schema = $this->getConnection()->getDoctrineSchemaManager();
        $parts = array_reverse(explode('.', $table));


        /** @var Collection<Column> */
        $columns = collect($schema->listTableColumns($parts[0], $parts[1] ?? null))
            ->mapWithKeys(function (Column $col) {
                return [
                    $col->getName() => [
                        'name' => $col->getName(),
                        'type' => $col->getType()->getName(),
                        'autoincrement' => $col->getAutoincrement(),
                        'length' => $col->getLength(),
                        'nullable' => ! $col->getNotnull(),
                        'default' => $col->getDefault(),
                        'precision' => $col->getPrecision(),
                        'options' => $col->getPlatformOptions(),
                        'is_db_field' => true,
                    ],
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

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function getDatabaseIndexes(): array
    {
        $table = $this->getTable();
        $parts = array_reverse(explode('.', $table));

        if ($parts[1] ?? null) {
            // 'unsupported database.table notation'
            return [];
        }

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
                    ],
                ];
            })->toArray();
    }
}
