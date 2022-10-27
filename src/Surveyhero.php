<?php

namespace Statikbe\Surveyhero;

use Illuminate\Support\Facades\Route;
use Statikbe\Surveyhero\Http\Controllers\Api\SurveyheroWebhookController;

class Surveyhero
{
    public function webhookRoutes(): void
    {
        Route::post('/process-surveyhero-response-completed', [SurveyheroWebhookController::class, 'handleResponseCompletedWebhook'])
            ->name('surveyhero_response_webhook');
    }
}
