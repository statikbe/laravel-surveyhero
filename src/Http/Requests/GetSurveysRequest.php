<?php

namespace Statikbe\Surveyhero\Http\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Statikbe\Surveyhero\Http\DTO\SurveyDTO;

class GetSurveysRequest extends Request
{
    protected Method $method = Method::GET;

    public function resolveEndpoint(): string
    {
        return 'surveys';
    }

    /**
     * @return array<int, SurveyDTO>
     */
    public function createDtoFromResponse(Response $response): array
    {
        $data = $response->object();

        if (! isset($data->surveys) || ! is_array($data->surveys)) {
            return [];
        }

        return array_map(
            fn (object $survey) => SurveyDTO::fromResponseObject($survey),
            $data->surveys
        );
    }
}
