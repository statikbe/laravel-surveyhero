<?php

namespace Statikbe\Surveyhero\Http\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Statikbe\Surveyhero\Http\DTO\SurveyLanguageDTO;

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

    /**
     * @return array<int, SurveyLanguageDTO>
     */
    public function createDtoFromResponse(Response $response): array
    {
        $data = $response->object();

        if (! isset($data->languages) || ! is_array($data->languages)) {
            return [];
        }

        return array_map(
            fn (object $language) => SurveyLanguageDTO::fromResponseObject($language),
            $data->languages
        );
    }
}
