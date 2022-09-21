<?php

namespace Statikbe\Surveyhero\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Statikbe\Surveyhero\Contracts\SurveyContract;
use Statikbe\Surveyhero\Exceptions\ResponseCreatorNotImplemented;
use Statikbe\Surveyhero\Exceptions\SurveyNotMappedException;
use Statikbe\Surveyhero\Services\SurveyResponseImportService;
use Statikbe\Surveyhero\SurveyheroRegistrar;

class SurveyheroResponseImportCommand extends Command
{
    public $signature = 'surveyhero:import-responses {--survey=all : The Surveyhero survey ID} {--fresh : Delete ALL imported responses and re-import}';

    public $description = 'Retrieve survey responses from SurveyHero API and save in the database.';

    /**
     * @var \Statikbe\Surveyhero\Services\SurveyResponseImportService
     */
    private SurveyResponseImportService $importService;

    public function __construct(SurveyResponseImportService $surveyResponseImportService)
    {
        parent::__construct();

        $this->importService = $surveyResponseImportService;
    }

    public function handle(): int
    {
        $truncateResponses = $this->option('fresh');

        if ($truncateResponses) {
            $this->deleteResponses();
        }

        $surveyId = trim($this->option('survey'));

        $surveyQuery = app(SurveyheroRegistrar::class)->getSurveyClass()::query();
        if ($surveyId !== 'all') {
            $surveyQuery->where('surveyhero_id', $surveyId);
        }
        $surveys = $surveyQuery->get();

        foreach ($surveys as $survey) {
            /* @var SurveyContract $survey */
            try {
                $importInfo = $this->importService->importSurveyResponses($survey);
            } catch (SurveyNotMappedException $exception) {
                $this->error("{$exception->getMessage()} Survey '$survey->name' with Surveyhero ID $survey->surveyhero_id");

                return self::FAILURE;
            } catch (ResponseCreatorNotImplemented $exception) {
                $this->error($exception->getMessage());

                return self::FAILURE;
            }

            if (! empty($importInfo['questions'])) {
                $this->info(sprintf('%d questions could not imported!', count($importInfo['questions'])));
                $this->table(['Surveyhero ID'], $importInfo['questions']);
            }

            if (! empty($importInfo['answers'])) {
                $this->info('Not all answers are mapped:');
                $this->table(['Surveyhero ID', 'Answer info'], $importInfo['answers']);
            }

            $this->comment("Survey '$survey->name' imported!");
        }

        $this->comment(sprintf('Imported %d responses of %d survey%s!', $importInfo['total_responses'], count($surveys), count($surveys) > 1 ? 's' : ''));

        return self::SUCCESS;
    }

    private function deleteResponses()
    {
        Schema::disableForeignKeyConstraints();
        app(SurveyheroRegistrar::class)->getSurveyQuestionResponseClass()::truncate();
        app(SurveyheroRegistrar::class)->getSurveyResponseClass()::truncate();
        Schema::enableForeignKeyConstraints();
    }
}
