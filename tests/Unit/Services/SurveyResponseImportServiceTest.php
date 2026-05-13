<?php

use Illuminate\Support\Facades\Event;
use Saloon\Http\Faking\MockResponse;
use Statikbe\Surveyhero\Events\SurveyResponseImported;
use Statikbe\Surveyhero\Events\SurveyResponseIncompletelyImported;
use Statikbe\Surveyhero\Http\Requests\GetSurveyResponseAnswersRequest;
use Statikbe\Surveyhero\Http\Requests\GetSurveyResponsesRequest;
use Statikbe\Surveyhero\Http\SurveyheroClient;
use Statikbe\Surveyhero\Models\Survey;
use Statikbe\Surveyhero\Models\SurveyAnswer;
use Statikbe\Surveyhero\Models\SurveyQuestion;
use Statikbe\Surveyhero\Models\SurveyQuestionResponse;
use Statikbe\Surveyhero\Models\SurveyResponse;
use Statikbe\Surveyhero\Services\SurveyMappingService;
use Statikbe\Surveyhero\Services\SurveyResponseImportService;
use Statikbe\Surveyhero\SurveyheroConfig;

beforeEach(function () {
    [$apiClient] = $this->makeSurveyheroClient([
        GetSurveyResponsesRequest::class => MockResponse::fixture('get-survey-responses'),
        GetSurveyResponseAnswersRequest::class => MockResponse::fixture('get-survey-response-answers'),
    ]);
    app()->instance(SurveyheroClient::class, $apiClient);
    $this->service = new SurveyResponseImportService($apiClient, new SurveyMappingService($apiClient));
});

function createSurveyWithQuestions(): Survey
{
    $survey = Survey::factory()->create(['surveyhero_id' => 1234567]);

    // choice question
    $choiceQuestion = SurveyQuestion::factory()->create([
        'survey_id' => $survey->id,
        'surveyhero_element_id' => 1000002,
        'surveyhero_question_id' => 1000002,
        'field' => 'question_2',
    ]);
    SurveyAnswer::factory()->create([
        'survey_question_id' => $choiceQuestion->id,
        'surveyhero_answer_id' => 13509166,
        'converted_int_value' => 1,
        'label' => ['en' => 'Department A'],
    ]);

    // input questions
    SurveyQuestion::factory()->create([
        'survey_id' => $survey->id,
        'surveyhero_element_id' => 1000005,
        'surveyhero_question_id' => 1000005,
        'field' => 'question_5',
    ]);
    SurveyQuestion::factory()->create([
        'survey_id' => $survey->id,
        'surveyhero_element_id' => 1000006,
        'surveyhero_question_id' => 1000006,
        'field' => 'question_6',
    ]);

    return $survey;
}

it('imports a completed response and creates a SurveyResponse record', function () {
    $survey = createSurveyWithQuestions();

    $info = $this->service->importSurveyResponses($survey);

    expect(SurveyResponse::count())->toBe(1)
        ->and($info->getTotalResponsesImported())->toBe(1);

    $response = SurveyResponse::first();
    expect($response->surveyhero_id)->toBe(9001)
        ->and($response->survey_completed)->toBeTruthy()
        ->and($response->survey_language)->toBe('en');
});

it('creates question responses for each mapped answer', function () {
    $survey = createSurveyWithQuestions();

    $this->service->importSurveyResponses($survey);

    // 3 answers in the fixture: 1 choices + 1 text + 1 number
    expect(SurveyQuestionResponse::count())->toBe(3);
});

it('skips an already-completed response on re-import', function () {
    $survey = createSurveyWithQuestions();
    SurveyResponse::factory()->create([
        'survey_id' => $survey->id,
        'surveyhero_id' => 9001,
        'survey_completed' => true,
    ]);

    $info = $this->service->importSurveyResponses($survey);

    // 0 new responses imported (already done)
    expect($info->getTotalResponsesImported())->toBe(0);
    expect(SurveyResponse::count())->toBe(1);
});

it('dispatches SurveyResponseImported event for completed response', function () {
    Event::fake([SurveyResponseImported::class, SurveyResponseIncompletelyImported::class]);

    $survey = createSurveyWithQuestions();

    $this->service->importSurveyResponses($survey);

    Event::assertDispatched(SurveyResponseImported::class);
    Event::assertNotDispatched(SurveyResponseIncompletelyImported::class);
});

it('dispatches SurveyResponseIncompletelyImported when a question is not imported', function () {
    Event::fake([SurveyResponseImported::class, SurveyResponseIncompletelyImported::class]);

    // Survey with no questions imported → all answers will have unimported questions
    $survey = Survey::factory()->create(['surveyhero_id' => 1234567]);

    $this->service->importSurveyResponses($survey);

    Event::assertDispatched(SurveyResponseIncompletelyImported::class);
});

it('updates the survey_last_imported timestamp after import', function () {
    $survey = createSurveyWithQuestions();
    expect($survey->survey_last_imported)->toBeNull();

    $this->service->importSurveyResponses($survey);

    $survey->refresh();
    expect($survey->survey_last_imported)->not->toBeNull();
});

it('imports image_choice_list response and creates a question response', function () {
    [$apiClient] = $this->makeSurveyheroClient([
        GetSurveyResponseAnswersRequest::class => MockResponse::fixture('get-survey-response-image-choice-list'),
    ]);
    $service = new SurveyResponseImportService($apiClient, new SurveyMappingService($apiClient));

    $survey = Survey::factory()->create(['surveyhero_id' => 1234567]);
    $question = SurveyQuestion::factory()->create([
        'survey_id' => $survey->id,
        'surveyhero_element_id' => 555001,
        'surveyhero_question_id' => 555001,
        'field' => 'question_1',
    ]);
    SurveyAnswer::factory()->create([
        'survey_question_id' => $question->id,
        'surveyhero_answer_id' => 13509100,
        'converted_int_value' => 1,
        'label' => ['en' => 'Option A'],
    ]);

    $service->importSurveyResponse(9003, $survey, [
        ['question_id' => 555001, 'type' => 'choices', 'field' => 'question_1', 'mapped_data_type' => 'int', 'answer_mapping' => [13509100 => 1, 13509101 => 2]],
    ]);

    expect(SurveyQuestionResponse::count())->toBe(1);
    expect(SurveyResponse::where('surveyhero_id', 9003)->exists())->toBeTrue();
});

it('imports file_upload response and stores the full file URL', function () {
    [$apiClient] = $this->makeSurveyheroClient([
        GetSurveyResponseAnswersRequest::class => MockResponse::fixture('get-survey-response-file-upload'),
    ]);
    $service = new SurveyResponseImportService($apiClient, new SurveyMappingService($apiClient));

    $survey = Survey::factory()->create(['surveyhero_id' => 1234567]);
    SurveyQuestion::factory()->create([
        'survey_id' => $survey->id,
        'surveyhero_element_id' => 1387752,
        'surveyhero_question_id' => 1387752,
        'field' => 'question_1',
    ]);

    $service->importSurveyResponse(3875825, $survey, [
        ['question_id' => 1387752, 'type' => 'file_upload', 'field' => 'question_1', 'mapped_data_type' => 'string'],
    ]);

    expect(SurveyQuestionResponse::count())->toBe(1);

    $storedAnswer = SurveyAnswer::first();
    $apiUrl = (new SurveyheroConfig)->getApiUrl();
    $expectedHost = parse_url($apiUrl, PHP_URL_SCHEME).'://'.parse_url($apiUrl, PHP_URL_HOST);
    expect($storedAnswer->converted_string_value)->toStartWith($expectedHost);
    expect($storedAnswer->converted_string_value)->toContain('/v1/download/element/1387752/response/3875825');
});

it('imports input_list response as separate question responses per input', function () {
    [$apiClient] = $this->makeSurveyheroClient([
        GetSurveyResponseAnswersRequest::class => MockResponse::fixture('get-survey-response-input-list'),
    ]);
    $service = new SurveyResponseImportService($apiClient, new SurveyMappingService($apiClient));

    $survey = Survey::factory()->create(['surveyhero_id' => 1234567]);
    SurveyQuestion::factory()->create([
        'survey_id' => $survey->id,
        'surveyhero_element_id' => 667012,
        'surveyhero_question_id' => 1745983,
        'field' => 'question_1_1',
    ]);
    SurveyQuestion::factory()->create([
        'survey_id' => $survey->id,
        'surveyhero_element_id' => 667012,
        'surveyhero_question_id' => 1745985,
        'field' => 'question_1_2',
    ]);

    $service->importSurveyResponse(9002, $survey, [
        [
            'question_id' => 667012,
            'type' => 'input_list',
            'mapped_data_type' => 'string',
            'subquestion_mapping' => [
                1745983 => ['question_id' => 1745983, 'field' => 'question_1_1'],
                1745985 => ['question_id' => 1745985, 'field' => 'question_1_2'],
            ],
        ],
    ]);

    // fixture has 2 inputs → 2 question responses
    expect(SurveyQuestionResponse::count())->toBe(2);
    expect(SurveyResponse::where('surveyhero_id', 9002)->exists())->toBeTrue();
});
