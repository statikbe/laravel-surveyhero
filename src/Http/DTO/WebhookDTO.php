<?php

namespace Statikbe\Surveyhero\Http\DTO;

use Saloon\Contracts\DataObjects\WithResponse;
use Saloon\Traits\Responses\HasResponse;

class WebhookDTO implements WithResponse
{
    use HasResponse;

    public function __construct(
        public readonly int $webhook_id,
        public readonly string $event_type,
        public readonly string $url,
        public readonly string $status,
        public readonly string $created_on
    ) {}

    public static function fromResponseObject(object $data): self
    {
        return new self(
            webhook_id: $data->webhook_id,
            event_type: $data->event_type,
            url: $data->url,
            status: $data->status,
            created_on: $data->created_on ?? ''
            created_on: $data->created_on
        );
    }
}
