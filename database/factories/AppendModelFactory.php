<?php

namespace Spatie\QueryBuilder\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Spatie\QueryBuilder\Tests\TestClasses\Models\AppendModel;

class AppendModelFactory extends Factory
{
    protected $model = AppendModel::class;

    public function definition()
    {
        return [
            'firstname' => $this->faker->firstName,
            'lastname' => $this->faker->lastName,
        ];
    }
}
