<?php

use Faker\Generator as Faker;
use Spatie\QueryBuilder\Tests\Models\TestModel;

$factory->define(TestModel::class, function (Faker $faker) {
    return [
        'name' => $faker->name,
    ];
});
