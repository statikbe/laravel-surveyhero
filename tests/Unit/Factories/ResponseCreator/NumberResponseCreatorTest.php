<?php

use Statikbe\Surveyhero\Models\Survey;
use Statikbe\Surveyhero\Models\SurveyQuestion;
use Statikbe\Surveyhero\Models\SurveyQuestionResponse;
use Statikbe\Surveyhero\Models\SurveyResponse;
use Statikbe\Surveyhero\Services\Factories\ResponseCreator\NumberResponseCreator;

it('creates a question response for a number answer', function () {
    $survey = Survey::factory()->create();
    $question = SurveyQuestion::factory()->create([
        'survey_id' => $survey->id,
        'surveyhero_element_id' => 1000006,
        'surveyhero_question_id' => 1000006,
        'field' => 'question_6',
    ]);
    $response = SurveyResponse::factory()->create(['survey_id' => $survey->id]);

    $apiAnswer = (object) [
        'element_id' => 1000006,
        'type' => 'number',
        'number' => 32,
    ];
    $mapping = ['question_id' => 1000006, 'type' => 'number', 'field' => 'question_6', 'mapped_data_type' => 'int'];

    $creator = new NumberResponseCreator;
    $creator->updateOrCreateQuestionResponse($apiAnswer, $response, $mapping);

    expect(SurveyQuestionResponse::count())->toBe(1);
    $qr = SurveyQuestionResponse::first();
    expect($qr->surveyAnswer->converted_int_value)->toBe(32);
});
