<?php

use Statikbe\Surveyhero\Exceptions\AnswerNotImportedException;
use Statikbe\Surveyhero\Models\Survey;
use Statikbe\Surveyhero\Models\SurveyAnswer;
use Statikbe\Surveyhero\Models\SurveyQuestion;
use Statikbe\Surveyhero\Models\SurveyQuestionResponse;
use Statikbe\Surveyhero\Models\SurveyResponse;
use Statikbe\Surveyhero\Services\Factories\ResponseCreator\ChoicesResponseCreator;

function makeChoicesApiAnswer(int $elementId, array $choices): stdClass
{
    return (object) [
        'element_id' => $elementId,
        'type' => 'choices',
        'choices' => array_map(fn ($c) => (object) $c, $choices),
    ];
}

it('creates a question response for a single choice answer', function () {
    $survey = Survey::factory()->create();
    $question = SurveyQuestion::factory()->create([
        'survey_id' => $survey->id,
        'surveyhero_element_id' => 1000002,
        'surveyhero_question_id' => 1000002,
    ]);
    SurveyAnswer::factory()->create([
        'survey_question_id' => $question->id,
        'surveyhero_answer_id' => 13509166,
        'converted_int_value' => 1,
    ]);
    $response = SurveyResponse::factory()->create(['survey_id' => $survey->id]);

    $apiAnswer = makeChoicesApiAnswer(1000002, [
        ['choice_id' => 13509166, 'label' => 'Department A'],
    ]);
    $mapping = [
        'question_id' => 1000002,
        'type' => 'choices',
        'field' => 'question_2',
        'answer_mapping' => [13509166 => 1],
        'mapped_data_type' => 'int',
    ];

    $creator = new ChoicesResponseCreator;
    $result = $creator->updateOrCreateQuestionResponse($apiAnswer, $response, $mapping);

    expect(SurveyQuestionResponse::count())->toBe(1)
        ->and($result)->toBeArray()->toHaveCount(1);
});

it('creates multiple question responses for multi-choice answers', function () {
    $survey = Survey::factory()->create();
    $question = SurveyQuestion::factory()->create([
        'survey_id' => $survey->id,
        'surveyhero_element_id' => 1000002,
        'surveyhero_question_id' => 1000002,
    ]);
    SurveyAnswer::factory()->create(['survey_question_id' => $question->id, 'surveyhero_answer_id' => 13509166]);
    SurveyAnswer::factory()->create(['survey_question_id' => $question->id, 'surveyhero_answer_id' => 13509167]);
    $response = SurveyResponse::factory()->create(['survey_id' => $survey->id]);

    $apiAnswer = makeChoicesApiAnswer(1000002, [
        ['choice_id' => 13509166, 'label' => 'A'],
        ['choice_id' => 13509167, 'label' => 'B'],
    ]);
    $mapping = ['question_id' => 1000002, 'type' => 'choices', 'field' => 'question_2', 'mapped_data_type' => 'int'];

    $creator = new ChoicesResponseCreator;
    $creator->updateOrCreateQuestionResponse($apiAnswer, $response, $mapping);

    expect(SurveyQuestionResponse::count())->toBe(2);
});

it('throws AnswerNotImportedException when answer is not in the database', function () {
    $survey = Survey::factory()->create();
    $question = SurveyQuestion::factory()->create([
        'survey_id' => $survey->id,
        'surveyhero_element_id' => 1000002,
        'surveyhero_question_id' => 1000002,
    ]);
    // No answer record created
    $response = SurveyResponse::factory()->create(['survey_id' => $survey->id]);

    $apiAnswer = makeChoicesApiAnswer(1000002, [
        ['choice_id' => 13509166, 'label' => 'Department A'],
    ]);
    $mapping = ['question_id' => 1000002, 'type' => 'choices', 'field' => 'question_2', 'mapped_data_type' => 'int'];

    $creator = new ChoicesResponseCreator;
    $creator->updateOrCreateQuestionResponse($apiAnswer, $response, $mapping);
})->throws(AnswerNotImportedException::class);
