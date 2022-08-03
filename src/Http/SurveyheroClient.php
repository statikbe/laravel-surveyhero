<?php

namespace Statikbe\Surveyhero\Http;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class SurveyheroClient
{
    public function getSurveyResponses(string|int $surveyId): array
    {
        $responsesData = $this->fetchFromSurveyHero(sprintf('surveys/%s/responses', $surveyId));
        $responses = json_decode($responsesData->body());

        return $responses ? $responses->responses : [];
    }

    public function getSurveyResponseAnswers(string|int $surveyId, string|int $responseId): ?\stdClass
    {
        $answerData = $this->fetchFromSurveyHero(sprintf('surveys/%s/responses/%s', $surveyId, $responseId));

        return $answerData->successful() ? json_decode($answerData->body()) : null;
    }

    public function transformAPITimestamp(string $surveyheroTimestamp): Carbon
    {
        return Carbon::createFromFormat('Y-m-d\TH:i:s',
            substr($surveyheroTimestamp, 0, strpos($surveyheroTimestamp, '+')));
    }

    private function fetchFromSurveyHero(string $urlPath): \Illuminate\Http\Client\Response
    {
        return Http::withBasicAuth(config('surveyhero.api_username'), config('surveyhero.api_password'))
            ->get(config('surveyhero.api_url').$urlPath);
    }
}
