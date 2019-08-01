<?php

use Faker\Generator as Faker;
use Spatie\QueryBuilder\Tests\TestClasses\Models\AppendModel;

$factory->define(AppendModel::class, function (Faker $faker) {
    return [
        'firstname' => $faker->firstName,
        'lastname' => $faker->lastName,
    ];
});
