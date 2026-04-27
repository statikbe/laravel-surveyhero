<?php

use Carbon\Carbon;
use Saloon\Exceptions\Request\RequestException;
use Saloon\Http\Faking\MockResponse;
use Statikbe\Surveyhero\Http\Requests\CreateWebhookRequest;
use Statikbe\Surveyhero\Http\Requests\DeleteResponseRequest;
use Statikbe\Surveyhero\Http\Requests\DeleteWebhookRequest;
use Statikbe\Surveyhero\Http\Requests\GetResumeLinkRequest;
use Statikbe\Surveyhero\Http\Requests\GetSurveyCollectorsRequest;
use Statikbe\Surveyhero\Http\Requests\GetSurveyElementsRequest;
use Statikbe\Surveyhero\Http\Requests\GetSurveyLanguagesRequest;
use Statikbe\Surveyhero\Http\Requests\GetSurveyQuestionsRequest;
use Statikbe\Surveyhero\Http\Requests\GetSurveyResponseAnswersRequest;
use Statikbe\Surveyhero\Http\Requests\GetSurveyResponsesRequest;
use Statikbe\Surveyhero\Http\Requests\GetSurveysRequest;
use Statikbe\Surveyhero\Http\Requests\ListWebhooksRequest;
use Statikbe\Surveyhero\Http\SurveyheroClient;
use Statikbe\Surveyhero\Http\Connector\SurveyheroConnector;

it('returns an array of surveys', function () {
    [$client, $mockClient] = $this->makeSurveyheroClient([
        GetSurveysRequest::class => MockResponse::fixture('get-surveys'),
    ]);

    $surveys = $client->getSurveys();

    expect($surveys)->toBeArray()
        ->and($surveys[0]->survey_id)->toBe(1234567);
    $mockClient->assertSent(GetSurveysRequest::class);
});

it('returns an empty array when no surveys found', function () {
    [$client] = $this->makeSurveyheroClient([
        GetSurveysRequest::class => MockResponse::make(['surveys' => []], 200),
    ]);

    expect($client->getSurveys())->toBeArray()->toBeEmpty();
});

it('returns survey responses for a survey', function () {
    [$client, $mockClient] = $this->makeSurveyheroClient([
        GetSurveyResponsesRequest::class => MockResponse::fixture('get-survey-responses'),
    ]);

    $responses = $client->getSurveyResponses(1234567, null);

    expect($responses)->toBeArray()
        ->and($responses[0]->response_id)->toBe(9001);
    $mockClient->assertSent(GetSurveyResponsesRequest::class);
});

it('returns survey response answers', function () {
    [$client, $mockClient] = $this->makeSurveyheroClient([
        GetSurveyResponseAnswersRequest::class => MockResponse::fixture('get-survey-response-answers'),
    ]);

    $answers = $client->getSurveyResponseAnswers(1234567, 9001);

    expect($answers)->toBeObject()
        ->and($answers->response_id)->toBe(9001)
        ->and($answers->answers)->toBeArray();
    $mockClient->assertSent(GetSurveyResponseAnswersRequest::class);
});

it('returns null for survey response answers on 404', function () {
    [$client] = $this->makeSurveyheroClient([
        GetSurveyResponseAnswersRequest::class => MockResponse::make([], 404),
    ]);

    expect($client->getSurveyResponseAnswers(1234567, 9999))->toBeNull();
});

it('returns survey elements', function () {
    [$client, $mockClient] = $this->makeSurveyheroClient([
        GetSurveyElementsRequest::class => MockResponse::fixture('get-survey-elements'),
    ]);

    $elements = $client->getSurveyElements(1234567);

    expect($elements)->toBeArray()->not->toBeEmpty();
    $mockClient->assertSent(GetSurveyElementsRequest::class);
});

it('returns survey questions', function () {
    [$client, $mockClient] = $this->makeSurveyheroClient([
        GetSurveyQuestionsRequest::class => MockResponse::fixture('get-survey-questions'),
    ]);

    $questions = $client->getSurveyQuestions(1234567, 'en');

    expect($questions)->toBeArray()->not->toBeEmpty();
    $mockClient->assertSent(GetSurveyQuestionsRequest::class);
});

it('returns survey collectors', function () {
    [$client, $mockClient] = $this->makeSurveyheroClient([
        GetSurveyCollectorsRequest::class => MockResponse::fixture('get-survey-collectors'),
    ]);

    $collectors = $client->getSurveyCollectors(1234567);

    expect($collectors)->toBeArray()
        ->and($collectors[0]->collector_id)->toBe(9876543);
    $mockClient->assertSent(GetSurveyCollectorsRequest::class);
});

it('throws on collectors failure', function () {
    [$client] = $this->makeSurveyheroClient([
        GetSurveyCollectorsRequest::class => MockResponse::make([], 500),
    ]);

    expect(fn () => $client->getSurveyCollectors(1234567))->toThrow(RequestException::class);
});

it('returns survey languages', function () {
    [$client, $mockClient] = $this->makeSurveyheroClient([
        GetSurveyLanguagesRequest::class => MockResponse::fixture('get-survey-languages'),
    ]);

    $languages = $client->getSurveyLanguages(1234567);

    expect($languages)->toBeArray()
        ->and($languages[0]->code)->toBe('en');
    $mockClient->assertSent(GetSurveyLanguagesRequest::class);
});

it('returns resume link url', function () {
    [$client, $mockClient] = $this->makeSurveyheroClient([
        GetResumeLinkRequest::class => MockResponse::fixture('get-resume-link'),
    ]);

    $url = $client->getResumeLink(1234567, 9001);

    expect($url)->toBe('https://surveyhero.com/r/abc123def456');
    $mockClient->assertSent(GetResumeLinkRequest::class);
});

it('returns null for resume link on failure', function () {
    [$client] = $this->makeSurveyheroClient([
        GetResumeLinkRequest::class => MockResponse::make([], 404),
    ]);

    expect($client->getResumeLink(1234567, 9001))->toBeNull();
});

it('returns webhooks', function () {
    [$client, $mockClient] = $this->makeSurveyheroClient([
        ListWebhooksRequest::class => MockResponse::fixture('list-webhooks'),
    ]);

    $webhooks = $client->listWebhooks(1234567);

    expect($webhooks)->toBeArray()
        ->and($webhooks[0]->webhook_id)->toBe(555);
    $mockClient->assertSent(ListWebhooksRequest::class);
});

it('creates a webhook', function () {
    [$client, $mockClient] = $this->makeSurveyheroClient([
        CreateWebhookRequest::class => MockResponse::fixture('create-webhook'),
    ]);

    $client->createWebhook(1234567, 'response_completed', 'https://example.com/hook');

    $mockClient->assertSent(CreateWebhookRequest::class);
});

it('deletes a webhook', function () {
    [$client, $mockClient] = $this->makeSurveyheroClient([
        DeleteWebhookRequest::class => MockResponse::fixture('delete-webhook'),
    ]);

    $client->deleteWebhook(1234567, 555);

    $mockClient->assertSent(DeleteWebhookRequest::class);
});

it('deletes a response', function () {
    [$client, $mockClient] = $this->makeSurveyheroClient([
        DeleteResponseRequest::class => MockResponse::fixture('delete-response'),
    ]);

    $client->deleteResponse(1234567, 9001);

    $mockClient->assertSent(DeleteResponseRequest::class);
});

it('transforms a surveyhero timestamp to a Carbon instance', function () {
    $client = new SurveyheroClient(new SurveyheroConnector);
    $carbon = $client->transformAPITimestamp('2024-06-01T11:00:00+00:00');

    expect($carbon)->toBeInstanceOf(Carbon::class)
        ->and($carbon->format('Y-m-d H:i:s'))->toBe('2024-06-01 11:00:00');
});
