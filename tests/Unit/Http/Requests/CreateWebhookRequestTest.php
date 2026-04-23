<?php

use Saloon\Enums\Method;
use Statikbe\Surveyhero\Http\Requests\CreateWebhookRequest;

it('resolves the correct endpoint', function () {
    $request = new CreateWebhookRequest(1234567, 'response_completed', 'https://example.com/hook');
    expect($request->resolveEndpoint())->toBe('surveys/1234567/webhooks');
});

it('uses the POST method', function () {
    $request = new CreateWebhookRequest(1234567, 'response_completed', 'https://example.com/hook');
    expect($request->getMethod())->toBe(Method::POST);
});

it('includes the correct body fields', function () {
    $request = new CreateWebhookRequest(1234567, 'response_completed', 'https://example.com/hook', 'inactive');
    $body = $request->body()->all();

    expect($body)->toMatchArray([
        'event_type' => 'response_completed',
        'url' => 'https://example.com/hook',
        'status' => 'inactive',
    ]);
});

it('defaults to active status', function () {
    $request = new CreateWebhookRequest(1234567, 'response_completed', 'https://example.com/hook');
    expect($request->body()->all()['status'])->toBe('active');
});
