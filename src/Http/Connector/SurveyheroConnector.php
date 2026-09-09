<?php

namespace Statikbe\Surveyhero\Http\Connector;

use Illuminate\Http\Response as LaravelResponse;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Saloon\Http\Auth\BasicAuthenticator;
use Saloon\Http\Connector;
use Saloon\Http\Response;
use Saloon\RateLimitPlugin\Contracts\RateLimitStore;
use Saloon\RateLimitPlugin\Helpers\RetryAfterHelper;
use Saloon\RateLimitPlugin\Limit;
use Saloon\RateLimitPlugin\Stores\LaravelCacheStore;
use Saloon\RateLimitPlugin\Traits\HasRateLimits;
use Saloon\Traits\Plugins\AcceptsJson;
use Statikbe\Surveyhero\Exceptions\InvalidConfigurationException;
use Statikbe\Surveyhero\SurveyheroConfig;

class SurveyheroConnector extends Connector
{
    use AcceptsJson;
    use HasRateLimits;

    public function resolveBaseUrl(): string
    {
        return $this->surveyheroConfig()->getApiUrl();
    }

    /**
     * @throws InvalidConfigurationException when the API credentials are not configured.
     */
    protected function defaultAuth(): BasicAuthenticator
    {
        $config = $this->surveyheroConfig();
        $config->validateApiCredentials();

        return new BasicAuthenticator(
            (string) $config->getApiUsername(),
            (string) $config->getApiPassword()
        );
    }

    private function surveyheroConfig(): SurveyheroConfig
    {
        return new SurveyheroConfig;
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

    protected function getTooManyAttemptsLimiter(): ?Limit
    {
        return Limit::custom($this->handleTooManyAttempts(...))->sleep();
    }

    protected function handleTooManyAttempts(Response $response, Limit $limit): void
    {
        if ($response->status() !== LaravelResponse::HTTP_TOO_MANY_REQUESTS) { // HTTP 429
            return;
        }

        // Parse Retry-After header (handles both delta seconds and HTTP-date formats)
        // Falls back to configured seconds if no (parseable) header is provided
        $seconds = RetryAfterHelper::parse($response->header('Retry-After'))
            ?? Config::integer('surveyhero.rate_limit_fallback_seconds', 60);

        Log::warning('[surveyhero] Rate limited (429). Sleeping '.$seconds.'s before retry.');

        $limit->exceeded(releaseInSeconds: $seconds);
    }
}
