<?php

namespace Statikbe\Surveyhero\Http\DTO;

use Carbon\Carbon;
use Saloon\Contracts\DataObjects\WithResponse;
use Saloon\Traits\Responses\HasResponse;

class SurveyResponseAnswersDTO implements WithResponse
{
    use HasResponse;

    public function __construct(
        public readonly int $responseId,
        public readonly int $collectorId,
        public readonly int $surveyId,
        public readonly Carbon $startedOn,
        public readonly Carbon $lastUpdatedOn,
        public readonly ?string $emailAddress,
        public readonly ?object $recipientData,
        public readonly ?object $linkParameters,
        public readonly ?object $language,
        public readonly ?string $ipAddress,
        public readonly object $metaData,
        public readonly string $status,
        public readonly array $answers
    ) {}

    public static function fromResponseObject(object $data): self
    {
        return new self(
            responseId: $data->response_id,
            collectorId: $data->collector_id,
            surveyId: $data->survey_id,
            startedOn: Carbon::parse($data->started_on),
            lastUpdatedOn: Carbon::parse($data->last_updated_on),
            emailAddress: $data->email_address ?? null,
            recipientData: $data->recipient_data ?? null,
            linkParameters: $data->link_parameters ?? null,
            language: $data->language ?? null,
            ipAddress: $data->ip_address ?? null,
            metaData: $data->meta_data,
            status: $data->status,
            answers: $data->answers ?? []
        );
    }
}
