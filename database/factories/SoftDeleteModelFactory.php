<?php

namespace Spatie\QueryBuilder\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Spatie\QueryBuilder\Tests\TestClasses\Models\SoftDeleteModel;

class SoftDeleteModelFactory extends Factory
{
    protected $model = SoftDeleteModel::class;

    public function definition()
    {
        return [
            'name' => $this->faker->name,
        ];
    }
}
