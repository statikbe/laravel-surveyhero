<?php

namespace Statikbe\Surveyhero\Commands;

use Illuminate\Console\Command;
use Statikbe\Surveyhero\Models\Survey;
use Statikbe\Surveyhero\Services\SurveyMappingService;

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

        $surveyQuery = Survey::query();
        if ($surveyId !== 'all') {
            $surveyQuery->where('surveyhero_id', $surveyId);
        }
        $surveys = $surveyQuery->get();

        $mapping = [];
        foreach ($surveys as $surveyIndex => $survey) {
            try {
                $mapping['question_mapping'][$surveyIndex] = $this->mappingService->map($survey);
            } catch (\Exception $e) {
                $this->error($e->getMessage());

                return self::FAILURE;
            }
            $this->comment("Mapping for survey '$survey->name' completed!");
        }

        $fileName = 'surveyhero_mapping.php';
        $myfile = fopen($fileName, 'w') or exit('Unable to open file!');

        fwrite($myfile, "<?php \n\n" . $this->var_export_short($mapping) . "; \n");
        fclose($myfile);

        $this->comment("Mapping complete! [$fileName]");

        return self::SUCCESS;
    }

    private function var_export_short($data): string
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

        return $dump;
    }
}
