<?php

use Saloon\Http\Auth\BasicAuthenticator;
use Statikbe\Surveyhero\Exceptions\InvalidConfigurationException;
use Statikbe\Surveyhero\Http\Connector\SurveyheroConnector;

it('resolves base url from config', function () {
    config()->set('surveyhero.api_url', 'https://custom.api.com/v2/');
    $connector = new SurveyheroConnector;

    expect($connector->resolveBaseUrl())->toBe('https://custom.api.com/v2/');
});

it('uses the default surveyhero api url when none is configured', function () {
    config()->set('surveyhero.api_url', null);
    $connector = new SurveyheroConnector;

    expect($connector->resolveBaseUrl())->toBe('https://api.surveyhero.com/v1/');
});

it('creates basic auth with configured credentials', function () {
    config()->set('surveyhero.api_username', 'my-user');
    config()->set('surveyhero.api_password', 'my-pass');
    $connector = new SurveyheroConnector;

    $auth = $connector->getAuthenticator();

    expect($auth)->toBeInstanceOf(BasicAuthenticator::class);
});

it('throws a configuration exception with a clear message when credentials are missing', function () {
    config()->set('surveyhero.api_username', null);
    config()->set('surveyhero.api_password', null);
    $connector = new SurveyheroConnector;

    expect(fn () => $connector->getAuthenticator())
        ->toThrow(
            InvalidConfigurationException::class,
            'The Surveyhero API credentials are not configured: surveyhero.api_username and surveyhero.api_password are empty. Set SURVEYHERO_API_USERNAME and SURVEYHERO_API_PASSWORD in your .env file. If the config file is missing, publish it with: php artisan vendor:publish --tag="laravel-surveyhero-config".'
        );
});

it('throws a configuration exception when only the password is missing', function () {
    config()->set('surveyhero.api_username', 'my-user');
    config()->set('surveyhero.api_password', '');
    $connector = new SurveyheroConnector;

    expect(fn () => $connector->getAuthenticator())
        ->toThrow(InvalidConfigurationException::class, 'surveyhero.api_password is empty');
});
