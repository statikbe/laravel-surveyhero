<?php

namespace Statikbe\Surveyhero\Http\DTO;

use Saloon\Contracts\DataObjects\WithResponse;
use Saloon\Traits\Responses\HasResponse;

class SurveyCollectorDTO implements WithResponse
{
    use HasResponse;

    public function __construct(
        public readonly int $collector_id,
        public readonly string $name,
        public readonly string $created_on,
        public readonly string $status,
        public readonly string $type,
        public readonly ?object $type_data = null,
        public readonly ?int $number_of_responses = null
    ) {}

    public static function fromResponseObject(object $data): self
    {
        // Extract type-specific data (e.g., survey_link, access_codes, etc.)
        $typeData = null;
        $type = $data->type ?? 'unknown';
        if (isset($data->{$type}) && is_object($data->{$type})) {
            $typeData = $data->{$type};
        }

        return new self(
            collector_id: $data->collector_id,
            name: $data->name ?? '',
            created_on: $data->created_on ?? '',
            status: $data->status ?? '',
            type: $type,
            type_data: $typeData,
            number_of_responses: $data->number_of_responses ?? null
        );
    }
}
