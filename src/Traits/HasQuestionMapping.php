<?php

namespace Statikbe\Surveyhero\Traits;

use Statikbe\Surveyhero\Services\SurveyMappingService;

trait HasQuestionMapping
{
    private ?array $mergedQuestionMapping = null;

    public function getQuestionMapping(): array
    {
        if (! $this->mergedQuestionMapping) {
            $surveyMappingService = new SurveyMappingService;
            $surveyMappingConfig = $surveyMappingService->getSurveyMappingFromConfig($this);
            $surveyMappingDatabase = $this->question_mapping;

            //Merge database mapping with config mapping. Config mapping gets priority.
            $this->mergedQuestionMapping = array_replace_recursive($surveyMappingDatabase ?? [], $surveyMappingConfig['questions'] ?? []);
        }

        return $this->mergedQuestionMapping;
    }
}
