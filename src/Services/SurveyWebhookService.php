<?php

namespace Statikbe\Surveyhero\Services;

use Statikbe\Surveyhero\Contracts\SurveyContract;
use Statikbe\Surveyhero\Models\Survey;

class SurveyWebhookService extends AbstractSurveyheroAPIService
{
    /**
     * Creates a webhook for Surveyhero to notify on the given event type.
     *
     * @param  SurveyContract  $survey
     * @return void
     *
     */
    public function createWebhook(SurveyContract $survey, string $eventType, string $url): void
    {
        $this->client->createWebhook($survey->surveyhero_id, $eventType, $url, 'active');
    }
}
