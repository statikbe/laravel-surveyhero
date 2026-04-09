<?php

use Saloon\Http\Auth\BasicAuthenticator;
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
