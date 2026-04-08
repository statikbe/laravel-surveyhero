<?php

use Carbon\Carbon;
use Illuminate\Support\Facades\Event;
use Statikbe\Surveyhero\Contracts\SurveyContract;
use Statikbe\Surveyhero\Events\SurveyResponseIncompletelyImported;
use Statikbe\Surveyhero\Http\DTO\SurveyResponseAnswersDTO;
use Statikbe\Surveyhero\Http\SurveyheroClient;
use Statikbe\Surveyhero\Models\Survey;
use Statikbe\Surveyhero\Models\SurveyResponse;
use Statikbe\Surveyhero\Services\SurveyMappingService;
use Statikbe\Surveyhero\Services\SurveyResponseImportService;

it('marks response incomplete when answer has no question mapping', function () {
    Event::fake();

    $survey = Survey::factory()->create([
        'surveyhero_id' => 12345,
        'survey_last_imported' => null,
        'question_mapping' => [],
        'collector_ids' => [1],
    ]);

    $responseAnswersDto = new SurveyResponseAnswersDTO(
        response_id: 555,
        collector_id: 1,
        survey_id: 12345,
        started_on: Carbon::parse('2026-01-01T12:00:00+00:00'),
        last_updated_on: Carbon::parse('2026-01-01T12:05:00+00:00'),
        email_address: null,
        recipient_data: null,
        link_parameters: null,
        language: (object) ['code' => 'en'],
        ip_address: null,
        meta_data: (object) [],
        status: 'completed',
        answers: [
            (object) [
                'element_id' => 999999,
                'type' => 'text',
                'text' => 'Unmapped answer',
            ],
        ]
    );

    $client = mock(SurveyheroClient::class);
    $client->shouldReceive('getSurveyResponseAnswers')
        ->once()
        ->with(12345, 555)
        ->andReturn($responseAnswersDto);
    $client->shouldReceive('getResumeLink')->never();

    $mappingService = app(SurveyMappingService::class);

    $service = new SurveyResponseImportService($client, $mappingService);

    /** @var SurveyContract $surveyContract */
    $surveyContract = $survey;

    $importInfo = $service->importSurveyResponse(555, $surveyContract, [
        [
            'question_id' => 123456,
            'type' => 'text',
            'field' => 'question_1',
        ],
    ]);

    expect($importInfo)->not->toBeNull();
    expect($importInfo->hasUnimportedQuestions())->toBeTrue();
    expect($importInfo->getUnimportedQuestions())->toHaveKey(999999);
    expect($importInfo->getUnimportedQuestions()[999999])->toBe('No question mapping available in configuration file.');

    $importedResponse = SurveyResponse::query()->where('surveyhero_id', 555)->first();
    expect($importedResponse)->not->toBeNull();
    expect($importedResponse->survey_completed)->toBeFalsy();

    Event::assertDispatched(SurveyResponseIncompletelyImported::class);
});
