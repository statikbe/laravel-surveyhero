<?php

namespace Statikbe\Surveyhero\Http\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Statikbe\Surveyhero\Http\DTO\SurveyElementDTO;

class GetSurveyQuestionsRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        private readonly string|int $surveyId,
        private readonly ?string $lang = null
    ) {}

    public function resolveEndpoint(): string
    {
        return "surveys/{$this->surveyId}/questions";
    }

    protected function defaultQuery(): array
    {
        return $this->lang ? ['lang' => $this->lang] : [];
    }

    /**
     * @return array<int, SurveyElementDTO>
     */
    public function createDtoFromResponse(Response $response): array
    {
        $data = $response->object();

        if (! isset($data->elements) || ! is_array($data->elements)) {
            return [];
        }

        return array_map(
            fn (object $element) => SurveyElementDTO::fromResponseObject($element),
            $data->elements
        );
    }
}
