<?php

use Statikbe\Surveyhero\Http\DTO\SurveyElementDTO;
use Statikbe\Surveyhero\Models\Survey;
use Statikbe\Surveyhero\Models\SurveyAnswer;
use Statikbe\Surveyhero\Models\SurveyQuestion;
use Statikbe\Surveyhero\Services\Factories\QuestionAndAnswerCreator\ImageChoiceListQuestionAndAnswerCreator;

beforeEach(function () {
    config()->set('surveyhero.question_mapping', [
        [
            'survey_id' => 1234567,
            'collectors' => [9876543],
            'use_resume_link' => false,
            'questions' => [
                666941 => [
                    'question_id' => 666941,
                    'type' => 'image_choice_list',
                    'field' => 'question_1',
                    'mapped_data_type' => 'int',
                    'answer_mapping' => [
                        6244 => 1,
                        6245 => 2,
                        6246 => 3,
                    ],
                ],
            ],
        ],
    ]);
});

function makeImageChoiceListApiQuestion(int $elementId, string $text, array $choices): SurveyElementDTO
{
    return new SurveyElementDTO(
        element_id: $elementId,
        type: 'question',
        question: (object) [
            'question_id' => $elementId,
            'type' => 'image_choice_list',
            'question_text' => $text,
            'image_choice_list' => (object) [
                'choices' => array_map(fn ($c) => (object) $c, $choices),
                'settings' => (object) [
                    'allows_multiple_choices' => false,
                ],
            ],
        ],
    );
}

it('creates a survey question and its answers', function () {
    $survey = Survey::factory()->create(['surveyhero_id' => 1234567]);
    $creator = new ImageChoiceListQuestionAndAnswerCreator;

    $apiQuestion = makeImageChoiceListApiQuestion(666941, 'Pick your favourite image:', [
        ['choice_id' => 6244, 'label' => 'Beach', 'image_url' => 'https://example.com/beach.jpg'],
        ['choice_id' => 6245, 'label' => 'Woods', 'image_url' => 'https://example.com/woods.jpg'],
    ]);

    $creator->updateOrCreateQuestionAndAnswer($apiQuestion, $survey, 'en');

    expect(SurveyQuestion::count())->toBe(1)
        ->and(SurveyAnswer::count())->toBe(2);
});

it('stores the question text as a translation', function () {
    $survey = Survey::factory()->create(['surveyhero_id' => 1234567]);
    $creator = new ImageChoiceListQuestionAndAnswerCreator;

    $apiQuestion = makeImageChoiceListApiQuestion(666941, 'Pick your favourite image:', [
        ['choice_id' => 6244, 'label' => 'Beach', 'image_url' => 'https://example.com/beach.jpg'],
    ]);

    $creator->updateOrCreateQuestionAndAnswer($apiQuestion, $survey, 'en');

    $question = SurveyQuestion::first();
    expect($question->getTranslation('label', 'en'))->toBe('Pick your favourite image:');
});

it('stores choice labels and converted int values', function () {
    $survey = Survey::factory()->create(['surveyhero_id' => 1234567]);
    $creator = new ImageChoiceListQuestionAndAnswerCreator;

    $apiQuestion = makeImageChoiceListApiQuestion(666941, 'Pick your favourite image:', [
        ['choice_id' => 6244, 'label' => 'Beach', 'image_url' => 'https://example.com/beach.jpg'],
        ['choice_id' => 6245, 'label' => 'Woods', 'image_url' => 'https://example.com/woods.jpg'],
    ]);

    $creator->updateOrCreateQuestionAndAnswer($apiQuestion, $survey, 'en');

    $answers = SurveyAnswer::all();
    expect($answers)->toHaveCount(2);

    $firstAnswer = $answers->firstWhere('surveyhero_answer_id', 6244);
    expect($firstAnswer->converted_int_value)->toBe(1)
        ->and($firstAnswer->getTranslation('label', 'en'))->toBe('Beach');

    $secondAnswer = $answers->firstWhere('surveyhero_answer_id', 6245);
    expect($secondAnswer->converted_int_value)->toBe(2)
        ->and($secondAnswer->getTranslation('label', 'en'))->toBe('Woods');
});

it('updates existing question and answers on re-import', function () {
    $survey = Survey::factory()->create(['surveyhero_id' => 1234567]);
    $creator = new ImageChoiceListQuestionAndAnswerCreator;
    $apiQuestion = makeImageChoiceListApiQuestion(666941, 'Pick your favourite image:', [
        ['choice_id' => 6244, 'label' => 'Beach', 'image_url' => 'https://example.com/beach.jpg'],
    ]);

    $creator->updateOrCreateQuestionAndAnswer($apiQuestion, $survey, 'en');
    $creator->updateOrCreateQuestionAndAnswer($apiQuestion, $survey, 'en');

    expect(SurveyQuestion::count())->toBe(1)
        ->and(SurveyAnswer::count())->toBe(1);
});
