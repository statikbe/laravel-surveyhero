<?php

namespace Statikbe\Surveyhero\Commands;

use Illuminate\Console\Command;
use Statikbe\Surveyhero\Contracts\SurveyContract;
use Statikbe\Surveyhero\Services\SurveyMappingService;
use Statikbe\Surveyhero\SurveyheroRegistrar;

class SurveyheroMapperCommand extends Command
{
    public $signature = 'surveyhero:map {--survey=all : The Surveyhero survey ID}';

    public $description = 'Map all questions and answers linked to configured surveys.';

    /**
     * @var \Statikbe\Surveyhero\Services\SurveyMappingService
     */
    private SurveyMappingService $mappingService;

    public function __construct(SurveyMappingService $surveyMappingService)
    {
        parent::__construct();

        $this->mappingService = $surveyMappingService;
    }

    public function handle(): int
    {
        $surveyId = trim($this->option('survey'));

        $surveyQuery = app(SurveyheroRegistrar::class)->getSurveyClass()::query();
        if ($surveyId !== 'all') {
            $surveyQuery->where('surveyhero_id', $surveyId);
        }
        $surveys = $surveyQuery->get();

        $mapping = [];
        foreach ($surveys as $surveyIndex => $survey) {
            /* @var SurveyContract $survey */
            try {
                $mapping['question_mapping'][$surveyIndex] = $this->mappingService->map($survey);
            } catch (\Exception $e) {
                $this->error($e->getMessage());

                return self::FAILURE;
            }
            $this->comment("Mapping for survey '$survey->name' completed!");
        }

        $fileName = $this->writeFile($mapping);

        $this->comment("Mapping complete! [$fileName]");

        return self::SUCCESS;
    }

    private function var_export_short($data, $linePrefix): string
    {
        $dump = var_export($data, true);

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
        fwrite($myfile, "\t\t[\n");
        foreach ($mapping['question_mapping'] as $surveyMap) {
            fwrite($myfile, sprintf("\t\t\t'survey_id' => %s, \n\t\t\t'questions' => [\n", $surveyMap['survey_id']));
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
