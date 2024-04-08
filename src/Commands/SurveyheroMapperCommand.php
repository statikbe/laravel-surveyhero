<?php

namespace Statikbe\Surveyhero\Commands;

use Illuminate\Console\Command;
use Statikbe\Surveyhero\Contracts\SurveyContract;
use Statikbe\Surveyhero\Services\SurveyMappingService;
use Statikbe\Surveyhero\SurveyheroRegistrar;

class SurveyheroMapperCommand extends Command
{
    public $signature = 'surveyhero:map
                        {--survey=all : The Surveyhero survey ID}
                        {--generateConfig : Generate the boiler plate for your question mapping}
                        {--updateDatabase : Update the question_mapping and collector_ids fields in the database based on the API}';

    public $description = 'Map all questions and answers linked to configured surveys.';

    private SurveyMappingService $mappingService;

    public function __construct(SurveyMappingService $surveyMappingService)
    {
        parent::__construct();

        $this->mappingService = $surveyMappingService;
    }

    public function handle(): int
    {
        $surveyId = trim($this->option('survey'));
        $generateConfig = trim($this->option('generateConfig'));
        $updateDatabase = trim($this->option('updateDatabase'));

        if (! $generateConfig && ! $updateDatabase) {
            $this->comment('Please include at least one of the following parameters:');
            $this->comment('--generateConfig (Generate the boiler plate for your question mapping)');
            $this->comment('--updateDatabase (Update the question_mapping field in the database based on the API and config)');

            return self::FAILURE;
        }

        $surveyQuery = app(SurveyheroRegistrar::class)->getSurveyClass()::query();
        if ($surveyId !== 'all') {
            $surveyQuery->where('surveyhero_id', $surveyId);
        }
        $surveys = $surveyQuery->get();

        $mapping = [];
        foreach ($surveys as $surveyIndex => $survey) {
            /* @var SurveyContract $survey */
            try {
                $surveyQuestionMapping = $this->mappingService->map($survey);
                $mapping['question_mapping'][$surveyIndex] = $surveyQuestionMapping;

                if ($updateDatabase) {
                    $this->updateDatabaseMapping($survey, $surveyQuestionMapping);
                    $this->comment('Mapping for survey ['.$survey->name.'] stored in database');
                } else {
                    $this->comment("Mapping for survey ['$survey->name'] completed!");
                }
            } catch (\Exception $e) {
                $this->error($e->getMessage());

                return self::FAILURE;
            }
            $this->comment("Mapping for survey ['$survey->name'] completed!");
        }

        if ($generateConfig) {
            $fileName = $this->writeFile($mapping);
            $this->comment("Generated mapping saved to: $fileName");
        }

        $this->comment('Mapping complete!');

        return self::SUCCESS;
    }

    private function updateDatabaseMapping($survey, $surveyQuestionMapping): void
    {
        $collectors = array_map('intval', explode(',', $surveyQuestionMapping['collectors']));

        app(SurveyheroRegistrar::class)->getSurveyClass()::updateOrCreate(
            ['id' => $survey->id],
            [
                'collector_ids' => $collectors,
                'question_mapping' => $surveyQuestionMapping['questions'],
            ]
        );
    }

    private function var_export_short($data, $linePrefix): string
    {
        $dump = var_export($data, true);

        //Add question_id keys to questions array. This is necessary for merging with the api config
        $dump = preg_replace('#(?:\A|\n)([ ]*)array \(#i', $data['question_id'].' => [', $dump, 1);
        $dump = preg_replace('#(?:\A|\n)([ ]*)array \(#i', '[', $dump); // Starts
        $dump = preg_replace('#\n([ ]*)\),#', "\n$1],", $dump); // Ends
        $dump = preg_replace('#=> \[\n\s+\],\n#', "=> [],\n", $dump); // Empties

        if (gettype($data) == 'object') { // Deal with object states
            $dump = str_replace('__set_state(array(', '__set_state([', $dump);
            $dump = preg_replace('#\)\)$#', '])', $dump);
        } else {
            $dump = preg_replace('#\)$#', ']', $dump);
        }

        if ($linePrefix) {
            $arr = explode("\n", $dump);
            foreach ($arr as $key => $value) {
                $arr[$key] = $linePrefix.$arr[$key];
            }
            $dump = implode("\n", $arr);
        }

        return $dump;
    }

    private function writeFile(array $mapping): string
    {
        $fileName = 'surveyhero_mapping.php';
        $myfile = fopen($fileName, 'w') or exit('Unable to open file!');

        fwrite($myfile, "<?php \n\n");
        fwrite($myfile, "return [\n\t'question_mapping' => [\n");
        foreach ($mapping['question_mapping'] as $surveyMap) {
            fwrite($myfile, "\t\t[\n");
            fwrite($myfile, sprintf("\t\t\t'survey_id' => %s, \n\t\t\t'collectors' => [%s], \n\t\t\t'questions' => [\n", $surveyMap['survey_id'], $surveyMap['collectors']));
            foreach ($surveyMap['questions'] as $line => $questionMap) {
                fwrite($myfile, $this->var_export_short($questionMap, "\t\t\t\t"));
                fwrite($myfile, ",\n");
            }
            fwrite($myfile, "\t\t\t],\n");
            fwrite($myfile, "\t\t], \n");
        }
        fwrite($myfile, "\t],\n");
        fwrite($myfile, "]; \n");
        fclose($myfile);

        return $fileName;
    }
}
