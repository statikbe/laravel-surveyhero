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

    $this->service->importSurveyQuestionsAndAnswers($survey);
    expect(SurveyQuestion::count())->toBe($questionCountAfterFirst);
});

it('imports image_choice_list question and creates answer choices', function () {
    $survey = Survey::factory()->create([
        'surveyhero_id' => 1234567,
        'question_mapping' => [
            555001 => ['question_id' => 555001, 'type' => 'image_choice_list', 'field' => 'question_1', 'mapped_data_type' => 'int', 'answer_mapping' => [13509100 => 1, 13509101 => 2]],
        ],
    ]);

    [$apiClient] = $this->makeSurveyheroClient([
        GetSurveyLanguagesRequest::class => MockResponse::fixture('get-survey-languages'),
        GetSurveyQuestionsRequest::class => MockResponse::fixture('get-survey-questions-image-choice-list'),
    ]);
    $service = new SurveyQuestionsAndAnswersImportService($apiClient);

    $service->importSurveyQuestionsAndAnswers($survey);

    $question = SurveyQuestion::where('surveyhero_element_id', 555001)->first();
    expect($question)->not->toBeNull()
        ->and($question->surveyAnswers()->count())->toBe(2);
});

it('imports file_upload question without answers', function () {
    $survey = Survey::factory()->create([
        'surveyhero_id' => 1234567,
        'question_mapping' => [
            1387752 => ['question_id' => 1387752, 'type' => 'file_upload', 'field' => 'question_1', 'mapped_data_type' => 'string'],
        ],
    ]);

    [$apiClient] = $this->makeSurveyheroClient([
        GetSurveyLanguagesRequest::class => MockResponse::fixture('get-survey-languages'),
        GetSurveyQuestionsRequest::class => MockResponse::fixture('get-survey-questions-file-upload'),
    ]);
    $service = new SurveyQuestionsAndAnswersImportService($apiClient);

    $service->importSurveyQuestionsAndAnswers($survey);

    $question = SurveyQuestion::where('surveyhero_element_id', 1387752)->first();
    expect($question)->not->toBeNull()
        ->and($question->surveyAnswers()->count())->toBe(0);
});

it('imports input_list question as separate SurveyQuestion rows per input', function () {
    $survey = Survey::factory()->create([
        'surveyhero_id' => 1234567,
        'question_mapping' => [
            667012 => [
                'question_id' => 667012,
                'type' => 'input_list',
                'mapped_data_type' => 'string',
                'subquestion_mapping' => [
                    1745983 => ['question_id' => 1745983, 'field' => 'question_1_1'],
                    1745984 => ['question_id' => 1745984, 'field' => 'question_1_2'],
                    1745985 => ['question_id' => 1745985, 'field' => 'question_1_3'],
                ],
            ],
        ],
    ]);

    [$apiClient] = $this->makeSurveyheroClient([
        GetSurveyLanguagesRequest::class => MockResponse::fixture('get-survey-languages'),
        GetSurveyQuestionsRequest::class => MockResponse::fixture('get-survey-questions-input-list'),
    ]);
    $service = new SurveyQuestionsAndAnswersImportService($apiClient);

    $service->importSurveyQuestionsAndAnswers($survey);

    // fixture has 3 inputs → 3 SurveyQuestion rows, each keyed by input_id
    expect(SurveyQuestion::where('surveyhero_element_id', 667012)->count())->toBe(3);
    expect(SurveyQuestion::where('surveyhero_question_id', 1745983)->exists())->toBeTrue();
    expect(SurveyQuestion::where('surveyhero_question_id', 1745984)->exists())->toBeTrue();
    expect(SurveyQuestion::where('surveyhero_question_id', 1745985)->exists())->toBeTrue();
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
