<?php

use Statikbe\Surveyhero\SurveyheroConfig;

it('returns the configured API URL', function () {
    config()->set('surveyhero.api_url', 'https://custom.api.com/v2/');

    expect((new SurveyheroConfig)->getApiUrl())->toBe('https://custom.api.com/v2/');
});

it('returns the default API URL when config is null', function () {
    config()->set('surveyhero.api_url', null);

    expect((new SurveyheroConfig)->getApiUrl())->toBe('https://api.surveyhero.com/v1/');
});

it('returns the configured API username', function () {
    config()->set('surveyhero.api_username', 'my-user');

    expect((new SurveyheroConfig)->getApiUsername())->toBe('my-user');
});

it('returns the configured API password', function () {
    config()->set('surveyhero.api_password', 'my-pass');

    expect((new SurveyheroConfig)->getApiPassword())->toBe('my-pass');
});

it('returns the configured rate limit fallback seconds', function () {
    config()->set('surveyhero.rate_limit_fallback_seconds', 30);

    expect((new SurveyheroConfig)->getRateLimitFallbackSeconds())->toBe(30);
});

it('returns the default rate limit fallback seconds when not configured', function () {
    config()->set('surveyhero.rate_limit_fallback_seconds', null);

    expect((new SurveyheroConfig)->getRateLimitFallbackSeconds())->toBe(60);
});

it('returns the question mapping', function () {
    $mapping = [['survey_id' => 123, 'questions' => []]];
    config()->set('surveyhero.question_mapping', $mapping);

    expect((new SurveyheroConfig)->getQuestionMapping())->toBe($mapping);
});

it('returns an empty array for question mapping when not configured', function () {
    config()->set('surveyhero.question_mapping', null);

    expect((new SurveyheroConfig)->getQuestionMapping())->toBe([]);
});

it('returns the link parameters mapping', function () {
    $mapping = ['user_uuid' => ['name' => 'user_id']];
    config()->set('surveyhero.surveyhero_link_parameters_mapping', $mapping);

    expect((new SurveyheroConfig)->getLinkParametersMapping())->toBe($mapping);
});

it('returns an empty array for link parameters mapping when not configured', function () {
    config()->set('surveyhero.surveyhero_link_parameters_mapping', null);

    expect((new SurveyheroConfig)->getLinkParametersMapping())->toBe([]);
});
