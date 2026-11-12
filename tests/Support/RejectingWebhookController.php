<?php

namespace Statikbe\Surveyhero\Tests\Support;

use Statikbe\Surveyhero\Exceptions\UnwantedResponseNotImportedException;
use Statikbe\Surveyhero\Http\Controllers\Api\SurveyheroWebhookController;
use Statikbe\Surveyhero\Models\Survey;

/**
 * A host-app style controller that discards every response in its pre-import hook.
 */
class RejectingWebhookController extends SurveyheroWebhookController
{
    /** @var array<int, array> */
    public static array $receivedResponseData = [];

    protected function handlePreImport(Survey $survey, array $collectors, array $responseData): void
    {
        static::$receivedResponseData[] = $responseData;

        throw UnwantedResponseNotImportedException::create($responseData, 'Rejected by the host app.');
    }
}
