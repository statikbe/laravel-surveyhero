<?php

namespace Statikbe\Surveyhero\Http\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class GetSurveyElementsRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        private readonly string|int $surveyId,
        private readonly ?string $lang = null
    ) {}

    public function resolveEndpoint(): string
    {
        return "surveys/{$this->surveyId}/elements";
    }

    protected function defaultQuery(): array
    {
        return $this->lang ? ['lang' => $this->lang] : [];
    }
}
