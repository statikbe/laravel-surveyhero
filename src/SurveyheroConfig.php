<?php

namespace Statikbe\Surveyhero;

class SurveyheroConfig
{
    const DEFAULT_API_URL = 'https://api.surveyhero.com/v1/';

    public function getApiUrl(): string
    {
        return config('surveyhero.api_url') ?: self::DEFAULT_API_URL;
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
