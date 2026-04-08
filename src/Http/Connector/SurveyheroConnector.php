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

    /**
     * The number of times a request should be retried if a failure response is returned.
     */
    public ?int $tries = 3;

    /**
     * The interval in milliseconds Saloon should wait between retries.
     */
    public ?int $retryInterval = 500;

    /**
     * Should Saloon use exponential backoff during retries?
     */
    public ?bool $useExponentialBackoff = true;

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
        sleep(1);
    }
}
