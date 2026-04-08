<?php

use Illuminate\Support\Facades\Event;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Statikbe\Surveyhero\Events\SurveyResponseImported;
use Statikbe\Surveyhero\Events\SurveyResponseIncompletelyImported;
use Statikbe\Surveyhero\Http\Connector\SurveyheroConnector;
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

function makeSurveyResponseImportService(array $mockResponses): SurveyResponseImportService
{
    $mockClient = new MockClient($mockResponses);
    $connector = new SurveyheroConnector;
    $connector->withMockClient($mockClient);
    $apiClient = new SurveyheroClient($connector);

    app()->instance(SurveyheroClient::class, $apiClient);

    return new SurveyResponseImportService($apiClient, new SurveyMappingService($apiClient));
}

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
    $service = makeSurveyResponseImportService([
        GetSurveyResponsesRequest::class => MockResponse::fixture('get-survey-responses'),
        GetSurveyResponseAnswersRequest::class => MockResponse::fixture('get-survey-response-answers'),
    ]);

    $info = $service->importSurveyResponses($survey);

    expect(SurveyResponse::count())->toBe(1)
        ->and($info->getTotalResponsesImported())->toBe(1);

    $response = SurveyResponse::first();
    expect($response->surveyhero_id)->toBe(9001)
        ->and($response->survey_completed)->toBeTruthy()
        ->and($response->survey_language)->toBe('en');
});

it('creates question responses for each mapped answer', function () {
    $survey = createSurveyWithQuestions();
    $service = makeSurveyResponseImportService([
        GetSurveyResponsesRequest::class => MockResponse::fixture('get-survey-responses'),
        GetSurveyResponseAnswersRequest::class => MockResponse::fixture('get-survey-response-answers'),
    ]);

    $service->importSurveyResponses($survey);

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

    $service = makeSurveyResponseImportService([
        GetSurveyResponsesRequest::class => MockResponse::fixture('get-survey-responses'),
        GetSurveyResponseAnswersRequest::class => MockResponse::fixture('get-survey-response-answers'),
    ]);

    $info = $service->importSurveyResponses($survey);

    // 0 new responses imported (already done)
    expect($info->getTotalResponsesImported())->toBe(0);
    expect(SurveyResponse::count())->toBe(1);
});

it('dispatches SurveyResponseImported event for completed response', function () {
    Event::fake([SurveyResponseImported::class, SurveyResponseIncompletelyImported::class]);

    $survey = createSurveyWithQuestions();
    $service = makeSurveyResponseImportService([
        GetSurveyResponsesRequest::class => MockResponse::fixture('get-survey-responses'),
        GetSurveyResponseAnswersRequest::class => MockResponse::fixture('get-survey-response-answers'),
    ]);

    $service->importSurveyResponses($survey);

    Event::assertDispatched(SurveyResponseImported::class);
    Event::assertNotDispatched(SurveyResponseIncompletelyImported::class);
});

it('dispatches SurveyResponseIncompletelyImported when a question is not imported', function () {
    Event::fake([SurveyResponseImported::class, SurveyResponseIncompletelyImported::class]);

    // Survey with no questions imported → all answers will have unimported questions
    $survey = Survey::factory()->create(['surveyhero_id' => 1234567]);

    $service = makeSurveyResponseImportService([
        GetSurveyResponsesRequest::class => MockResponse::fixture('get-survey-responses'),
        GetSurveyResponseAnswersRequest::class => MockResponse::fixture('get-survey-response-answers'),
    ]);

    $service->importSurveyResponses($survey);

    Event::assertDispatched(SurveyResponseIncompletelyImported::class);
});

it('updates the survey_last_imported timestamp after import', function () {
    $survey = createSurveyWithQuestions();
    expect($survey->survey_last_imported)->toBeNull();

    $service = makeSurveyResponseImportService([
        GetSurveyResponsesRequest::class => MockResponse::fixture('get-survey-responses'),
        GetSurveyResponseAnswersRequest::class => MockResponse::fixture('get-survey-response-answers'),
    ]);

    $service->importSurveyResponses($survey);

    $survey->refresh();
    expect($survey->survey_last_imported)->not->toBeNull();
});
