<?php

namespace Statikbe\Surveyhero\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Statikbe\Surveyhero\Models\SurveyAnswer;
use Statikbe\Surveyhero\Models\SurveyQuestion;
use Statikbe\Surveyhero\Models\SurveyQuestionResponse;
use Statikbe\Surveyhero\Models\SurveyResponse;

class SurveyQuestionResponseFactory extends Factory
{
    protected $model = SurveyQuestionResponse::class;

    public function definition(): array
    {
        return [
            'survey_response_id' => SurveyResponse::factory(),
            'survey_question_id' => SurveyQuestion::factory(),
            'survey_answer_id' => null,
        ];
    }
}
