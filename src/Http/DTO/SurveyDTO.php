<?php

namespace Statikbe\Surveyhero\Http\DTO;

use Saloon\Contracts\DataObjects\WithResponse;
use Saloon\Traits\Responses\HasResponse;

class SurveyDTO implements WithResponse
{
    use HasResponse;

    public function __construct(
        public readonly int $surveyId,
        public readonly string $title,
        public readonly string $internalName,
        public readonly string $createdOn,
        public readonly int $numberOfQuestions,
        public readonly int $numberOfCollectors,
        public readonly int $numberOfResponses
    ) {}

    public static function fromResponseObject(object $data): self
    {
        return new self(
            surveyId: $data->survey_id,
            title: $data->title,
            internalName: $data->internal_name ?? '',
            createdOn: $data->created_on,
            numberOfQuestions: $data->number_of_questions,
            numberOfCollectors: $data->number_of_collectors,
            numberOfResponses: $data->number_of_responses
        );
    }
}
