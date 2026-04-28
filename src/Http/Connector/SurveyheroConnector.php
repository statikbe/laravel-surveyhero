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
use Statikbe\Surveyhero\SurveyheroConfig;

class SurveyheroConnector extends Connector
{
    use AcceptsJson;
    use HasRateLimits;

    public function __construct(private readonly SurveyheroConfig $surveyheroConfig = new SurveyheroConfig)
    {
    }

    public function resolveBaseUrl(): string
    {
        return $this->surveyheroConfig->getApiUrl();
    }

    protected function defaultAuth(): BasicAuthenticator
    {
        return new BasicAuthenticator(
            $this->surveyheroConfig->getApiUsername(),
            $this->surveyheroConfig->getApiPassword()
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

    protected function getTooManyAttemptsLimiter(): ?Limit
    {
        return Limit::custom($this->handleTooManyAttempts(...))->sleep();
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
                $this->surveyheroConfig->getRateLimitFallbackSeconds()
            ),
        );
    }
}
