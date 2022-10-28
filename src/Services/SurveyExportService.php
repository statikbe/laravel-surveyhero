<?php

namespace Statikbe\Surveyhero\Services;

use Statikbe\Surveyhero\Contracts\SurveyContract;
use Statikbe\Surveyhero\Exports\SurveyExport;

class SurveyExportService
{
    public function exportToFile(SurveyContract $survey, string $filePath, array $linkParameters = [], array $extraResponseColumns = [])
    {
        $export = $this->createSurveyExport($survey, $linkParameters, $extraResponseColumns);

        return $export->store($filePath);
    }

    public function exportDownload(SurveyContract $survey, string $fileName, array $linkParameters = [], array $extraResponseColumns = [])
    {
        $export = $this->createSurveyExport($survey, $linkParameters, $extraResponseColumns);

        return $export->download($fileName);
    }

    public function createSurveyExport(SurveyContract $survey, array $linkParameters, array $extraResponseColumns)
    {
        return new SurveyExport($survey, $linkParameters, $extraResponseColumns);
    }
}
