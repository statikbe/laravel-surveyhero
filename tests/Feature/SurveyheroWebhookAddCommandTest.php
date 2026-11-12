<?php

use Illuminate\Support\Facades\Route;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\Request;
use Statikbe\Surveyhero\Facades\Surveyhero;
use Statikbe\Surveyhero\Http\Requests\CreateWebhookRequest;
use Statikbe\Surveyhero\Models\Survey;

beforeEach(function () {
    [, $this->mockClient] = $this->makeSurveyheroClient([
        CreateWebhookRequest::class => MockResponse::fixture('create-webhook'),
    ]);

    Survey::factory()->create(['surveyhero_id' => 1234567, 'name' => 'Test survey']);
});

function registerWebhookRoutes(): void
{
    Surveyhero::webhookRoutes();
    Route::getRoutes()->refreshNameLookups();
}

it('creates the webhook with the tokenised route URL when no --url is given', function () {
    config()->set('surveyhero.webhook_secret', 'super-secret-token');
    registerWebhookRoutes();

    $this->artisan('surveyhero:add-webhooks')
        ->expectsOutputToContain('/process-surveyhero-response-completed/********')
        ->doesntExpectOutputToContain('super-secret-token')
        ->assertSuccessful();

    $this->mockClient->assertSent(function (Request $request) {
        return $request instanceof CreateWebhookRequest
            && $request->body()->all()['url'] === url('/process-surveyhero-response-completed/super-secret-token')
            && $request->body()->all()['event_type'] === 'response.completed';
    });
});

it('appends the token as query parameter for a hand-registered route without {token}', function () {
    config()->set('surveyhero.webhook_secret', 'super-secret-token');
    Route::post('/my-webhook', fn () => null)->name('surveyhero_response_webhook');
    Route::getRoutes()->refreshNameLookups();

    $this->artisan('surveyhero:add-webhooks')->assertSuccessful();

    $this->mockClient->assertSent(fn (Request $request) => $request->body()->all()['url'] === url('/my-webhook?token=super-secret-token'));
});

it('fails without a webhook secret when no --url is given', function () {
    config()->set('surveyhero.webhook_secret', null);
    registerWebhookRoutes();

    $this->artisan('surveyhero:add-webhooks')
        ->expectsOutputToContain('No webhook secret is configured')
        ->assertFailed();

    $this->mockClient->assertNothingSent();
});

it('fails when the webhook route is not registered and no --url is given', function () {
    config()->set('surveyhero.webhook_secret', 'super-secret-token');

    $this->artisan('surveyhero:add-webhooks')
        ->expectsOutputToContain('The route "surveyhero_response_webhook" does not exist')
        ->assertFailed();

    $this->mockClient->assertNothingSent();
});

it('uses the --url option as-is', function () {
    config()->set('surveyhero.webhook_secret', null);

    $this->artisan('surveyhero:add-webhooks', ['--url' => 'https://elsewhere.example.com/hook'])
        ->assertSuccessful();

    $this->mockClient->assertSent(fn (Request $request) => $request->body()->all()['url'] === 'https://elsewhere.example.com/hook');
});
