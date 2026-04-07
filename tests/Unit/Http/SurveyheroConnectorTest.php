<?php

use Saloon\Http\Auth\BasicAuthenticator;
use Saloon\RateLimitPlugin\Limit;
use Statikbe\Surveyhero\Http\Connector\SurveyheroConnector;

it('resolves base url from config', function () {
    config()->set('surveyhero.api_url', 'https://custom.api.com/v2/');
    $connector = new SurveyheroConnector;

    expect($connector->resolveBaseUrl())->toBe('https://custom.api.com/v2/');
});

it('uses the default surveyhero api url when none is configured', function () {
    config()->set('surveyhero.api_url', 'https://api.surveyhero.com/v1/');
    $connector = new SurveyheroConnector;

    expect($connector->resolveBaseUrl())->toBe('https://api.surveyhero.com/v1/');
});

it('creates basic auth with configured credentials', function () {
    config()->set('surveyhero.api_username', 'my-user');
    config()->set('surveyhero.api_password', 'my-pass');
    $connector = new SurveyheroConnector;

    // The authenticator is returned via the public getAuthenticator() method
    $auth = $connector->getAuthenticator();

    expect($auth)->toBeInstanceOf(BasicAuthenticator::class);
});

it('resolves a rate limit of 2 requests per second', function () {
    $connector = new SurveyheroConnector;

    $reflector = new ReflectionMethod($connector, 'resolveLimits');
    $reflector->setAccessible(true);
    $limits = $reflector->invoke($connector);

    expect($limits)->toHaveCount(1)
        ->and($limits[0])->toBeInstanceOf(Limit::class);
});

it('has no default retry configuration (retries are per-request)', function () {
    $connector = new SurveyheroConnector;

    expect($connector->tries)->toBeNull()
        ->and($connector->retryInterval)->toBeNull();
});
