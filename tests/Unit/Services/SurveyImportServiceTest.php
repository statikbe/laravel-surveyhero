<?php

use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Statikbe\Surveyhero\Http\Connector\SurveyheroConnector;
use Statikbe\Surveyhero\Http\Requests\GetSurveysRequest;
use Statikbe\Surveyhero\Http\SurveyheroClient;
use Statikbe\Surveyhero\Models\Survey;
use Statikbe\Surveyhero\Services\SurveyImportService;

function makeSurveyImportService(array $mockResponses): SurveyImportService
{
    $mockClient = new MockClient($mockResponses);
    $connector = new SurveyheroConnector;
    $connector->withMockClient($mockClient);
    $apiClient = new SurveyheroClient($connector);

    app()->instance(SurveyheroClient::class, $apiClient);

    return new SurveyImportService($apiClient);
}

it('imports all surveys from the API', function () {
    $service = makeSurveyImportService([
        GetSurveysRequest::class => MockResponse::fixture('get-surveys'),
    ]);

    $result = $service->importSurveys(null);

    expect($result['imported'])->toContain(1234567)
        ->and($result['notImported'])->toBeEmpty();

    expect(Survey::where('surveyhero_id', 1234567)->exists())->toBeTrue();
});

it('stores the survey name from the API', function () {
    $service = makeSurveyImportService([
        GetSurveysRequest::class => MockResponse::fixture('get-surveys'),
    ]);

    $service->importSurveys(null);

    $survey = Survey::where('surveyhero_id', 1234567)->first();
    expect($survey->name)->toBe('Test Survey');
});

it('sets use_resume_link from config', function () {
    $service = makeSurveyImportService([
        GetSurveysRequest::class => MockResponse::fixture('get-surveys'),
    ]);

    $service->importSurveys(null);

    $survey = Survey::where('surveyhero_id', 1234567)->first();
    expect($survey->use_resume_link)->toBeFalsy();
});

it('updates an existing survey on re-import', function () {
    Survey::factory()->create(['surveyhero_id' => 1234567, 'name' => 'Old Name']);

    $service = makeSurveyImportService([
        GetSurveysRequest::class => MockResponse::fixture('get-surveys'),
    ]);

    $service->importSurveys(null);

    expect(Survey::where('surveyhero_id', 1234567)->count())->toBe(1)
        ->and(Survey::where('surveyhero_id', 1234567)->first()->name)->toBe('Test Survey');
});

it('only imports specified surveys when IDs are provided', function () {
    $service = makeSurveyImportService([
        GetSurveysRequest::class => MockResponse::fixture('get-surveys'),
    ]);

    $result = $service->importSurveys(collect([1234567]));

    expect($result['imported'])->toContain(1234567);
});

it('skips surveys not in the provided list', function () {
    $service = makeSurveyImportService([
        GetSurveysRequest::class => MockResponse::fixture('get-surveys'),
    ]);

    $result = $service->importSurveys(collect([9999999]));

    expect($result['imported'])->toBeEmpty();
    expect(Survey::count())->toBe(0);
});
