<?php

namespace Statikbe\Surveyhero\Http\DTO;

use Saloon\Contracts\DataObjects\WithResponse;
use Saloon\Traits\Responses\HasResponse;

class SurveyLanguageDTO implements WithResponse
{
    use HasResponse;

    public function __construct(
        public readonly string $code,
        public readonly string $label,
        public readonly bool $is_default,
        public readonly bool $is_active
    ) {}

    public static function fromResponseObject(object $data): self
    {
        return new self(
            code: $data->code,
            label: $data->label ?? $data->name ?? '',
            is_default: $data->is_default ?? false,
            is_active: $data->is_active ?? true
        );
    }
}
