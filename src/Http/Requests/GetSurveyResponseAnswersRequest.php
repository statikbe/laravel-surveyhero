<?php

namespace Statikbe\Surveyhero\Http\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Statikbe\Surveyhero\Http\DTO\SurveyResponseAnswersDTO;

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

    public function createDtoFromResponse(Response $response): SurveyResponseAnswersDTO
    {
        return SurveyResponseAnswersDTO::fromResponseObject($response->object());
    }
}
