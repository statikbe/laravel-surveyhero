<?php

use Statikbe\Surveyhero\Models\Survey;
use Statikbe\Surveyhero\Models\SurveyQuestion;
use Statikbe\Surveyhero\Models\SurveyQuestionResponse;
use Statikbe\Surveyhero\Models\SurveyResponse;
use Statikbe\Surveyhero\Services\Factories\ResponseCreator\InputsResponseCreator;

function makeInputsApiAnswer(int $elementId, array $inputs): stdClass
{
    return (object) [
        'element_id' => $elementId,
        'type'       => 'inputs',
        'inputs'     => array_map(fn ($i) => (object) [
            'input_id' => $i['input_id'],
            'label'    => $i['label'],
            'answer'   => (object) $i['answer'],
        ], $inputs),
    ];
}

function makeInputsMapping(int $elementId, array $subquestionMapping, string $dataType = 'string'): array
{
    return [
        'question_id'         => $elementId,
        'type'                => 'input_list',
        'subquestion_mapping' => $subquestionMapping,
        'mapped_data_type'    => $dataType,
    ];
}

it('stores converted_string_value for each answered text input', function () {
    $survey = Survey::factory()->create(['surveyhero_id' => 1234567]);
    SurveyQuestion::factory()->create([
        'survey_id'               => $survey->id,
        'surveyhero_element_id'   => 667012,
        'surveyhero_question_id'  => 1745983,
        'field'                   => 'question_1_1',
    ]);
    SurveyQuestion::factory()->create([
        'survey_id'               => $survey->id,
        'surveyhero_element_id'   => 667012,
        'surveyhero_question_id'  => 1745984,
        'field'                   => 'question_1_2',
    ]);
    $response = SurveyResponse::factory()->create(['survey_id' => $survey->id]);
    $mapping = makeInputsMapping(667012, [
        1745983 => ['question_id' => 1745983, 'field' => 'question_1_1'],
        1745984 => ['question_id' => 1745984, 'field' => 'question_1_2'],
    ]);

    $creator = new InputsResponseCreator;
    $creator->updateOrCreateQuestionResponse(
        makeInputsApiAnswer(667012, [
            ['input_id' => 1745983, 'label' => 'First and last name', 'answer' => ['type' => 'text', 'text' => 'John Smith']],
            ['input_id' => 1745984, 'label' => 'Street address', 'answer' => ['type' => 'text', 'text' => 'Main Street 1']],
        ]),
        $response,
        $mapping
    );

    expect(SurveyQuestionResponse::count())->toBe(2);
    $r1 = SurveyQuestionResponse::whereHas('surveyQuestion', fn ($q) => $q->where('surveyhero_question_id', 1745983))->first();
    expect($r1->surveyAnswer->converted_string_value)->toBe('John Smith');
});

it('stores converted_int_value for number inputs', function () {
    $survey = Survey::factory()->create(['surveyhero_id' => 1234567]);
    SurveyQuestion::factory()->create([
        'survey_id'              => $survey->id,
        'surveyhero_element_id'  => 667012,
        'surveyhero_question_id' => 1745983,
        'field'                  => 'question_1_1',
    ]);
    $response = SurveyResponse::factory()->create(['survey_id' => $survey->id]);
    $mapping = makeInputsMapping(667012, [
        1745983 => ['question_id' => 1745983, 'field' => 'question_1_1'],
    ], 'int');

    $creator = new InputsResponseCreator;
    $creator->updateOrCreateQuestionResponse(
        makeInputsApiAnswer(667012, [
            ['input_id' => 1745983, 'label' => 'Age', 'answer' => ['type' => 'number', 'number' => 42]],
        ]),
        $response,
        $mapping
    );

    $r = SurveyQuestionResponse::first();
    expect($r->surveyAnswer->converted_int_value)->toBe(42);
});

it('produces no response row for unanswered inputs absent from the response', function () {
    $survey = Survey::factory()->create(['surveyhero_id' => 1234567]);
    SurveyQuestion::factory()->create([
        'survey_id'              => $survey->id,
        'surveyhero_element_id'  => 667012,
        'surveyhero_question_id' => 1745983,
        'field'                  => 'question_1_1',
    ]);
    $response = SurveyResponse::factory()->create(['survey_id' => $survey->id]);
    $mapping = makeInputsMapping(667012, [
        1745983 => ['question_id' => 1745983, 'field' => 'question_1_1'],
        1745984 => ['question_id' => 1745984, 'field' => 'question_1_2'],
    ]);

    $creator = new InputsResponseCreator;
    // Only input 1745983 answered; 1745984 is absent
    $creator->updateOrCreateQuestionResponse(
        makeInputsApiAnswer(667012, [
            ['input_id' => 1745983, 'label' => 'Name', 'answer' => ['type' => 'text', 'text' => 'Jane']],
        ]),
        $response,
        $mapping
    );

    expect(SurveyQuestionResponse::count())->toBe(1);
});

it('updates the existing response on re-import', function () {
    $survey = Survey::factory()->create(['surveyhero_id' => 1234567]);
    SurveyQuestion::factory()->create([
        'survey_id'              => $survey->id,
        'surveyhero_element_id'  => 667012,
        'surveyhero_question_id' => 1745983,
        'field'                  => 'question_1_1',
    ]);
    $response = SurveyResponse::factory()->create(['survey_id' => $survey->id]);
    $mapping = makeInputsMapping(667012, [
        1745983 => ['question_id' => 1745983, 'field' => 'question_1_1'],
    ]);
    $apiAnswer = makeInputsApiAnswer(667012, [
        ['input_id' => 1745983, 'label' => 'Name', 'answer' => ['type' => 'text', 'text' => 'John']],
    ]);

    $creator = new InputsResponseCreator;
    $creator->updateOrCreateQuestionResponse($apiAnswer, $response, $mapping);
    $creator->updateOrCreateQuestionResponse($apiAnswer, $response, $mapping);

    expect(SurveyQuestionResponse::count())->toBe(1);
});
