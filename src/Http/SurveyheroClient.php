<?php

namespace Statikbe\Surveyhero\Http;

use Carbon\Carbon;
use Statikbe\Surveyhero\Http\Connector\SurveyheroConnector;
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
use stdClass;

class SurveyheroClient
{
    private SurveyheroConnector $connector;

    public function __construct()
    {
        $this->connector = new SurveyheroConnector;
    }

    public function setConnector(SurveyheroConnector $connector): void
    {
        $this->connector = $connector;
    }

    public function getSurveys(): array
    {
        $response = $this->connector->send(new GetSurveysRequest);
        $response->throw();
        $data = $response->object();

        return $data ? $data->surveys : [];
    }

    public function getSurveyResponses(
        string|int $surveyId,
        ?Carbon $surveyLastUpdatedAt,
        array $collectorIds = []
    ): array {
        $response = $this->connector->send(
            new GetSurveyResponsesRequest($surveyId, $surveyLastUpdatedAt, $collectorIds)
        );
        $response->throw();
        $data = $response->object();

        return $data ? $data->responses : [];
    }

    public function getSurveyResponseAnswers(string|int $surveyId, string|int $responseId): ?stdClass
    {
        $response = $this->connector->send(new GetSurveyResponseAnswersRequest($surveyId, $responseId));

        return $response->successful() ? $response->object() : null;
    }

    public function getSurveyElements(string|int $surveyId, ?string $lang = null): array
    {
        $response = $this->connector->send(new GetSurveyElementsRequest($surveyId, $lang));
        $response->throw();
        $data = $response->object();

        return $data ? $data->elements : [];
    }

    public function getSurveyQuestions(string|int $surveyId, ?string $lang = null): array
    {
        $response = $this->connector->send(new GetSurveyQuestionsRequest($surveyId, $lang));
        $response->throw();
        $data = $response->object();

        return $data ? $data->elements : [];
    }

    public function getSurveyCollectors(string|int $surveyId): ?array
    {
        $response = $this->connector->send(new GetSurveyCollectorsRequest($surveyId));

        return $response->successful() ? $response->object()->collectors : null;
    }

    public function getSurveyLanguages(string|int $surveyId): array
    {
        $response = $this->connector->send(new GetSurveyLanguagesRequest($surveyId));
        $response->throw();
        $data = $response->object();

        return $data ? $data->languages : [];
    }

    public function getResumeLink(string|int $surveyId, string|int $responseId): ?string
    {
        $response = $this->connector->send(new GetResumeLinkRequest($surveyId, $responseId));

        return $response->successful() ? $response->object()->url : null;
    }

    public function listWebhooks(string|int $surveyId): ?array
    {
        $response = $this->connector->send(new ListWebhooksRequest($surveyId));

        return $response->successful() ? $response->object()->webhooks : null;
    }

    public function createWebhook(string|int $surveyId, string $eventType, string $url, string $status = 'active'): void
    {
        $response = $this->connector->send(new CreateWebhookRequest($surveyId, $eventType, $url, $status));
        $response->throw();
    }

    public function deleteWebhook(string|int $surveyId, string|int $webhookId): ?stdClass
    {
        $response = $this->connector->send(new DeleteWebhookRequest($surveyId, $webhookId));

        return $response->successful() ? $response->object() : null;
    }

    public function deleteResponse(string|int $surveyId, string|int $responseId): void
    {
        $response = $this->connector->send(new DeleteResponseRequest($surveyId, $responseId));
        $response->throw();
    }

    public function transformAPITimestamp(string $surveyheroTimestamp): Carbon
    {
        return Carbon::createFromFormat('Y-m-d\TH:i:s', substr($surveyheroTimestamp, 0, strpos($surveyheroTimestamp, '+')));
    }
}
