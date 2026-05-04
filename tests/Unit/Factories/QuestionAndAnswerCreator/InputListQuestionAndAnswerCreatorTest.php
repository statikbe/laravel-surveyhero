<?php

use Statikbe\Surveyhero\Http\DTO\SurveyElementDTO;
use Statikbe\Surveyhero\Models\Survey;
use Statikbe\Surveyhero\Models\SurveyAnswer;
use Statikbe\Surveyhero\Models\SurveyQuestion;
use Statikbe\Surveyhero\Services\Factories\QuestionAndAnswerCreator\InputListQuestionAndAnswerCreator;

beforeEach(function () {
    config()->set('surveyhero.question_mapping', [
        [
            'survey_id'       => 1234567,
            'collectors'      => [9876543],
            'use_resume_link' => false,
            'questions'       => [
                667012 => [
                    'question_id'         => 667012,
                    'type'                => 'input_list',
                    'subquestion_mapping' => [
                        1745983 => ['question_id' => 1745983, 'field' => 'question_1_1'],
                        1745984 => ['question_id' => 1745984, 'field' => 'question_1_2'],
                        1745985 => ['question_id' => 1745985, 'field' => 'question_1_3'],
                    ],
                    'mapped_data_type'    => 'string',
                ],
            ],
        ],
    ]);
});

function makeInputListApiQuestion(int $elementId, string $text, array $inputs, string $acceptsType = 'text'): SurveyElementDTO
{
    return new SurveyElementDTO(
        element_id: $elementId,
        type: 'question',
        question: (object) [
            'question_id'  => $elementId,
            'type'         => 'input_list',
            'question_text' => $text,
            'input_list'   => (object) [
                'accepts' => (object) ['type' => $acceptsType],
                'inputs'  => array_map(fn ($i) => (object) $i, $inputs),
            ],
        ],
    );
}

it('creates one SurveyQuestion per input field with no SurveyAnswer rows', function () {
    $survey = Survey::factory()->create(['surveyhero_id' => 1234567]);
    $creator = new InputListQuestionAndAnswerCreator;

    $apiQuestion = makeInputListApiQuestion(667012, 'Please enter your contact details:', [
        ['input_id' => 1745983, 'label' => 'First and last name'],
        ['input_id' => 1745984, 'label' => 'Street address'],
        ['input_id' => 1745985, 'label' => 'Postal code and city'],
    ]);

    $creator->updateOrCreateQuestionAndAnswer($apiQuestion, $survey, 'en');

    expect(SurveyQuestion::count())->toBe(3)
        ->and(SurveyAnswer::count())->toBe(0);
});

it('stores input labels as question translations', function () {
    $survey = Survey::factory()->create(['surveyhero_id' => 1234567]);
    $creator = new InputListQuestionAndAnswerCreator;

    $apiQuestion = makeInputListApiQuestion(667012, 'Contact details:', [
        ['input_id' => 1745983, 'label' => 'First and last name'],
        ['input_id' => 1745984, 'label' => 'Street address'],
    ]);

    $creator->updateOrCreateQuestionAndAnswer($apiQuestion, $survey, 'en');

    $q1 = SurveyQuestion::where('surveyhero_question_id', 1745983)->first();
    $q2 = SurveyQuestion::where('surveyhero_question_id', 1745984)->first();

    expect($q1->getTranslation('label', 'en'))->toBe('First and last name')
        ->and($q2->getTranslation('label', 'en'))->toBe('Street address');
});

it('is idempotent on re-import', function () {
    $survey = Survey::factory()->create(['surveyhero_id' => 1234567]);
    $creator = new InputListQuestionAndAnswerCreator;

    $apiQuestion = makeInputListApiQuestion(667012, 'Contact details:', [
        ['input_id' => 1745983, 'label' => 'First and last name'],
        ['input_id' => 1745984, 'label' => 'Street address'],
    ]);

    $creator->updateOrCreateQuestionAndAnswer($apiQuestion, $survey, 'en');
    $creator->updateOrCreateQuestionAndAnswer($apiQuestion, $survey, 'en');

    expect(SurveyQuestion::count())->toBe(2)
        ->and(SurveyAnswer::count())->toBe(0);
});
