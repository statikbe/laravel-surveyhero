<?php

namespace Statikbe\Surveyhero\Commands;

use Illuminate\Console\Command;
use Statikbe\Surveyhero\Services\SurveyExportService;
use Statikbe\Surveyhero\SurveyheroRegistrar;

class SurveyResponseExportCommand extends Command
{
    public $signature = 'surveyhero:responses-export
                        {--survey= : The Surveyhero survey ID}
                        {--file=survey.xlsx : The export file name or path}
                        {--linkParameter=* : A list of link parameters to be included in the export}
                        {--extraResponseCol=* : A list of extra columns of the survey_responses table to be included in the export}';

    public $description = 'Generate a spreadsheet with the survey responses.';

    public function handle(SurveyExportService $exportService): int
    {
        $surveyId = trim($this->option('survey'));
        $filePath = trim($this->option('file'));
        $linkParameters = $this->option('linkParameter');
        $extraResponseColumns = $this->option('extraResponseCol');

        $survey = app(SurveyheroRegistrar::class)->getSurveyClass()::where('surveyhero_id', $surveyId)->first();

        $exportService->exportToFile($survey, $filePath, $linkParameters, $extraResponseColumns);

        return self::SUCCESS;
    }
}
