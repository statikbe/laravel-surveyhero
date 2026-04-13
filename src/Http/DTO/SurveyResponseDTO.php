<?php

namespace Statikbe\Surveyhero\Http\DTO;

use Saloon\Contracts\DataObjects\WithResponse;
use Saloon\Traits\Responses\HasResponse;

class SurveyResponseDTO implements WithResponse
{
    use HasResponse;

    public function __construct(
        public readonly int $response_id,
        public readonly int $collector_id,
        public readonly int $survey_id,
        public readonly string $started_on,
        public readonly string $last_updated_on,
        public readonly ?string $email_address,
        public readonly ?object $recipient_data,
        public readonly ?object $link_parameters,
        public readonly ?object $language,
        public readonly ?string $ip_address,
        public readonly object $meta_data,
        public readonly string $status
    ) {}

    public static function fromResponseObject(object $data): self
    {
        return new self(
            response_id: $data->response_id,
            collector_id: $data->collector_id ?? 0,
            survey_id: $data->survey_id ?? 0,
            started_on: $data->started_on,
            last_updated_on: $data->last_updated_on,
            email_address: $data->email_address ?? null,
            recipient_data: $data->recipient_data ?? null,
            link_parameters: $data->link_parameters ?? null,
            language: $data->language ?? null,
            ip_address: $data->ip_address ?? null,
            meta_data: $data->meta_data ?? (object) [],
            status: $data->status
        );
    }
}
