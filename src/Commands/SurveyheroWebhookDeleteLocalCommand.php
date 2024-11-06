<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Statikbe\Surveyhero\Contracts\SurveyContract;
use Statikbe\Surveyhero\Services\SurveyWebhookService;
use Statikbe\Surveyhero\SurveyheroRegistrar;

class SurveyheroWebhookDeleteLocalCommand extends Command
{
    public $signature = 'surveyhero:delete-local-webhooks
                        {--survey=all : The Surveyhero survey ID}
                        {--url=ngrok : The part of the url of the local URLs}';

    public $description = 'Delete all local webhooks for Surveyhero surveys';

    private SurveyWebhookService $webhookService;

    public function __construct(SurveyWebhookService $webhookService)
    {
        parent::__construct();

        $this->webhookService = $webhookService;
    }

    public function handle(): int
    {
        $surveyId = trim($this->option('survey'));
        $urlPart = trim($this->option('url'));

        $surveyQuery = app(SurveyheroRegistrar::class)->getSurveyClass()::query();
        if ($surveyId !== 'all') {
            $surveyQuery->where('surveyhero_id', $surveyId);
        }
        $surveys = $surveyQuery->get();

        foreach ($surveys as $survey) {
            /* @var SurveyContract $survey */
            try {
                $webhooks = $this->webhookService->listWebhooks($survey);
                $webhooksDeleted = 0;
                foreach ($webhooks as $webhook) {
                    $host = parse_url($webhook->url)['host'];
                    if (Str::contains($host, $urlPart)) {
                        $this->webhookService->deleteWebhook($survey, $webhook->webhook_id);
                        $webhooksDeleted++;
                    }
                }
                $this->comment("Deleted local webhooks for survey '$survey->name' ($survey->surveyhero_id): $webhooksDeleted");
            } catch (\Exception $e) {
                $this->error($e->getMessage());

                return self::FAILURE;
            }
        }

        return self::SUCCESS;
    }
}
