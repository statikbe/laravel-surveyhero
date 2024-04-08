<?php

namespace Statikbe\Surveyhero\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Statikbe\Surveyhero\Services\SurveyImportService;
use Statikbe\Surveyhero\SurveyheroRegistrar;

class SurveyheroSurveyImportCommand extends Command
{
    public $signature = 'surveyhero:import-surveys {--survey= : The Surveyhero survey ID} {--all : Import all Surveyhero surveys} {--fresh : Delete ALL imported surveys and re-import}';

    public $description = 'Retrieve surveys from SurveyHero API and save in the database.';

    private SurveyImportService $importService;

    public function __construct(SurveyImportService $surveyImportService)
    {
        parent::__construct();

        $this->importService = $surveyImportService;
    }

    public function handle(): int
    {
        $truncateResponses = $this->option('fresh');

        if ($truncateResponses) {
            $this->deleteSurveys();
        }

        $surveyId = trim($this->option('survey'));

        /* @var Collection $existingSurveys */
        $surveyIdsToImport = null;
        if ($surveyId) {
            $surveyIdsToImport = app(SurveyheroRegistrar::class)->getSurveyClass()::query()
                ->where('surveyhero_id', $surveyId)
                ->select('surveyhero_id')
                ->get();
        }
        //if no survey id is passed as arg, we check if there is a mapping and import there surveys, otherwise we import all.
        else {
            $questionMapping = config('surveyhero.question_mapping');
            $surveyIdsToImport = collect(array_map(function ($elem) {
                return $elem['survey_id'];
            }, $questionMapping));
        }

        $importedInfo = $this->importService->importSurveys($surveyIdsToImport);

        if (! empty($importedInfo['notImported'])) {
            $this->info(sprintf('%d surveys could not imported!', count($importedInfo['notImported'])));
            $this->table(['Surveyhero ID'], $importedInfo['notImported']);
        }

        $this->comment(sprintf('Imported %d survey%s!', count($importedInfo['imported']), count($importedInfo['imported']) > 1 ? 's' : ''));

        return self::SUCCESS;
    }

    private function deleteSurveys()
    {
        Schema::disableForeignKeyConstraints();
        app(SurveyheroRegistrar::class)->getSurveyClass()::truncate();
        Schema::enableForeignKeyConstraints();
    }
}
