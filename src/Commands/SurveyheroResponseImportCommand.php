<?php

namespace Statikbe\Surveyhero\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Statikbe\Surveyhero\Exceptions\ResponseCreatorNotImplemented;
use Statikbe\Surveyhero\Exceptions\SurveyNotMappedException;
use Statikbe\Surveyhero\Models\Survey;
use Statikbe\Surveyhero\Models\SurveyQuestionResponse;
use Statikbe\Surveyhero\Models\SurveyResponse;
use Statikbe\Surveyhero\Services\SurveyResponseImportService;

class SurveyheroResponseImportCommand extends Command
{
    public $signature = 'surveyhero:import-responses {--survey=all : The Surveyhero survey ID} {--fresh : Delete ALL imported responses and re-import}';

    public $description = 'Retrieve survey responses from SurveyHero API and save in database.';

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

        $surveyQuery = Survey::query();
        if ($surveyId !== 'all') {
            $surveyQuery->where('surveyhero_id', $surveyId);
        }
        $surveys = $surveyQuery->get();

        foreach ($surveys as $survey) {
            try {
                $notImported = $this->importService->importSurveyResponses($survey);
            } catch (SurveyNotMappedException $exception) {
                $this->error("{$exception->getMessage()} Survey '$survey->name' with Surveyhero ID $survey->surveyhero_id");
                return self::FAILURE;
            }
            catch(ResponseCreatorNotImplemented $exception){
                $this->error($exception->getMessage());
                return self::FAILURE;
            }

            if (! empty($notImported['questions'])) {
                $this->info(sprintf('%d questions could not imported!', count($notImported['questions'])));
                $this->table(['Surveyhero ID'], $notImported['questions']);
            }

            if (! empty($notImported['answers'])) {
                $this->info('Not all answers are mapped:');
                $this->table(['Surveyhero ID', 'Answer info'], $notImported['answers']);
            }

            $this->comment("Survey '$survey->name' imported!");
        }

        $this->comment(sprintf('Imported %d survey%s!', count($surveys), count($surveys) > 1 ? 's' : ''));

        return self::SUCCESS;
    }

    private function deleteResponses()
    {
        Schema::disableForeignKeyConstraints();
        SurveyQuestionResponse::truncate();
        SurveyResponse::truncate();
        Schema::enableForeignKeyConstraints();
    }
}
