<?php

namespace Statikbe\Surveyhero\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Statikbe\Surveyhero\Models\Survey;
use Statikbe\Surveyhero\Models\SurveyQuestion;

class SurveyQuestionFactory extends Factory
{
    protected $model = SurveyQuestion::class;

    public function definition(): array
    {
        return [
            'survey_id' => Survey::factory(),
            'surveyhero_element_id' => $this->faker->unique()->numerify('#######'),
            'surveyhero_question_id' => $this->faker->unique()->numerify('#######'),
            'field' => 'question_'.$this->faker->unique()->numberBetween(1, 100),
            'label' => ['en' => $this->faker->sentence()],
        ];
    }
}
