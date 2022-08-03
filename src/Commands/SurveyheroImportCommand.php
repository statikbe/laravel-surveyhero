<?php

namespace Statikbe\Surveyhero\Commands;

use Illuminate\Console\Command;
use Statikbe\Surveyhero\Models\Survey;
use Statikbe\Surveyhero\Services\SurveyResponseImportService;

class SurveyheroImportCommand extends Command
{
    public $signature = 'surveyhero:import {--survey=all : The Surveyhero survey ID}';

    public $description = 'Retrieve survey responses from SurveyHero API and save in database.';

    private SurveyResponseImportService $importService;

    public function __construct(SurveyResponseImportService $surveyResponseImportService) {
        parent::__construct();

        $this->importService = $surveyResponseImportService;
    }

    public function handle(): int
    {
        $surveyId = trim($this->option('survey'));

        $surveyQuery = Survey::query();
        if($surveyId !== 'all'){
            $surveyQuery->where('surveyhero_id', $surveyId);
        }
        $surveys = $surveyQuery->get();

        foreach($surveys as $survey){
            $this->importService->importSurveyResponses($survey);
            $this->comment(sprintf('Survey "%s" imported!', $survey->name));
        }

        $this->comment(sprintf('Imported %d survey%s!', count($surveys), count($surveys) > 1 ? 's' : ''));

        return self::SUCCESS;
    }
}
