<?php

namespace Statikbe\Surveyhero\Http\DTO;

use Saloon\Contracts\DataObjects\WithResponse;
use Saloon\Traits\Responses\HasResponse;

class WebhookDTO implements WithResponse
{
    use HasResponse;

    public function __construct(
        public readonly int $webhookId,
        public readonly string $eventType,
        public readonly string $url,
        public readonly string $status,
        public readonly string $createdOn
    ) {}

    public static function fromResponseObject(object $data): self
    {
        return new self(
            webhookId: $data->webhook_id,
            eventType: $data->event_type,
            url: $data->url,
            status: $data->status,
            createdOn: $data->created_on
        );
    }
}
