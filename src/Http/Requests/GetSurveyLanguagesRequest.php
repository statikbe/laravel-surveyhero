<?php

namespace Statikbe\Surveyhero\Http\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class GetSurveyLanguagesRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        private readonly string|int $surveyId
    ) {}

    public function resolveEndpoint(): string
    {
        return "surveys/{$this->surveyId}/languages";
    }
}
