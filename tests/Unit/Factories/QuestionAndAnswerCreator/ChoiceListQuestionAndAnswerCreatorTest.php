<?php

use Statikbe\Surveyhero\Http\DTO\SurveyElementDTO;
use Statikbe\Surveyhero\Models\Survey;
use Statikbe\Surveyhero\Models\SurveyAnswer;
use Statikbe\Surveyhero\Models\SurveyQuestion;
use Statikbe\Surveyhero\Services\Factories\QuestionAndAnswerCreator\ChoiceListQuestionAndAnswerCreator;

function makeChoiceListApiQuestion(int $elementId, string $text, array $choices): SurveyElementDTO
{
    return new SurveyElementDTO(
        element_id: $elementId,
        type: 'question',
        question: (object) [
            'question_id' => $elementId,
            'type' => 'choice_list',
            'question_text' => $text,
            'choice_list' => (object) [
                'choices' => array_map(fn ($c) => (object) $c, $choices),
            ],
        ],
    );
}

it('creates a survey question and its answers', function () {
    $survey = Survey::factory()->create(['surveyhero_id' => 1234567]);
    $creator = new ChoiceListQuestionAndAnswerCreator;

    $apiQuestion = makeChoiceListApiQuestion(1000002, 'In which department?', [
        ['choice_id' => 13509166, 'label' => 'Department A'],
        ['choice_id' => 13509167, 'label' => 'Department B'],
        ['choice_id' => 13509168, 'label' => 'Department C'],
    ]);

    $creator->updateOrCreateQuestionAndAnswer($apiQuestion, $survey, 'en');

    expect(SurveyQuestion::count())->toBe(1)
        ->and(SurveyAnswer::count())->toBe(3);
});

it('stores the question text as a translation', function () {
    $survey = Survey::factory()->create(['surveyhero_id' => 1234567]);
    $creator = new ChoiceListQuestionAndAnswerCreator;

    $apiQuestion = makeChoiceListApiQuestion(1000002, 'In which department?', [
        ['choice_id' => 13509166, 'label' => 'Department A'],
    ]);

    $creator->updateOrCreateQuestionAndAnswer($apiQuestion, $survey, 'en');

    $question = SurveyQuestion::first();
    expect($question->getTranslation('label', 'en'))->toBe('In which department?');
});

it('stores choice labels and converted values', function () {
    $survey = Survey::factory()->create(['surveyhero_id' => 1234567]);
    $creator = new ChoiceListQuestionAndAnswerCreator;

    $apiQuestion = makeChoiceListApiQuestion(1000002, 'Department?', [
        ['choice_id' => 13509166, 'label' => 'Department A'],
        ['choice_id' => 13509167, 'label' => 'Department B'],
        ['choice_id' => 13509168, 'label' => 'Department C'],
    ]);

    $creator->updateOrCreateQuestionAndAnswer($apiQuestion, $survey, 'en');

    $answers = SurveyAnswer::all();
    expect($answers)->toHaveCount(3);

    $firstAnswer = $answers->firstWhere('surveyhero_answer_id', 13509166);
    expect($firstAnswer->converted_int_value)->toBe(1)
        ->and($firstAnswer->getTranslation('label', 'en'))->toBe('Department A');
});

it('updates existing answers on re-import', function () {
    $survey = Survey::factory()->create(['surveyhero_id' => 1234567]);
    $creator = new ChoiceListQuestionAndAnswerCreator;
    $apiQuestion = makeChoiceListApiQuestion(1000002, 'Department?', [
        ['choice_id' => 13509166, 'label' => 'Department A'],
    ]);

    $creator->updateOrCreateQuestionAndAnswer($apiQuestion, $survey, 'en');
    $creator->updateOrCreateQuestionAndAnswer($apiQuestion, $survey, 'en');

    expect(SurveyQuestion::count())->toBe(1)
        ->and(SurveyAnswer::count())->toBe(1);
});
