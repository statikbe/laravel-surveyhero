<?php

namespace Statikbe\Surveyhero\Exports\Concerns;

use PhpOffice\PhpSpreadsheet\Cell\Cell;
use Statikbe\Surveyhero\Exports\SafeValueBinder;

/**
 * Makes an export (or a sheet) its own formula-safe value binder.
 *
 * Laravel Excel's `WithCustomValueBinder` concern needs the export object itself to implement
 * `bindValue()`; it registers that object as PhpSpreadsheet's static cell value binder when the
 * sheet is opened and again before every row. Use this trait together with
 * `Maatwebsite\Excel\Concerns\WithCustomValueBinder` on any custom sheet that exports
 * respondent-provided text.
 */
trait BindsValuesSafely
{
    private bool $prefixFormulaCharactersForCsv = false;

    private ?SafeValueBinder $safeValueBinder = null;

    /**
     * Prefix dangerous values with a single quote, needed when the output is CSV.
     */
    public function prefixFormulaCharactersForCsv(bool $prefix = true): static
    {
        $this->prefixFormulaCharactersForCsv = $prefix;
        $this->safeValueBinder = null;

        return $this;
    }

    public function bindValue(Cell $cell, mixed $value): bool
    {
        $this->safeValueBinder ??= new SafeValueBinder($this->prefixFormulaCharactersForCsv);

        return $this->safeValueBinder->bindValue($cell, $value);
    }
}
