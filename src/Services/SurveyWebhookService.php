<?php

namespace Statikbe\Surveyhero\Services;

use Statikbe\Surveyhero\Contracts\SurveyContract;
use Statikbe\Surveyhero\Models\Survey;

class SurveyWebhookService extends AbstractSurveyheroAPIService
{
    /**
     * Creates a basic question mapping based on the API to kickstart the configuration.
     *
     * @param  SurveyContract  $survey
     * @return array
     *
     * @see SurveyheroMapperCommand
     */
    public function generateWebhook(Survey $survey, string $eventType, string $url)
    {
        $this->client->createWebhook($survey->surveyhero_id, $eventType, $url, 'active');
    }
}
