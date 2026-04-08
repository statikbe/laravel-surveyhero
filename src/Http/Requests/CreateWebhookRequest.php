<?php

namespace Statikbe\Surveyhero\Http\Requests;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

class CreateWebhookRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        private readonly string|int $surveyId,
        private readonly string $eventType,
        private readonly string $url,
        private readonly string $status = 'active'
    ) {}

    public function resolveEndpoint(): string
    {
        return "surveys/{$this->surveyId}/webhooks";
    }

    protected function defaultBody(): array
    {
        return [
            'event_type' => $this->eventType,
            'url' => $this->url,
            'status' => $this->status,
        ];
    }
}
