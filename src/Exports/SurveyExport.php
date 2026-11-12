<?php

namespace Statikbe\Surveyhero\Exports;

use Illuminate\Bus\PendingBatch;
use Illuminate\Foundation\Bus\PendingDispatch;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\Import;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Excel;
use Maatwebsite\Excel\Exceptions\NoTypeDetectedException;
use Maatwebsite\Excel\Helpers\FileTypeDetector;
use Statikbe\Surveyhero\Contracts\SurveyContract;
use Statikbe\Surveyhero\Exports\Concerns\BindsValuesSafely;
use Statikbe\Surveyhero\Exports\Sheets\AnswersSheet;
use Statikbe\Surveyhero\Exports\Sheets\QuestionsSheet;
use Statikbe\Surveyhero\Exports\Sheets\ResponsesSheet;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * The export is a formula-safe value binder itself (see SafeValueBinder). The default sheets
 * each bind their own values; custom sheets passed to setSheets() that do not implement
 * WithCustomValueBinder fall back to the binder registered for this export.
 */
class SurveyExport implements Export, ShouldAutoSize, WithCustomValueBinder, WithMultipleSheets
{
    use BindsValuesSafely;
    use Exportable {
        download as private exportableDownload;
        store as private exportableStore;
        queue as private exportableQueue;
        raw as private exportableRaw;
    }

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
        // default config, unless overridden:
        $sheets = $this->sheets ?: [
            new QuestionsSheet($this->survey),
            new AnswersSheet($this->survey),
            new ResponsesSheet($this->survey, $this->linkParameters, $this->extraResponseColumns),
        ];

        foreach ($sheets as $sheet) {
            if (method_exists($sheet, 'prefixFormulaCharactersForCsv')) {
                $sheet->prefixFormulaCharactersForCsv($this->prefixFormulaCharactersForCsv);
            }
        }

        return $sheets;
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

    /*
     * CSV has no cell types, so the "store as text" type set by SafeValueBinder is lost when
     * writing. For CSV output only, formula characters are prefixed with a single quote instead.
     * Laravel Excel resolves the writer type from the file name deep inside the exporter, out of
     * reach of the sheets, so it is resolved here first. XLSX output never gets a visible prefix.
     */
    public function download(?string $fileName = null, ?string $writerType = null, ?array $headers = null): BinaryFileResponse
    {
        $this->detectCsvOutput($fileName ?? $this->fileName, $writerType);

        return $this->exportableDownload($fileName, $writerType, $headers);
    }

    public function store(?string $filePath = null, ?string $disk = null, ?string $writerType = null, mixed $diskOptions = []): bool|PendingDispatch|PendingBatch
    {
        $this->detectCsvOutput($filePath ?? $this->filePath, $writerType);

        return $this->exportableStore($filePath, $disk, $writerType, $diskOptions);
    }

    public function queue(?string $filePath = null, ?string $disk = null, ?string $writerType = null, mixed $diskOptions = []): PendingDispatch|PendingBatch
    {
        $this->detectCsvOutput($filePath ?? $this->filePath, $writerType);

        return $this->exportableQueue($filePath, $disk, $writerType, $diskOptions);
    }

    public function raw(?string $writerType = null): string
    {
        $this->detectCsvOutput(null, $writerType);

        return $this->exportableRaw($writerType);
    }

    private function detectCsvOutput(?string $fileName, ?string $writerType): void
    {
        $writerType ??= $this->writerType;

        try {
            $detectedType = $fileName !== null || $writerType !== null
                ? FileTypeDetector::detect($fileName ?? '', $writerType)
                : null;
        } catch (NoTypeDetectedException) {
            // Laravel Excel throws the proper exception later on
            $detectedType = null;
        }

        $this->prefixFormulaCharactersForCsv($detectedType === Excel::CSV);
    }
}
