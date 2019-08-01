<?php

use Faker\Generator as Faker;
use Spatie\QueryBuilder\Tests\TestClasses\Models\SoftDeleteModel;

$factory->define(SoftDeleteModel::class, function (Faker $faker) {
    return [
        'name' => $faker->name,
    ];
});
