<?php

namespace Statikbe\Surveyhero\Exports;

use Maatwebsite\Excel\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Shared\StringHelper;
use Stringable;

/**
 * Value binder that stops spreadsheet formula injection (CSV injection).
 *
 * Survey answers, link parameters and labels come from respondents or an external API. The
 * default binder stores any string that parses as a formula (e.g. `=HYPERLINK(...)`) as a real
 * formula, which the person opening the export would then execute.
 *
 * Every string that starts with one of `= + - @ \t \r` is written as literal text instead.
 * PHP ints/floats/null/bool are never touched, so numeric columns stay numeric.
 *
 * Numeric strings (`-5`, `+3.5`) are deliberately left to the default binder: they can't contain
 * a formula, and the responses sheet implodes answers into strings, so treating them as text
 * would turn every negative number answer into a text cell.
 *
 * CSV: a CSV file has no cell types, so the explicit string type is lost on writing and the
 * spreadsheet application would parse the value as a formula again. For CSV only, a single quote
 * is therefore prefixed (the OWASP recommendation). XLSX never gets a visible prefix.
 */
class SafeValueBinder extends DefaultValueBinder
{
    private const DANGEROUS_PREFIXES = ['=', '+', '-', '@', "\t", "\r"];

    public function __construct(private readonly bool $prefixForCsv = false) {}

    public function bindValue(Cell $cell, mixed $value): bool
    {
        if ($value instanceof Stringable) {
            $value = (string) $value;
        }

        if (is_string($value)) {
            $value = StringHelper::sanitizeUTF8($value);

            if (self::isDangerous($value)) {
                $cell->setValueExplicit($this->prefixForCsv ? "'".$value : $value, DataType::TYPE_STRING);

                return true;
            }
        }

        return parent::bindValue($cell, $value);
    }

    public static function isDangerous(string $value): bool
    {
        if ($value === '' || ! in_array($value[0], self::DANGEROUS_PREFIXES, true)) {
            return false;
        }

        // plain signed numbers cannot hold a formula, keep them numeric
        return ! preg_match('/^[+-]?\d+(\.\d+)?$/', $value);
    }
}
