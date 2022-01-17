<?php

use Illuminate\Database\Eloquent\Model;
use IvanoMatteo\ModelUtils\ReflectionMetadata;
use IvanoMatteo\ModelUtils\ReflectionModelMetadata;

class FooClass extends Model
{

    protected $casts = [
        'some_field' => 'object'
    ];

    /** @return int */
    function doc()
    {
    }

    function type(): string
    {
        return '';
    }

    function getFooBarAttribute(): string
    {
        return 'baz';
    }

    function setFooBarAttribute(string $value): void
    {
    }
}


it('can read reflection metadata', function () {

    $refMeta = new ReflectionMetadata();
    $class = new ReflectionClass(FooClass::class);

    expect($refMeta->getReturnTypeFromDocBlock($class->getMethod('doc')))->toBe('int');
    expect($refMeta->getReturnTypeFromDocBlock($class->getMethod('type')))->toBe(null);
    expect($refMeta->getReturnTypeFromReflection($class->getMethod('type')))->toBe('string');
    expect($refMeta->getReturnTypeFromReflection($class->getMethod('doc')))->toBe(null);
});


it('can read accessors and mutators', function () {
    $ref = new ReflectionModelMetadata();

    $res = $ref->getPropertiesFromAccessors(FooClass::class);

    $expected = [
        'foo_bar' => [
            "name" => 'foo_bar',
            "type" => 'string',
            "has_mutator" => true,
        ]
    ];

    expect($res)->toMatchArray($expected);
});


it('can read casts', function () {
    $ref = new ReflectionModelMetadata();

    $res = $ref->getCastPropertiesTypes(new FooClass);


    $expected = [
        "id" => 'integer',
        "some_field" => 'object',
    ];

    expect($res)->toMatchArray($expected);
});
