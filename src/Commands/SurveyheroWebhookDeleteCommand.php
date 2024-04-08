<?php

namespace Statikbe\Surveyhero\Commands;

use Illuminate\Console\Command;
use Statikbe\Surveyhero\Contracts\SurveyContract;
use Statikbe\Surveyhero\Services\SurveyWebhookService;
use Statikbe\Surveyhero\SurveyheroRegistrar;

class SurveyheroWebhookDeleteCommand extends Command
{
    public $signature = 'surveyhero:delete-webhook
                        {--survey= : The Surveyhero survey ID}
                        {--webhook= : The Surveyhero webhook ID}';

    public $description = 'Delete Surveyhero webhooks';

    private SurveyWebhookService $webhookService;

    public function __construct(SurveyWebhookService $webhookService)
    {
        parent::__construct();

        $this->webhookService = $webhookService;
    }

    public function handle(): int
    {
        $surveyId = trim($this->option('survey'));
        $webhookId = trim($this->option('webhook'));

        $surveys = app(SurveyheroRegistrar::class)->getSurveyClass()::query()->where('surveyhero_id', $surveyId)->get();

        foreach ($surveys as $survey) {
            /* @var SurveyContract $survey */
            try {
                $this->webhookService->deleteWebhook($survey, $webhookId);
                $this->comment('Webhook #'.$webhookId.' for survey #'.$surveyId.' deleted');
            } catch (\Exception $e) {
                $this->error($e->getMessage());

                return self::FAILURE;
            }
        }

        return self::SUCCESS;
    }
}
