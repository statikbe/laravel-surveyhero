<?php

namespace Statikbe\Surveyhero\Http\Connector;

use Saloon\Http\Auth\BasicAuthenticator;
use Saloon\Http\Connector;
use Saloon\Http\Response;
use Saloon\RateLimitPlugin\Contracts\RateLimitStore;
use Saloon\RateLimitPlugin\Helpers\RetryAfterHelper;
use Saloon\RateLimitPlugin\Limit;
use Saloon\RateLimitPlugin\Stores\LaravelCacheStore;
use Saloon\RateLimitPlugin\Traits\HasRateLimits;
use Saloon\Traits\Plugins\AcceptsJson;

class SurveyheroConnector extends Connector
{
    use AcceptsJson;
    use HasRateLimits;

    public function resolveBaseUrl(): string
    {
        return config('surveyhero.api_url') ?: 'https://api.surveyhero.com/v1/';
    }

    protected function defaultAuth(): BasicAuthenticator
    {
        return new BasicAuthenticator(
            config('surveyhero.api_username'),
            config('surveyhero.api_password')
        );
    }

    protected function resolveLimits(): array
    {
        return [
            Limit::allow(2)->everySeconds(1)->sleep(),
        ];
    }

    protected function resolveRateLimitStore(): RateLimitStore
    {
        return new LaravelCacheStore(cache()->store());
    }

    protected function handleTooManyAttempts(Response $response, Limit $limit): void
    {
        if ($response->status() !== 429) {
            return;
        }

        // Parse Retry-After header (handles both delta seconds and HTTP-date formats)
        // Falls back to configured seconds if no header is provided
        $limit->exceeded(
            releaseInSeconds: RetryAfterHelper::parse(
                $response->header('Retry-After'),
                config('surveyhero.rate_limit_fallback_seconds', 60)
            ),
        );
    }
}
