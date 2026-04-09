<?php

namespace Statikbe\Surveyhero\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Statikbe\Surveyhero\Models\Survey;
use Statikbe\Surveyhero\Models\SurveyQuestion;

class SurveyQuestionFactory extends Factory
{
    protected $model = SurveyQuestion::class;

    public function definition(): array
    {
        return [
            'survey_id' => Survey::factory(),
            'surveyhero_element_id' => fake()->unique()->numerify('#######'),
            'surveyhero_question_id' => fake()->unique()->numerify('#######'),
            'field' => 'question_'.Str::random(8),
            'label' => ['en' => fake()->sentence()],
        ];
    }
}
