<?php

namespace Statikbe\Surveyhero;

use Statikbe\Surveyhero\Exceptions\InvalidConfigurationException;

class SurveyheroConfig
{
    const DEFAULT_API_URL = 'https://api.surveyhero.com/v1/';

    /**
     * The config keys that must be filled in before any API request can be made,
     * mapped to the environment variable that sets them.
     */
    private const REQUIRED_API_CREDENTIALS = [
        'api_username' => 'SURVEYHERO_API_USERNAME',
        'api_password' => 'SURVEYHERO_API_PASSWORD',
    ];

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

    /**
     * Checks that the API credentials are configured, so a request can actually be authenticated.
     *
     * @throws InvalidConfigurationException
     */
    public function validateApiCredentials(): void
    {
        $missingKeys = [];
        foreach (self::REQUIRED_API_CREDENTIALS as $configKey => $envKey) {
            if (blank(config("surveyhero.$configKey"))) {
                $missingKeys[$configKey] = $envKey;
            }
        }

        if (! empty($missingKeys)) {
            throw InvalidConfigurationException::missingApiCredentials($missingKeys);
        }
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
