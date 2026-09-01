<?php

namespace Statikbe\Surveyhero\Exports;

use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\Import;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Statikbe\Surveyhero\Contracts\SurveyContract;
use Statikbe\Surveyhero\Exports\Sheets\AnswersSheet;
use Statikbe\Surveyhero\Exports\Sheets\QuestionsSheet;
use Statikbe\Surveyhero\Exports\Sheets\ResponsesSheet;

class SurveyExport implements Export, ShouldAutoSize, WithMultipleSheets
{
    use Exportable;

    private SurveyContract $survey;

    private array $linkParameters;

    private array $extraResponseColumns;

    private array $sheets = [];

    public function __construct(SurveyContract $survey, array $linkParameters, array $extraResponseColumns = [])
    {
        $this->survey = $survey;
        $this->linkParameters = $linkParameters;
        $this->extraResponseColumns = $extraResponseColumns;
    }

    /**
     * @return array<int|string, Export|Import>
     */
    public function sheets(): array
    {
        if ($this->sheets) {
            return $this->sheets;
        }

        // default config:
        return [
            new QuestionsSheet($this->survey),
            new AnswersSheet($this->survey),
            new ResponsesSheet($this->survey, $this->linkParameters, $this->extraResponseColumns),
        ];
    }

    /**
     * Override default worksheets list.
     *
     * @param  array<int|string, Export|Import>  $sheets
     */
    public function setSheets(array $sheets): void
    {
        $this->sheets = $sheets;
    }
}
