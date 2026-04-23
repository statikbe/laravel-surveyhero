<?php

namespace Statikbe\Surveyhero\Http\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class GetResumeLinkRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        private readonly string|int $surveyId,
        private readonly string|int $responseId
    ) {}

    public function resolveEndpoint(): string
    {
        return "surveys/{$this->surveyId}/responses/{$this->responseId}/resume";
    }

    public function hasRequestFailed(Response $response): ?bool
    {
        if ($response->status() === HttpResponse::HTTP_NOT_FOUND) {
            return false;
        }

        return null;
    }
}
