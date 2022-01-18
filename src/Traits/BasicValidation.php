<?php

declare(strict_types=1);

namespace IvanoMatteo\ModelUtils\Traits;

use DateTimeInterface;

/**
 * @method array getAttributesMetadata()
 */
trait BasicValidation
{
    use AttributesMetadata;

    public function getBasicValidationRules(): array
    {
        $columns = $this->getAttributesMetadata()['columns'];


        return collect($columns)
            ->filter(fn ($item) => (empty($item['is_accessor']) || ! empty($item['has_mutator'])))
            ->map(function ($item, $key) {
                $rules = [];

                if ($this->isAttributeRequired($item)) {
                    $rules[] = 'required';
                } else {
                    $rules[] = 'sometimes';
                    $rules[] = 'nullable';
                }

                switch ($item['type']) {
                case 'integer':
                    $rules[] = 'integer';

                    break;
                case 'float':
                    $rules[] = 'numeric';

                    break;
                case 'string':
                case 'text':
                case 'blob':
                    $rules[] = 'string';
                    if (! empty($item['length'])) {
                        $rules[] = "max:" . $item['length'];
                    }

                    break;
                case 'date':
                    $rules[] = 'date_format:Y-m-d';

                    break;
                case 'datetime':
                    $rules[] = 'date_format:'. DateTimeInterface::ISO8601;

                    break;
                case 'time':
                    $rules[] = 'date_format:H:i:s';

                    break;
                case 'json':
                    $rules[] = 'json';
                    if (! empty($item['length'])) {
                        $rules[] = "max:" . $item['length'];
                    }

                    break;
                default:

                    break;
            }

                return $rules;
            })->toArray();
    }

    protected function isAttributeRequired($item): bool
    {
        if (empty($item['is_db_field'])) {
            return false;
        }

        if (! empty($item['autoincrement'])) {
            return false;
        }

        if (! empty($item['nullable'])) {
            return false;
        }

        if (! empty($item['default'])) {
            return false;
        }

        return true;
    }
}
