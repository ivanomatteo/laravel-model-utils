<?php

declare(strict_types=1);

namespace IvanoMatteo\ModelUtils\Traits;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * @method array getAttributesMetadata()
 */
trait ValidationUtil
{
    use AttributesMetadata;

    public function getValidationRules(): Collection
    {
        $columns = $this->getAttributesMetadata()['columns'];

        return $columns
            ->filter(fn($item)=>(empty($item['is_accessor']) || !empty($item['has_mutator'])))
            ->map(function ($item, $key) {
            $rules = [];
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
                    if(!empty($item['length'])) {
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
                    if(!empty($item['length'])) {
                        $rules[] = "max:" . $item['length'];
                    }
                    break;
                default:

                    break;
            }

            return $rules;
        });
    }
}
