<?php

use Statikbe\Surveyhero\Models\Survey;
use Statikbe\Surveyhero\Models\SurveyQuestion;
use Statikbe\Surveyhero\Models\SurveyQuestionResponse;
use Statikbe\Surveyhero\Models\SurveyResponse;
use Statikbe\Surveyhero\Services\Factories\ResponseCreator\TextResponseCreator;

function makeTextApiAnswer(int $elementId, string $text): stdClass
{
    return (object) [
        'element_id' => $elementId,
        'type' => 'text',
        'text' => $text,
    ];
}

it('creates a question response for a text answer', function () {
    $survey = Survey::factory()->create(['surveyhero_id' => 1234567]);
    $question = SurveyQuestion::factory()->create([
        'survey_id' => $survey->id,
        'surveyhero_element_id' => 1000005,
        'surveyhero_question_id' => 1000005,
        'field' => 'question_5',
    ]);
    $response = SurveyResponse::factory()->create(['survey_id' => $survey->id]);

    $creator = new TextResponseCreator;
    $mapping = ['question_id' => 1000005, 'type' => 'text', 'field' => 'question_5', 'mapped_data_type' => 'string'];

    $creator->updateOrCreateQuestionResponse(makeTextApiAnswer(1000005, 'Software Engineer'), $response, $mapping);

    expect(SurveyQuestionResponse::count())->toBe(1);
    $qr = SurveyQuestionResponse::first();
    expect($qr->surveyAnswer->converted_string_value)->toBe('Software Engineer');
});

it('updates an existing text question response', function () {
    $survey = Survey::factory()->create(['surveyhero_id' => 1234567]);
    $question = SurveyQuestion::factory()->create([
        'survey_id' => $survey->id,
        'surveyhero_element_id' => 1000005,
        'surveyhero_question_id' => 1000005,
        'field' => 'question_5',
    ]);
    $response = SurveyResponse::factory()->create(['survey_id' => $survey->id]);
    $mapping = ['question_id' => 1000005, 'type' => 'text', 'field' => 'question_5', 'mapped_data_type' => 'string'];

    $creator = new TextResponseCreator;
    $creator->updateOrCreateQuestionResponse(makeTextApiAnswer(1000005, 'First Answer'), $response, $mapping);
    $creator->updateOrCreateQuestionResponse(makeTextApiAnswer(1000005, 'Updated Answer'), $response, $mapping);

    expect(SurveyQuestionResponse::count())->toBe(1);
});

it('creates a numeric answer when mapped_data_type is int', function () {
    $survey = Survey::factory()->create(['surveyhero_id' => 1234567]);
    SurveyQuestion::factory()->create([
        'survey_id' => $survey->id,
        'surveyhero_element_id' => 1000006,
        'surveyhero_question_id' => 1000006,
        'field' => 'question_6',
    ]);
    $response = SurveyResponse::factory()->create(['survey_id' => $survey->id]);
    $mapping = ['question_id' => 1000006, 'type' => 'text', 'field' => 'question_6', 'mapped_data_type' => 'int'];

    $creator = new TextResponseCreator;
    $creator->updateOrCreateQuestionResponse(makeTextApiAnswer(1000006, '42'), $response, $mapping);

    $qr = SurveyQuestionResponse::first();
    expect($qr->surveyAnswer->converted_int_value)->toBe(42);
});
