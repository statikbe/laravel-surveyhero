<?php

namespace Statikbe\Surveyhero\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Statikbe\Surveyhero\Models\Survey;
use Statikbe\Surveyhero\Models\SurveyResponse;

class SurveyResponseFactory extends Factory
{
    protected $model = SurveyResponse::class;

    public function definition(): array
    {
        return [
            'survey_id' => Survey::factory(),
            'surveyhero_id' => fake()->unique()->numerify('####'),
            'survey_start_date' => now()->subHour(),
            'survey_last_updated' => now(),
            'survey_language' => 'en',
            'survey_completed' => true,
        ];
    }
}
