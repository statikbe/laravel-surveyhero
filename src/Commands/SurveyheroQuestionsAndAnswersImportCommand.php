<?php

namespace Statikbe\Surveyhero\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Statikbe\Surveyhero\Contracts\SurveyContract;
use Statikbe\Surveyhero\Services\SurveyQuestionsAndAnswersImportService;
use Statikbe\Surveyhero\SurveyheroRegistrar;

class SurveyheroQuestionsAndAnswersImportCommand extends Command
{
    public $signature = 'surveyhero:import-questions-and-answers {--survey=all : The Surveyhero survey ID} {--fresh : Delete ALL imported responses and re-import}';

    public $description = 'Retrieve survey questions and answers from SurveyHero API and save in database.';

    private SurveyQuestionsAndAnswersImportService $importService;

    public function __construct(SurveyQuestionsAndAnswersImportService $surveyQuestionsAndAnswersImportService)
    {
        parent::__construct();

        $this->importService = $surveyQuestionsAndAnswersImportService;
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
                $notImported = $this->importService->importSurveyQuestionsAndAnswers($survey);
            } catch (\Exception $e) {
                $this->error($e->getMessage());

                return self::FAILURE;
            }

            if (! empty($notImported['question'])) {
                $this->info('Not all question can be imported:');
                $this->table(['Surveyhero ID', 'Answer info'], $notImported['question']);
            }

            $this->comment("Questions and answers for survey '$survey->name' imported!");
        }

        $this->comment(sprintf('Imported questions and answers for %d survey%s!', count($surveys), count($surveys) > 1 ? 's' : ''));

        return self::SUCCESS;
    }

    private function deleteResponses()
    {
        Schema::disableForeignKeyConstraints();
        app(SurveyheroRegistrar::class)->getSurveyQuestionClass()::truncate();
        app(SurveyheroRegistrar::class)->getSurveyAnswerClass()::truncate();
        Schema::enableForeignKeyConstraints();
    }
}
