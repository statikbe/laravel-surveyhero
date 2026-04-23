<?php

use Statikbe\Surveyhero\Models\Survey;
use Statikbe\Surveyhero\Models\SurveyAnswer;
use Statikbe\Surveyhero\Models\SurveyQuestion;
use Statikbe\Surveyhero\Services\Factories\QuestionAndAnswerCreator\InputQuestionAndAnswerCreator;

it('creates a survey question without answers for input type', function () {
    $survey = Survey::factory()->create(['surveyhero_id' => 1234567]);
    $creator = new InputQuestionAndAnswerCreator;

    $apiQuestion = (object) [
        'element_id' => 1000005,
        'type' => 'question',
        'question' => (object) [
            'question_id' => 1000005,
            'type' => 'input',
            'question_text' => 'Describe your role',
        ],
    ];

    $creator->updateOrCreateQuestionAndAnswer($apiQuestion, $survey, 'en');

    expect(SurveyQuestion::count())->toBe(1)
        ->and(SurveyAnswer::count())->toBe(0);
});

it('stores the question label translation', function () {
    $survey = Survey::factory()->create(['surveyhero_id' => 1234567]);
    $creator = new InputQuestionAndAnswerCreator;

    $apiQuestion = (object) [
        'element_id' => 1000005,
        'type' => 'question',
        'question' => (object) [
            'question_id' => 1000005,
            'type' => 'input',
            'question_text' => 'Describe your role',
        ],
    ];

    $creator->updateOrCreateQuestionAndAnswer($apiQuestion, $survey, 'en');

    $question = SurveyQuestion::first();
    expect($question->getTranslation('label', 'en'))->toBe('Describe your role');
});
