<?php

namespace Statikbe\Surveyhero\Services;

use Statikbe\Surveyhero\Contracts\SurveyContract;

class SurveyWebhookService extends AbstractSurveyheroAPIService
{
    /**
     * Lists all webhooks for a certain Surveyhero survey
     *
     * @return void
     */
    public function listWebhooks(SurveyContract $survey): ?array
    {
        return $this->client->listWebhooks($survey->surveyhero_id);
    }

    /**
     * Creates a webhook for Surveyhero to notify on the given event type.
     */
    public function createWebhook(SurveyContract $survey, string $eventType, string $url): void
    {
        $this->client->createWebhook($survey->surveyhero_id, $eventType, $url, 'active');
    }

    /**
     * Deletes a webhooks for a certain Surveyhero survey
     */
    public function deleteWebhook(SurveyContract $survey, string|int $webhookId): void
    {
        $this->client->deleteWebhook($survey->surveyhero_id, $webhookId);
    }
}
