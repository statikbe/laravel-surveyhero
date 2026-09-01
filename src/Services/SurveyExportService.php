<?php

namespace Statikbe\Surveyhero\Services;

use Illuminate\Bus\PendingBatch;
use Illuminate\Foundation\Bus\PendingDispatch;
use Statikbe\Surveyhero\Contracts\SurveyContract;
use Statikbe\Surveyhero\Exports\SurveyExport;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SurveyExportService
{
    public function exportToFile(SurveyContract $survey, string $filePath, array $linkParameters = [], array $extraResponseColumns = []): bool|PendingDispatch|PendingBatch
    {
        $export = $this->createSurveyExport($survey, $linkParameters, $extraResponseColumns);

        return $export->store($filePath);
    }

    public function exportDownload(SurveyContract $survey, string $fileName, array $linkParameters = [], array $extraResponseColumns = []): BinaryFileResponse
    {
        $export = $this->createSurveyExport($survey, $linkParameters, $extraResponseColumns);

        return $export->download($fileName);
    }

    public function createSurveyExport(SurveyContract $survey, array $linkParameters, array $extraResponseColumns): SurveyExport
    {
        return new SurveyExport($survey, $linkParameters, $extraResponseColumns);
    }
}
