<?php

namespace Statikbe\Surveyhero\Http\Requests;

use Carbon\Carbon;
use Saloon\Enums\Method;
use Saloon\Http\Request;

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
}
