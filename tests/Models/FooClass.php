<?php

declare(strict_types=1);

namespace IvanoMatteo\ModelUtils\Tests\Models;

use Illuminate\Database\Eloquent\Model;

class FooClass extends Model
{
    protected $casts = [
        'data' => 'array',
        'some_field' => 'object',
    ];

    protected $hidden = ['password'];

    /** @return int */
    public function doc()
    {
    }

    public function type(): string
    {
        return '';
    }

    public function getFooBarAttribute(): string
    {
        return 'baz';
    }

    public function setFooBarAttribute(string $value): void
    {
    }
}
