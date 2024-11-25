<?php

namespace Statikbe\Surveyhero\Traits;

use Statikbe\Surveyhero\Services\SurveyMappingService;

trait HasCollectors
{
    private ?array $collectors = null;

    public function getCollectors(): array
    {
        if (! $this->collectors) {
            $surveyMappingService = new SurveyMappingService;
            $surveyMappingConfig = $surveyMappingService->getSurveyMappingFromConfig($this);
            $surveyCollectors = $this->collector_ids;

            //Take collectors from config if defined, otherwise use collectors from API
            $this->collectors = isset($surveyMappingConfig['collectors']) && ! empty($surveyMappingConfig['collectors']) ? $surveyMappingConfig['collectors'] : $surveyCollectors;
        }

        return $this->collectors;
    }
}
