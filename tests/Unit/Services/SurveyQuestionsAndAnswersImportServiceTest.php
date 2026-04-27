<?php

use Saloon\Http\Faking\MockResponse;
use Statikbe\Surveyhero\Http\Requests\GetSurveyLanguagesRequest;
use Statikbe\Surveyhero\Http\Requests\GetSurveyQuestionsRequest;
use Statikbe\Surveyhero\Http\SurveyheroClient;
use Statikbe\Surveyhero\Models\Survey;
use Statikbe\Surveyhero\Models\SurveyQuestion;
use Statikbe\Surveyhero\Services\SurveyQuestionsAndAnswersImportService;

beforeEach(function () {
    [$apiClient] = $this->makeSurveyheroClient([
        GetSurveyLanguagesRequest::class => MockResponse::fixture('get-survey-languages'),
        GetSurveyQuestionsRequest::class => MockResponse::fixture('get-survey-questions'),
    ]);
    app()->instance(SurveyheroClient::class, $apiClient);
    $this->service = new SurveyQuestionsAndAnswersImportService($apiClient);
});

it('imports questions for each survey language', function () {
    $survey = Survey::factory()->create(['surveyhero_id' => 1234567]);

    $result = $this->service->importSurveyQuestionsAndAnswers($survey);

    expect(SurveyQuestion::count())->toBeGreaterThan(0)
        ->and($result['question'])->toBeEmpty();
});

it('imports choice_list answers for choice questions', function () {
    $survey = Survey::factory()->create(['surveyhero_id' => 1234567]);

    $this->service->importSurveyQuestionsAndAnswers($survey);

    $choiceQuestion = SurveyQuestion::where('surveyhero_element_id', 1000002)->first();
    expect($choiceQuestion)->not->toBeNull()
        ->and($choiceQuestion->surveyAnswers()->count())->toBe(3);
});

it('imports input questions without answers', function () {
    $survey = Survey::factory()->create(['surveyhero_id' => 1234567]);

    $this->service->importSurveyQuestionsAndAnswers($survey);

    $inputQuestion = SurveyQuestion::where('surveyhero_element_id', 1000005)->first();
    expect($inputQuestion)->not->toBeNull()
        ->and($inputQuestion->surveyAnswers()->count())->toBe(0);
});

it('stores question labels with language codes', function () {
    $survey = Survey::factory()->create(['surveyhero_id' => 1234567]);

    $this->service->importSurveyQuestionsAndAnswers($survey);

    $question = SurveyQuestion::where('surveyhero_element_id', 1000002)->first();
    expect($question->getTranslation('label', 'en'))->not->toBeEmpty();
});

it('updates existing questions on re-import', function () {
    $survey = Survey::factory()->create(['surveyhero_id' => 1234567]);

    $this->service->importSurveyQuestionsAndAnswers($survey);
    $questionCountAfterFirst = SurveyQuestion::count();

    usleep(1_100_000);

    $this->service->importSurveyQuestionsAndAnswers($survey);
    expect(SurveyQuestion::count())->toBe($questionCountAfterFirst);
});

it('reports unsupported question types in not-imported list', function () {
    $survey = Survey::factory()->create(['surveyhero_id' => 1234567]);

    [$apiClient] = $this->makeSurveyheroClient([
        GetSurveyLanguagesRequest::class => MockResponse::fixture('get-survey-languages'),
        GetSurveyQuestionsRequest::class => MockResponse::make([
            'elements' => [
                [
                    'element_id' => 9999,
                    'type' => 'question',
                    'question' => [
                        'question_id' => 9999,
                        'type' => 'unsupported_custom_type',
                        'question_text' => 'Unknown question',
                    ],
                ],
            ],
        ], 200),
    ]);
    $service = new SurveyQuestionsAndAnswersImportService($apiClient);

    $result = $service->importSurveyQuestionsAndAnswers($survey);

    expect($result['question'])->not->toBeEmpty()
        ->and($result['question'][0][0])->toBe(9999);
});
