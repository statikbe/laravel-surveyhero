<?php

namespace Statikbe\Surveyhero\Http\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Statikbe\Surveyhero\Http\DTO\WebhookDTO;

class ListWebhooksRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        private readonly string|int $surveyId
    ) {}

    public function resolveEndpoint(): string
    {
        return "surveys/{$this->surveyId}/webhooks";
    }

    /**
     * @return array<int, WebhookDTO>
     */
    public function createDtoFromResponse(Response $response): array
    {
        $data = $response->object();

        if (! isset($data->webhooks) || ! is_array($data->webhooks)) {
            return [];
        }

        return array_map(
            fn (object $webhook) => WebhookDTO::fromResponseObject($webhook),
            $data->webhooks
        );
    }
}
