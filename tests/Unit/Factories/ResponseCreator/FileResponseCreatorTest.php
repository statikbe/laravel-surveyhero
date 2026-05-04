<?php

use Statikbe\Surveyhero\Models\Survey;
use Statikbe\Surveyhero\Models\SurveyQuestion;
use Statikbe\Surveyhero\Models\SurveyQuestionResponse;
use Statikbe\Surveyhero\Models\SurveyResponse;
use Statikbe\Surveyhero\Services\Factories\ResponseCreator\FileResponseCreator;

function makeFileApiAnswer(int $elementId, string $path): stdClass
{
    return (object) [
        'element_id' => $elementId,
        'type' => 'file',
        'file' => (object) [
            'name' => 'Galapagos.jpg',
            'size' => 480447,
            'path' => $path,
        ],
    ];
}

it('stores the file path as converted_string_value', function () {
    $survey = Survey::factory()->create(['surveyhero_id' => 1234567]);
    SurveyQuestion::factory()->create([
        'survey_id' => $survey->id,
        'surveyhero_element_id' => 1387752,
        'surveyhero_question_id' => 1387752,
        'field' => 'question_1',
    ]);
    $response = SurveyResponse::factory()->create(['survey_id' => $survey->id]);
    $mapping = ['question_id' => 1387752, 'type' => 'file_upload', 'field' => 'question_1', 'mapped_data_type' => 'string'];

    $creator = new FileResponseCreator;
    $creator->updateOrCreateQuestionResponse(
        makeFileApiAnswer(1387752, '/v1/download/element/1387752/response/3875825'),
        $response,
        $mapping
    );

    expect(SurveyQuestionResponse::count())->toBe(1);
    $qr = SurveyQuestionResponse::first();
    expect($qr->surveyAnswer->converted_string_value)->toBe('/v1/download/element/1387752/response/3875825');
});

it('updates the existing response on re-import', function () {
    $survey = Survey::factory()->create(['surveyhero_id' => 1234567]);
    SurveyQuestion::factory()->create([
        'survey_id' => $survey->id,
        'surveyhero_element_id' => 1387752,
        'surveyhero_question_id' => 1387752,
        'field' => 'question_1',
    ]);
    $response = SurveyResponse::factory()->create(['survey_id' => $survey->id]);
    $mapping = ['question_id' => 1387752, 'type' => 'file_upload', 'field' => 'question_1', 'mapped_data_type' => 'string'];

    $creator = new FileResponseCreator;
    $creator->updateOrCreateQuestionResponse(makeFileApiAnswer(1387752, '/v1/download/element/1387752/response/3875825'), $response, $mapping);
    $creator->updateOrCreateQuestionResponse(makeFileApiAnswer(1387752, '/v1/download/element/1387752/response/3875825'), $response, $mapping);

    expect(SurveyQuestionResponse::count())->toBe(1);
});
