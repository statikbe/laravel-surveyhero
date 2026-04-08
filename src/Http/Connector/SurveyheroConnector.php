<?php

namespace Statikbe\Surveyhero\Http\Connector;

use Saloon\Http\Auth\BasicAuthenticator;
use Saloon\Http\Connector;
use Saloon\Http\Response;
use Saloon\RateLimitPlugin\Contracts\RateLimitStore;
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
        $baseUrl = config('surveyhero.api_url');

        if (is_string($baseUrl) && trim($baseUrl) !== '') {
            return $baseUrl;
        }

        return 'https://api.surveyhero.com/v1/';
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
            Limit::allow(2)->everySeconds(1),
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

        // Check for Retry-After header (in seconds)
        $retryAfter = $response->header('Retry-After');

        if ($retryAfter !== null && is_numeric($retryAfter)) {
            $limit->exceeded(releaseInSeconds: (int) $retryAfter);
        } else {
            // Default: wait 1 second if no Retry-After header is provided
            $limit->exceeded(releaseInSeconds: 1);
        }
    }
}
