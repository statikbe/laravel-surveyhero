<?php

namespace Statikbe\Surveyhero\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Statikbe\Surveyhero\Contracts\SurveyContract;
use Statikbe\Surveyhero\Exceptions\ResponseCreatorNotImplemented;
use Statikbe\Surveyhero\Exceptions\SurveyNotMappedException;
use Statikbe\Surveyhero\Services\Info\ResponseImportInfo;
use Statikbe\Surveyhero\Services\SurveyResponseImportService;
use Statikbe\Surveyhero\SurveyheroRegistrar;

class SurveyheroResponseImportCommand extends Command
{
    public $signature = 'surveyhero:import-responses {--survey=all : The Surveyhero survey ID} {--fresh : Delete ALL imported responses and re-import}';

    public $description = 'Retrieve survey responses from SurveyHero API and save in the database.';

    private SurveyResponseImportService $importService;

    public function __construct(SurveyResponseImportService $surveyResponseImportService)
    {
        parent::__construct();

        $this->importService = $surveyResponseImportService;
    }

    public function handle(): int
    {
        $refreshResponses = $this->option('fresh');

        if ($refreshResponses) {
            $this->deleteResponses();
        }

        $importInfo = new ResponseImportInfo;

        $surveyId = trim($this->option('survey'));

        $surveyQuery = app(SurveyheroRegistrar::class)->getSurveyClass()::query();
        if ($surveyId !== 'all') {
            $surveyQuery->where('surveyhero_id', $surveyId);
        }
        $surveys = $surveyQuery->get();

        foreach ($surveys as $survey) {
            /* @var SurveyContract $survey */
            try {
                if ($refreshResponses) {
                    $survey->survey_last_imported = null;
                    $survey->save();
                }
                $importInfo->addInfo($this->importService->importSurveyResponses($survey));
            } catch (SurveyNotMappedException $exception) {
                $this->error("{$exception->getMessage()} Survey '$survey->name' with Surveyhero ID $survey->surveyhero_id");

                return self::FAILURE;
            } catch (ResponseCreatorNotImplemented $exception) {
                $this->error($exception->getMessage());

                return self::FAILURE;
            }

            if ($importInfo->hasUnimportedQuestions()) {
                $this->info(sprintf('%d questions could not imported!', count($importInfo->getUnimportedQuestions())));
                $this->table(['Surveyhero ID', 'Info'], $importInfo->getUnimportedQuestions());
            }

            if ($importInfo->hasUnimportedAnswers()) {
                $this->info('Not all answers are mapped:');
                $this->table(['Surveyhero ID', 'Info'], $importInfo->getUnimportedAnswers());
            }

            $this->comment("Survey '$survey->name' imported!");
        }

        $this->comment(sprintf('Imported %d responses of %d survey%s!', $importInfo->getTotalResponsesImported(), count($surveys), count($surveys) > 1 ? 's' : ''));

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
