<?php

namespace Statikbe\Surveyhero\Http\DTO;

use Saloon\Contracts\DataObjects\WithResponse;
use Saloon\Traits\Responses\HasResponse;

class SurveyLanguageDTO implements WithResponse
{
    use HasResponse;

    public function __construct(
        public readonly string $name,
        public readonly string $code,
        public readonly bool $is_default,
        public readonly bool $is_active
    ) {}

    public static function fromResponseObject(object $data): self
    {
        return new self(
            name: $data->name,
            code: $data->code,
            is_default: $data->is_default,
            is_active: $data->is_active
        );
    }
}
