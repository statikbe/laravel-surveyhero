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
        public readonly bool $isDefault,
        public readonly bool $isActive
    ) {}

    public static function fromResponseObject(object $data): self
    {
        return new self(
            name: $data->name,
            code: $data->code,
            isDefault: $data->is_default,
            isActive: $data->is_active
        );
    }
}
