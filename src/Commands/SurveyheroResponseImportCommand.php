<?php

namespace Statikbe\Surveyhero\Commands;

use Illuminate\Console\Command;
use Statikbe\Surveyhero\Models\Survey;
use Statikbe\Surveyhero\Services\SurveyResponseImportService;

class SurveyheroResponseImportCommand extends Command
{
    public $signature = 'surveyhero:import-responses {--survey=all : The Surveyhero survey ID}';

    public $description = 'Retrieve survey responses from SurveyHero API and save in database.';

    private SurveyResponseImportService $importService;

    public function __construct(SurveyResponseImportService $surveyResponseImportService)
    {
        parent::__construct();

        $this->importService = $surveyResponseImportService;
    }

    public function handle(): int
    {
        $surveyId = trim($this->option('survey'));

        $surveyQuery = Survey::query();
        if ($surveyId !== 'all') {
            $surveyQuery->where('surveyhero_id', $surveyId);
        }
        $surveys = $surveyQuery->get();

        foreach ($surveys as $survey) {
            $notImported = $this->importService->importSurveyResponses($survey);

            if (!empty($notImported['questions'])) {
                $this->info(sprintf('%d questions could not imported!', count($notImported['questions'])));
                $this->table(['Surveyhero ID'], $notImported['questions']);
            }

            if (!empty($notImported['answers'])) {
                $this->info('Not all answers are mapped:');
                $this->table(['Surveyhero ID', 'Answer info'], $notImported['answers']);
            }

            $this->comment("Survey '$survey->name' imported!");
        }

        $this->comment(sprintf('Imported %d survey%s!', count($surveys), count($surveys) > 1 ? 's' : ''));

        return self::SUCCESS;
    }
}
