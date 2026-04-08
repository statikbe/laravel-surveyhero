<?php

namespace Statikbe\Surveyhero\Http\DTO;

use Saloon\Contracts\DataObjects\WithResponse;
use Saloon\Traits\Responses\HasResponse;

class SurveyDTO implements WithResponse
{
    use HasResponse;

    public function __construct(
        public readonly int $survey_id,
        public readonly string $title,
        public readonly string $internal_name,
        public readonly string $created_on,
        public readonly int $number_of_questions,
        public readonly int $number_of_collectors,
        public readonly int $number_of_responses
    ) {}

    public static function fromResponseObject(object $data): self
    {
        return new self(
            survey_id: $data->survey_id,
            title: $data->title,
            internal_name: $data->internal_name ?? '',
            created_on: $data->created_on ?? '',
            number_of_questions: $data->number_of_questions ?? 0,
            number_of_collectors: $data->number_of_collectors ?? 0,
            number_of_responses: $data->number_of_responses ?? 0
        );
    }
}
