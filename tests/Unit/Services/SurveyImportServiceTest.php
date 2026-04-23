<?php

use Saloon\Http\Faking\MockResponse;
use Statikbe\Surveyhero\Http\Requests\GetSurveysRequest;
use Statikbe\Surveyhero\Http\SurveyheroClient;
use Statikbe\Surveyhero\Models\Survey;
use Statikbe\Surveyhero\Services\SurveyImportService;

beforeEach(function () {
    [$apiClient] = $this->makeSurveyheroClient([
        GetSurveysRequest::class => MockResponse::fixture('get-surveys'),
    ]);
    app()->instance(SurveyheroClient::class, $apiClient);
    $this->service = new SurveyImportService($apiClient);
});

it('imports all surveys from the API', function () {
    $result = $this->service->importSurveys(null);

    expect($result['imported'])->toContain(1234567)
        ->and($result['notImported'])->toBeEmpty();

    expect(Survey::where('surveyhero_id', 1234567)->exists())->toBeTrue();
});

it('stores the survey name from the API', function () {
    $this->service->importSurveys(null);

    $survey = Survey::where('surveyhero_id', 1234567)->first();
    expect($survey->name)->toBe('Test Survey');
});

it('sets use_resume_link from config', function () {
    $this->service->importSurveys(null);

    $survey = Survey::where('surveyhero_id', 1234567)->first();
    expect($survey->use_resume_link)->toBeFalsy();
});

it('updates an existing survey on re-import', function () {
    Survey::factory()->create(['surveyhero_id' => 1234567, 'name' => 'Old Name']);

    $this->service->importSurveys(null);

    expect(Survey::where('surveyhero_id', 1234567)->count())->toBe(1)
        ->and(Survey::where('surveyhero_id', 1234567)->first()->name)->toBe('Test Survey');
});

it('only imports specified surveys when IDs are provided', function () {
    $result = $this->service->importSurveys(collect([1234567]));

    expect($result['imported'])->toContain(1234567);
});

it('skips surveys not in the provided list', function () {
    $result = $this->service->importSurveys(collect([9999999]));

    expect($result['imported'])->toBeEmpty();
    expect(Survey::count())->toBe(0);
});
