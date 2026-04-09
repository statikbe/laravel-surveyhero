<?php

namespace Statikbe\Surveyhero;

class SurveyheroConfig
{
    public function getApiUrl(): string
    {
        return config('surveyhero.api_url') ?: 'https://api.surveyhero.com/v1/';
    }

    public function getApiUsername(): ?string
    {
        return config('surveyhero.api_username');
    }

    public function getApiPassword(): ?string
    {
        return config('surveyhero.api_password');
    }

    public function getRateLimitFallbackSeconds(): int
    {
        return (int) (config('surveyhero.rate_limit_fallback_seconds') ?? 60);
    }

    public function getQuestionMapping(): array
    {
        return config('surveyhero.question_mapping') ?? [];
    }

    public function getLinkParametersMapping(): array
    {
        return config('surveyhero.surveyhero_link_parameters_mapping') ?? [];
    }
}
