<?php

namespace Statikbe\Surveyhero\Http\DTO;

use Saloon\Contracts\DataObjects\WithResponse;
use Saloon\Traits\Responses\HasResponse;

class SurveyElementDTO implements WithResponse
{
    use HasResponse;

    public function __construct(
        public readonly int $elementId,
        public readonly string $type,
        public readonly ?object $question = null,
        public readonly ?object $text = null,
        public readonly ?object $image = null,
        public readonly ?object $code = null,
        public readonly ?object $separator = null
    ) {}

    public static function fromResponseObject(object $data): self
    {
        return new self(
            elementId: $data->element_id,
            type: $data->type,
            question: $data->question ?? null,
            text: $data->text ?? null,
            image: $data->image ?? null,
            code: $data->code ?? null,
            separator: $data->separator ?? null
        );
    }
}
