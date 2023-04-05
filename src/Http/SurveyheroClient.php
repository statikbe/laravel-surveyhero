<?php

namespace Statikbe\Surveyhero\Http;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class SurveyheroClient
{
    const CACHE_LATEST_REQUEST_TIME_KEY = 'latest-surveyhero-api-request-time';

    const REQUEST_RATE_LIMIT_WAIT_TIME = 60000;

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

    public function getSurveyElements(string|int $surveyId, string $lang = null): array
    {
        $questionsData = $this->fetchFromSurveyHero(sprintf('surveys/%s/elements%s', $surveyId, $lang ? '?lang='.$lang : null));
        $questions = json_decode($questionsData->body());

        return $questions ? $questions->elements : [];
    }

    public function getSurveyQuestions(string|int $surveyId, string $lang = null): array
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

    public function createWebhook(string|int $surveyId, string $eventType, string $url, string $status = 'active')
    {
        $body = [
            'event_type' => $eventType,
            'url' => $url,
            'status' => $status,
        ];

        $this->postToSurveyHero(sprintf('surveys/%s/webhooks', $surveyId), $body);
    }

    public function deleteResponse(string|int $surveyId, string|int $responseId)
    {
        $this->deleteFromSurveyHero(sprintf('surveys/%s/responses/%s', $surveyId, $responseId));
    }

    public function transformAPITimestamp(string $surveyheroTimestamp): Carbon
    {
        return Carbon::createFromFormat('Y-m-d\TH:i:s', substr($surveyheroTimestamp, 0, strpos($surveyheroTimestamp, '+')));
    }

    private function fetchFromSurveyHero(string $urlPath, array $queryStringArgs = []): \Illuminate\Http\Client\Response
    {
        $this->preventThrottle();

        $response = Http::retry(3, 600)
                        ->withBasicAuth(config('surveyhero.api_username'), config('surveyhero.api_password'))
                        ->get(config('surveyhero.api_url').$urlPath, $queryStringArgs);

        $this->updateThrottle();

        if ($response->successful()) {
            return $response;
        }

        throw new \Exception($response->body());
    }

    private function postToSurveyHero(string $urlPath, array $queryStringArgs = []): \Illuminate\Http\Client\Response
    {
        $this->preventThrottle();

        $response = Http::retry(3, 600)
                        ->withBasicAuth(config('surveyhero.api_username'), config('surveyhero.api_password'))
                        ->post(config('surveyhero.api_url').$urlPath, $queryStringArgs);

        $this->updateThrottle();

        if ($response->successful()) {
            return $response;
        }

        throw new \Exception($response->body());
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
        if (cache(self::CACHE_LATEST_REQUEST_TIME_KEY)) {
            usleep(self::REQUEST_RATE_LIMIT_WAIT_TIME);
        }
    }

    //Set latest request time with 1s TTL
    private function updateThrottle(): void
    {
        cache([self::CACHE_LATEST_REQUEST_TIME_KEY => now()->format('Y-m-d H:i:s.u')], 1);
    }
}
