<?php

namespace Statikbe\Surveyhero\Http\DTO;

use Saloon\Contracts\DataObjects\WithResponse;
use Saloon\Traits\Responses\HasResponse;

class SurveyResponseDTO implements WithResponse
{
    use HasResponse;

    public function __construct(
        public readonly int $responseId,
        public readonly int $collectorId,
        public readonly int $surveyId,
        public readonly string $startedOn,
        public readonly string $lastUpdatedOn,
        public readonly ?string $emailAddress,
        public readonly ?object $recipientData,
        public readonly ?object $linkParameters,
        public readonly ?object $language,
        public readonly ?string $ipAddress,
        public readonly object $metaData,
        public readonly string $status
    ) {}

    public static function fromResponseObject(object $data): self
    {
        return new self(
            responseId: $data->response_id,
            collectorId: $data->collector_id,
            surveyId: $data->survey_id,
            startedOn: $data->started_on,
            lastUpdatedOn: $data->last_updated_on,
            emailAddress: $data->email_address ?? null,
            recipientData: $data->recipient_data ?? null,
            linkParameters: $data->link_parameters ?? null,
            language: $data->language ?? null,
            ipAddress: $data->ip_address ?? null,
            metaData: $data->meta_data,
            status: $data->status
        );
    }
}
