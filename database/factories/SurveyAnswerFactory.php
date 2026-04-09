<?php

namespace Statikbe\Surveyhero\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Statikbe\Surveyhero\Models\SurveyAnswer;
use Statikbe\Surveyhero\Models\SurveyQuestion;

class SurveyAnswerFactory extends Factory
{
    protected $model = SurveyAnswer::class;

    public function definition(): array
    {
        return [
            'survey_question_id' => SurveyQuestion::factory(),
            'surveyhero_answer_id' => fake()->unique()->numerify('########'),
            'converted_string_value' => null,
            'converted_int_value' => fake()->numberBetween(1, 5),
            'label' => ['en' => fake()->word()],
        ];
    }
}
