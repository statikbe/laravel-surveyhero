<?php

use Statikbe\Surveyhero\Http\DTO\SurveyElementDTO;
use Statikbe\Surveyhero\Models\Survey;
use Statikbe\Surveyhero\Models\SurveyAnswer;
use Statikbe\Surveyhero\Models\SurveyQuestion;
use Statikbe\Surveyhero\Services\Factories\QuestionAndAnswerCreator\FileUploadQuestionAndAnswerCreator;

it('creates a survey question without answers for file_upload type', function () {
    $survey = Survey::factory()->create(['surveyhero_id' => 1234567]);
    $creator = new FileUploadQuestionAndAnswerCreator;

    $apiQuestion = new SurveyElementDTO(
        element_id: 1000005,
        type: 'question',
        question: (object) [
            'question_id' => 1000005,
            'type' => 'file_upload',
            'question_text' => 'Please upload your image here:',
            'file_upload' => (object) [
                'max_file_size_in_mb' => 25,
                'accepted_file_types' => ['gif', 'jpg', 'jpeg', 'png'],
            ],
        ],
    );

    $creator->updateOrCreateQuestionAndAnswer($apiQuestion, $survey, 'en');

    expect(SurveyQuestion::count())->toBe(1)
        ->and(SurveyAnswer::count())->toBe(0);
});

it('stores the question label as a translation', function () {
    $survey = Survey::factory()->create(['surveyhero_id' => 1234567]);
    $creator = new FileUploadQuestionAndAnswerCreator;

    $apiQuestion = new SurveyElementDTO(
        element_id: 1000005,
        type: 'question',
        question: (object) [
            'question_id' => 1000005,
            'type' => 'file_upload',
            'question_text' => 'Please upload your image here:',
        ],
    );

    $creator->updateOrCreateQuestionAndAnswer($apiQuestion, $survey, 'en');

    $question = SurveyQuestion::first();
    expect($question->getTranslation('label', 'en'))->toBe('Please upload your image here:');
});

it('is idempotent on re-import', function () {
    $survey = Survey::factory()->create(['surveyhero_id' => 1234567]);
    $creator = new FileUploadQuestionAndAnswerCreator;

    $apiQuestion = new SurveyElementDTO(
        element_id: 1000005,
        type: 'question',
        question: (object) [
            'question_id' => 1000005,
            'type' => 'file_upload',
            'question_text' => 'Please upload your image here:',
        ],
    );

    $creator->updateOrCreateQuestionAndAnswer($apiQuestion, $survey, 'en');
    $creator->updateOrCreateQuestionAndAnswer($apiQuestion, $survey, 'en');

    expect(SurveyQuestion::count())->toBe(1)
        ->and(SurveyAnswer::count())->toBe(0);
});
