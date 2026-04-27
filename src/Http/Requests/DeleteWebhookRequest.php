<?php

namespace Statikbe\Surveyhero\Http\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class DeleteWebhookRequest extends Request
{
    protected Method $method = Method::DELETE;

    public function __construct(
        private readonly string|int $surveyId,
        private readonly string|int $webhookId
    ) {}

    public function resolveEndpoint(): string
    {
        return "surveys/{$this->surveyId}/webhooks/{$this->webhookId}";
    }
}
