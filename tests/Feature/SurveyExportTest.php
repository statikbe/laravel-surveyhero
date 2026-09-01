<?php

use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Concerns\Export;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Statikbe\Surveyhero\Exports\Sheets\AnswersSheet;
use Statikbe\Surveyhero\Exports\Sheets\QuestionsSheet;
use Statikbe\Surveyhero\Exports\Sheets\ResponsesSheet;
use Statikbe\Surveyhero\Exports\SurveyExport;
use Statikbe\Surveyhero\Models\Survey;
use Statikbe\Surveyhero\Models\SurveyAnswer;
use Statikbe\Surveyhero\Models\SurveyQuestion;
use Statikbe\Surveyhero\Models\SurveyQuestionResponse;
use Statikbe\Surveyhero\Models\SurveyResponse;

/**
 * Builds a survey with two questions and a single completed response, and returns
 * an export limited to the responses sheet. The questions and answers sheets rely
 * on JSON_KEYS(), which SQLite does not provide, so they are excluded here.
 */
function makeResponsesOnlyExport(array $linkParameters = [], array $extraResponseColumns = []): array
{
    $survey = Survey::factory()->create();

    $questionOne = SurveyQuestion::factory()->for($survey, 'survey')->create(['field' => 'question_1']);
    $questionTwo = SurveyQuestion::factory()->for($survey, 'survey')->create(['field' => 'question_2']);

    $answerOne = SurveyAnswer::factory()->for($questionOne, 'surveyQuestion')->create([
        'converted_string_value' => 'Yes',
        'converted_int_value' => null,
    ]);
    $answerTwo = SurveyAnswer::factory()->for($questionTwo, 'surveyQuestion')->create([
        'converted_string_value' => null,
        'converted_int_value' => 42,
    ]);

    $response = SurveyResponse::factory()->for($survey, 'survey')->create([
        'surveyhero_id' => 555,
        'surveyhero_link_parameters' => json_encode(['participant' => 'p-1']),
    ]);

    SurveyQuestionResponse::factory()->create([
        'survey_response_id' => $response->id,
        'survey_question_id' => $questionOne->id,
        'survey_answer_id' => $answerOne->id,
    ]);
    SurveyQuestionResponse::factory()->create([
        'survey_response_id' => $response->id,
        'survey_question_id' => $questionTwo->id,
        'survey_answer_id' => $answerTwo->id,
    ]);

    $export = new SurveyExport($survey, $linkParameters, $extraResponseColumns);
    $export->setSheets([new ResponsesSheet($survey, $linkParameters, $extraResponseColumns)]);

    return [$survey, $export];
}

it('declares sheet signatures that satisfy the Laravel-Excel concerns', function (string $sheetClass) {
    // PHP verifies the interface signatures while loading the class, so a mismatch
    // with a Laravel-Excel concern is a fatal error here rather than a silent pass.
    expect(class_exists($sheetClass))->toBeTrue();
})->with([
    AnswersSheet::class,
    QuestionsSheet::class,
    ResponsesSheet::class,
]);

it('is recognised as a Laravel-Excel export', function () {
    [, $export] = makeResponsesOnlyExport();

    expect($export)->toBeInstanceOf(Export::class);
    expect($export->sheets())->each->toBeInstanceOf(Export::class);
});

it('writes a spreadsheet with the response rows', function () {
    Storage::fake('local');

    [, $export] = makeResponsesOnlyExport(['participant']);

    expect($export->store('survey.xlsx', 'local'))->toBeTrue();

    $path = Storage::disk('local')->path('survey.xlsx');
    $sheet = IOFactory::load($path)->getActiveSheet();

    expect($sheet->getTitle())->toBe('Responses');
    expect($sheet->rangeToArray('A1:D2', null, true, false))->toBe([
        ['surveyhero_response_id', 'participant', 'question_1', 'question_2'],
        [555, 'p-1', 'Yes', 42],
    ]);
});

it('downloads the spreadsheet as a binary file response', function () {
    [, $export] = makeResponsesOnlyExport();

    $response = $export->download('survey.xlsx');

    expect($response->getStatusCode())->toBe(200);
    expect($response->headers->get('content-disposition'))->toContain('survey.xlsx');
});
