<?php

namespace Statikbe\Surveyhero\Http\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class GetSurveyResponseAnswersRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        private readonly string|int $surveyId,
        private readonly string|int $responseId
    ) {}

    public function resolveEndpoint(): string
    {
        return "surveys/{$this->surveyId}/responses/{$this->responseId}";
    }
}
