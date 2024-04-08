<?php

namespace Statikbe\Surveyhero\Commands;

use Illuminate\Console\Command;
use Statikbe\Surveyhero\Contracts\SurveyContract;
use Statikbe\Surveyhero\Services\SurveyWebhookService;
use Statikbe\Surveyhero\SurveyheroRegistrar;

class SurveyheroWebhookListCommand extends Command
{
    public $signature = 'surveyhero:list-webhooks
                        {--survey=all : The Surveyhero survey ID}';

    public $description = 'List webhooks for Surveyhero surveys';

    private SurveyWebhookService $webhookService;

    public function __construct(SurveyWebhookService $webhookService)
    {
        parent::__construct();

        $this->webhookService = $webhookService;
    }

    public function handle(): int
    {
        $surveyId = trim($this->option('survey'));

        $surveyQuery = app(SurveyheroRegistrar::class)->getSurveyClass()::query();
        if ($surveyId !== 'all') {
            $surveyQuery->where('surveyhero_id', $surveyId);
        }
        $surveys = $surveyQuery->get();

        foreach ($surveys as $survey) {
            $this->comment("Webhooks for survey '$survey->name' ($survey->surveyhero_id):");
            /* @var SurveyContract $survey */
            try {
                $webhooks = $this->webhookService->listWebhooks($survey);
                $webhookData = [];
                foreach ($webhooks as $webhook) {
                    $webhookData[] = [
                        $webhook->webhook_id,
                        $webhook->event_type,
                        $webhook->url,
                        $webhook->status,
                        $webhook->created_on,
                    ];
                }
                $this->table(
                    ['Webhook id', 'Event type', 'Url', 'Status', 'Created'],
                    $webhookData
                );
            } catch (\Exception $e) {
                $this->error($e->getMessage());

                return self::FAILURE;
            }
        }

        return self::SUCCESS;
    }
}
