<?php

namespace Statikbe\Surveyhero\Commands;

use Illuminate\Console\Command;
use Statikbe\Surveyhero\Contracts\SurveyContract;
use Statikbe\Surveyhero\Services\SurveyWebhookService;
use Statikbe\Surveyhero\SurveyheroRegistrar;

class SurveyheroWebhookAddCommand extends Command
{
    public $signature = 'surveyhero:add-webhooks
                        {--survey=all : The Surveyhero survey ID}
                        {--eventType=response.completed : Webhooks event type (https://developer.surveyhero.com/api/#webhooks-event-types)}
                        {--url= : The URL the webhook should call}';

    public $description = 'Automatically generate webhooks for survey responses on your desired URL.';

    private SurveyWebhookService $webhookService;

    public function __construct(SurveyWebhookService $webhookService)
    {
        parent::__construct();

        $this->webhookService = $webhookService;
    }

    public function handle(): int
    {
        $surveyId = trim($this->option('survey'));
        $url = trim($this->option('url'));
        $eventType = trim($this->option('eventType'));

        if (! $url) {
            $this->error('Please specify the webhook URL');

            return self::FAILURE;
        }
        if (! $eventType) {
            $this->error('Please specify the event type');

            return self::FAILURE;
        }

        $surveyQuery = app(SurveyheroRegistrar::class)->getSurveyClass()::query();
        if ($surveyId !== 'all') {
            $surveyQuery->where('surveyhero_id', $surveyId);
        }
        $surveys = $surveyQuery->get();

        foreach ($surveys as $surveyIndex => $survey) {
            /* @var SurveyContract $survey */
            try {
                $this->webhookService->createWebhook($survey, $eventType, $url);
            } catch (\Exception $e) {
                $this->error($e->getMessage());

                return self::FAILURE;
            }
            $this->comment("generating Webhooks for survey '$survey->name' completed!");
        }

        $this->comment('Generating webhooks complete!');

        return self::SUCCESS;
    }
}
