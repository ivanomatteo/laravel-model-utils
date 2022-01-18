<?php

declare(strict_types=1);

namespace IvanoMatteo\ModelUtils\Tests;

use IvanoMatteo\ModelUtils\ModelMetadata;
use IvanoMatteo\ModelUtils\ReflectionMetadata;
use IvanoMatteo\ModelUtils\ReflectionModelMetadata;
use IvanoMatteo\ModelUtils\Tests\Models\FooClass;
use ReflectionClass;

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
    $res = $ref->getAccessorsMetadata(FooClass::class);

    $expected = [
        'foo_bar' => [
            "name" => 'foo_bar',
            "type" => 'string',
            "has_mutator" => true,
            "is_accessor" => true,
        ],
    ];

    expect($res)->toMatchArray($expected);
});


it('can read casts', function () {
    $ref = new ReflectionModelMetadata();
    $res = $ref->getCastAttributesTypes(new FooClass());

    $expected = [
        "id" => 'integer',
        "some_field" => 'object',
    ];

    expect($res)->toMatchArray($expected);
});


it('can read database properties', function () {
    $meta = new ModelMetadata(FooClass::class);
    $res = $meta->getDatabaseColumns();

    expect($res)->toHaveKeys(['id', 'name', 'age', 'memo', 'data','some_field']);
});


it('can read all attributes properties', function () {
    $meta = new ModelMetadata(FooClass::class);

    $res = $meta->getAttributesMetadata();

    expect($res['columns'])->toHaveKeys(['id', 'name', 'age', 'memo', 'data','some_field','foo_bar']);
});

it('can respect hidden attributes', function () {
    $meta = new ModelMetadata(FooClass::class);

    $res = $meta->getAttributesMetadata();
    expect($res['columns'])->not()->toHaveKeys(['password']);

    $res = $meta->getAttributesMetadata(true);
    expect($res['columns'])->toHaveKeys(['password']);
});


it('can respect hidden attributes - model trait', function () {
    $f = new FooClass();

    $f->makeHidden('some_field');
    $res = $f->getAttributesMetadata();
    expect($res['columns'])->not()->toHaveKeys(['some_field']);

    $f->makeVisible('some_field');
    $res = $f->getAttributesMetadata();
    expect($res['columns'])->toHaveKeys(['some_field']);
});

it('can respect hidden attributes - accessors model trait', function () {
    $f = new FooClass();

    $f->makeHidden('foo_bar');
    $res = $f->getAttributesMetadata();
    expect($res['columns'])->not()->toHaveKeys(['foo_bar']);

    $f->makeVisible('foo_bar');
    $res = $f->getAttributesMetadata();
    expect($res['columns'])->toHaveKeys(['foo_bar']);
});

it('can generate validation rules', function () {
    $f = new FooClass();

    $rules = $f->getBasicValidationRules();

    expect($rules)->toHaveKeys(['id', 'name', 'age', 'memo', 'data','some_field','foo_bar']);
    expect($rules['memo'])->toMatchArray(["required", "string"]);
});
