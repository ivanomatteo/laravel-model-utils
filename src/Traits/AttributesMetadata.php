<?php

declare(strict_types=1);

namespace IvanoMatteo\ModelUtils\Traits;

use IvanoMatteo\ModelUtils\TypeMapper;

/**
 * @method array getHidden()
 * @method array getVisible()
 */
trait ModelMetadataTrait
{
    use DatabaseMetadata;
    use ReflectionMetadata;

    public function getAttributesMetadata($all = false)
    {
        $typeMapper = new TypeMapper();
        $columns = $this->getDatabaseColumns($all);
        $accessors = $this->getAccessorsMetadata($all);
        $cast = $this->getCastAttributesTypes();
        $indexes = $this->getDatabaseIndexes();

        foreach ($indexes as $name => $props) {
            if (count($props['columns']) === 1 && isset($columns[$props['columns'][0]])) {
                $colName = $props['columns'][0];

                $columns[$colName]['index'][] = $props['name'];

                if ($props['primary']) {
                    $columns[$colName]['primary'] = true;
                }
                if ($props['unique']) {
                    $columns[$colName]['unique'] = true;
                }
            }
        }

        $columns = collect($columns)->merge($accessors)
            ->map(function ($item) use ($cast, $typeMapper) {
                if (isset($cast[$item['name']])) {
                    $item['type'] = $typeMapper->phpToGeneric($cast[$item['name']]);
                }

                return $item;
            })->toArray();

        return compact('columns', 'indexes');
    }
}
