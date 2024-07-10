<?php

namespace Statikbe\Surveyhero\Http;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use stdClass;

class SurveyheroClient
{
    const CACHE_LATEST_REQUEST_TIME_KEY = 'latest-surveyhero-api-request-time';

    public function getSurveys(): array
    {
        $responsesData = $this->fetchFromSurveyHero('surveys');
        $surveys = json_decode($responsesData->body());

        return $surveys ? $surveys->surveys : [];
    }

    public function getSurveyResponses(
        string|int $surveyId,
        ?Carbon $surveyLastUpdatedAt,
        array $collectorIds = []
    ): array {
        $url = sprintf('surveys/%s/responses', $surveyId);
        $queryStringArgs = [];
        if ($surveyLastUpdatedAt) {
            $queryStringArgs['last_updated_on[from]'] = $surveyLastUpdatedAt->toIso8601String();
        }
        if (! empty($collectorIds)) {
            $queryStringArgs['collector_id'] = $collectorIds;
        }

        $responsesData = $this->fetchFromSurveyHero($url, $queryStringArgs);
        $responses = json_decode($responsesData->body());

        return $responses ? $responses->responses : [];
    }

    public function getSurveyResponseAnswers(string|int $surveyId, string|int $responseId): ?\stdClass
    {
        $answerData = $this->fetchFromSurveyHero(sprintf('surveys/%s/responses/%s', $surveyId, $responseId));

        return $answerData->successful() ? json_decode($answerData->body()) : null;
    }

    public function getSurveyElements(string|int $surveyId, ?string $lang = null): array
    {
        $questionsData = $this->fetchFromSurveyHero(sprintf('surveys/%s/elements%s', $surveyId, $lang ? '?lang='.$lang : null));
        $questions = json_decode($questionsData->body());

        return $questions ? $questions->elements : [];
    }

    public function getSurveyQuestions(string|int $surveyId, ?string $lang = null): array
    {
        $questionsData = $this->fetchFromSurveyHero(sprintf('surveys/%s/questions%s', $surveyId, $lang ? '?lang='.$lang : null));
        $questions = json_decode($questionsData->body());

        return $questions ? $questions->elements : [];
    }

    public function getSurveyCollectors(string|int $surveyId): ?array
    {
        $collectorData = $this->fetchFromSurveyHero(sprintf('surveys/%s/collectors', $surveyId));

        return $collectorData->successful() ? json_decode($collectorData->body())->collectors : null;
    }

    public function getSurveyLanguages(string|int $surveyId): array
    {
        $questionsData = $this->fetchFromSurveyHero(sprintf('surveys/%s/languages', $surveyId));
        $languages = json_decode($questionsData->body());

        return $languages ? $languages->languages : [];
    }

    public function getResumeLink(string|int $surveyId, string|int $responseId): ?string
    {
        $resumeData = $this->fetchFromSurveyHero("surveys/$surveyId/responses/$responseId/resume");

        return $resumeData->successful() ? json_decode($resumeData->body())->url : null;
    }

    public function listWebhooks(string|int $surveyId): ?array
    {
        $webhookData = $this->fetchFromSurveyHero(sprintf('surveys/%s/webhooks', $surveyId));

        return $webhookData->successful() ? json_decode($webhookData->body())->webhooks : null;
    }

    public function createWebhook(string|int $surveyId, string $eventType, string $url, string $status = 'active')
    {
        $body = [
            'event_type' => $eventType,
            'url' => $url,
            'status' => $status,
        ];

        $this->postToSurveyHero(sprintf('surveys/%s/webhooks', $surveyId), $body);
    }

    public function deleteWebhook(string|int $surveyId, string|int $webhookId): ?stdClass
    {
        $webhookData = $this->deleteFromSurveyHero(sprintf('surveys/%s/webhooks/%s', $surveyId, $webhookId));

        return $webhookData->successful() ? json_decode($webhookData->body()) : null;
    }

    public function deleteResponse(string|int $surveyId, string|int $responseId)
    {
        $this->deleteFromSurveyHero(sprintf('surveys/%s/responses/%s', $surveyId, $responseId));
    }

    public function transformAPITimestamp(string $surveyheroTimestamp): Carbon
    {
        return Carbon::createFromFormat('Y-m-d\TH:i:s', substr($surveyheroTimestamp, 0, strpos($surveyheroTimestamp, '+')));
    }

    /**
     * @throws \Exception
     */
    private function fetchFromSurveyHero(string $urlPath, array $queryStringArgs = []): \Illuminate\Http\Client\Response
    {
        $this->preventThrottle();

        $response = Http::retry(3, 800)
            ->withBasicAuth(config('surveyhero.api_username'), config('surveyhero.api_password'))
            ->get(config('surveyhero.api_url').$urlPath, $queryStringArgs);

        $this->updateThrottle();

        if ($response->successful()) {
            return $response;
        }

        throw new \Exception($response->body());
    }

    /**
     * @throws \Exception
     */
    private function postToSurveyHero(string $urlPath, array $queryStringArgs = []): \Illuminate\Http\Client\Response
    {
        $this->preventThrottle();

        $response = Http::retry(3, 600)
            ->withBasicAuth(config('surveyhero.api_username'), config('surveyhero.api_password'))
            ->post(config('surveyhero.api_url').$urlPath, $queryStringArgs);

        $this->updateThrottle();

        if (! $response->successful()) {
            throw new \Exception($response->body());
        }

        return $response;
    }

    /**
     * @throws \Exception
     */
    private function deleteFromSurveyHero(string $urlPath, array $queryStringArgs = []): \Illuminate\Http\Client\Response
    {
        $this->preventThrottle();

        $response = Http::retry(3, 600)
            ->withBasicAuth(config('surveyhero.api_username'), config('surveyhero.api_password'))
            ->delete(config('surveyhero.api_url').$urlPath, $queryStringArgs);

        $this->updateThrottle();

        if ($response->successful()) {
            return $response;
        }
        throw new \Exception($response->body());
    }

    //Prevent API rate limiting: max 2 requests per second
    //Ensure sleep between requests
    private function preventThrottle(): void
    {
        if (Cache::has(self::CACHE_LATEST_REQUEST_TIME_KEY)) {
            //usleep is in microseconds, 1000000 is 1s.
            //Surveyhero only allows 2 requests per second.
            $sleepTime = abs(1000000 - (Carbon::now()->getTimestampMs() - Cache::get(self::CACHE_LATEST_REQUEST_TIME_KEY)) * 1000);
            usleep($sleepTime);
        }
    }

    //Set latest request time with 1s TTL
    private function updateThrottle(): void
    {
        Cache::put(self::CACHE_LATEST_REQUEST_TIME_KEY, Carbon::now()->getTimestampMs(), 1);
    }
}
