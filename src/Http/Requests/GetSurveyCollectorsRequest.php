<?php

namespace Statikbe\Surveyhero\Http\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Statikbe\Surveyhero\Http\DTO\SurveyCollectorDTO;

class GetSurveyCollectorsRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        private readonly string|int $surveyId
    ) {}

    public function resolveEndpoint(): string
    {
        return "surveys/{$this->surveyId}/collectors";
    }

    /**
     * @return array<int, SurveyCollectorDTO>
     */
    public function createDtoFromResponse(Response $response): array
    {
        $data = $response->object();

        if (! isset($data->collectors) || ! is_array($data->collectors)) {
            return [];
        }

        return array_map(
            fn (object $collector) => SurveyCollectorDTO::fromResponseObject($collector),
            $data->collectors
        );
    }
}
