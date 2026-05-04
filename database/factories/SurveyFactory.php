<?php

namespace Statikbe\Surveyhero\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Statikbe\Surveyhero\Models\Survey;

class SurveyFactory extends Factory
{
    protected $model = Survey::class;

    public function definition(): array
    {
        return [
            'surveyhero_id' => fake()->unique()->numberBetween(1000000, 9999999),
            'name' => fake()->sentence(3),
            'use_resume_link' => false,
        ];
    }
}
