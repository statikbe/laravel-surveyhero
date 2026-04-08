<?php

namespace Statikbe\Surveyhero\Http\Requests;

use Carbon\Carbon;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Statikbe\Surveyhero\Http\DTO\SurveyResponseDTO;

class GetSurveyResponsesRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        private readonly string|int $surveyId,
        private readonly ?Carbon $lastUpdatedFrom = null,
        private readonly array $collectorIds = []
    ) {}

    public function resolveEndpoint(): string
    {
        return "surveys/{$this->surveyId}/responses";
    }

    protected function defaultQuery(): array
    {
        $query = [];

        if ($this->lastUpdatedFrom) {
            $query['last_updated_on[from]'] = $this->lastUpdatedFrom->toIso8601String();
        }

        if (! empty($this->collectorIds)) {
            $query['collector_id'] = $this->collectorIds;
        }

        return $query;
    }

    /**
     * @return array<int, SurveyResponseDTO>
     */
    public function createDtoFromResponse(Response $response): array
    {
        $data = $response->object();

        if (! isset($data->responses) || ! is_array($data->responses)) {
            return [];
        }

        return array_map(
            fn (object $response) => SurveyResponseDTO::fromResponseObject($response),
            $data->responses
        );
    }
}
