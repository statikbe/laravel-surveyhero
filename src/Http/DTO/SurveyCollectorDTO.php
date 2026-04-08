<?php

namespace Statikbe\Surveyhero\Http\DTO;

use Saloon\Contracts\DataObjects\WithResponse;
use Saloon\Traits\Responses\HasResponse;

class SurveyCollectorDTO implements WithResponse
{
    use HasResponse;

    public function __construct(
        public readonly int $collectorId,
        public readonly string $name,
        public readonly string $createdOn,
        public readonly string $status,
        public readonly string $type,
        public readonly ?object $typeData = null,
        public readonly ?int $numberOfResponses = null
    ) {}

    public static function fromResponseObject(object $data): self
    {
        // Extract type-specific data (e.g., survey_link, access_codes, etc.)
        $typeData = null;
        if (isset($data->{$data->type}) && is_object($data->{$data->type})) {
            $typeData = $data->{$data->type};
        }

        return new self(
            collectorId: $data->collector_id,
            name: $data->name,
            createdOn: $data->created_on,
            status: $data->status,
            type: $data->type,
            typeData: $typeData,
            numberOfResponses: $data->number_of_responses ?? null
        );
    }
}
