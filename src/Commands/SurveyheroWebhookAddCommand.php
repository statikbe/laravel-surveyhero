<?php

namespace Statikbe\Surveyhero\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use Statikbe\Surveyhero\Contracts\SurveyContract;
use Statikbe\Surveyhero\Services\SurveyWebhookService;
use Statikbe\Surveyhero\SurveyheroConfig;
use Statikbe\Surveyhero\SurveyheroRegistrar;

class SurveyheroWebhookAddCommand extends Command
{
    private const WEBHOOK_ROUTE = 'surveyhero_response_webhook';

    public $signature = 'surveyhero:add-webhooks
                        {--survey=all : The Surveyhero survey ID}
                        {--eventType=response.completed : Webhooks event type (https://developer.surveyhero.com/api/#webhooks-event-types)}
                        {--url= : The URL the webhook should call (default: the surveyhero_response_webhook route with the webhook secret)}';

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
        $url = trim((string) $this->option('url'));
        $eventType = trim($this->option('eventType'));

        if (! $url) {
            $url = $this->buildDefaultUrl();
            if (! $url) {
                return self::FAILURE;
            }
            $this->comment('Using webhook URL: '.$this->maskSecret($url));
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
                $this->error($this->maskSecret($e->getMessage()));

                return self::FAILURE;
            }
            $this->comment("generating Webhooks for survey '$survey->name' completed!");
        }

        $this->comment('Generating webhooks complete!');

        return self::SUCCESS;
    }

    /**
     * Builds the URL of the package's webhook route, with the webhook secret as token.
     */
    private function buildDefaultUrl(): ?string
    {
        $secret = app(SurveyheroConfig::class)->getWebhookSecret();
        if (! $secret) {
            $this->error('No webhook secret is configured. Set SURVEYHERO_WEBHOOK_SECRET, or pass the webhook URL with --url.');

            return null;
        }

        if (! Route::has(self::WEBHOOK_ROUTE)) {
            $this->error('The route "'.self::WEBHOOK_ROUTE.'" does not exist. Register it with Surveyhero::webhookRoutes(), or pass the webhook URL with --url.');

            return null;
        }

        return route(self::WEBHOOK_ROUTE, ['token' => $secret]);
    }

    private function maskSecret(string $text): string
    {
        $secret = app(SurveyheroConfig::class)->getWebhookSecret();
        if (! $secret) {
            return $text;
        }

        return str_replace([$secret, rawurlencode($secret)], '********', $text);
    }
}
