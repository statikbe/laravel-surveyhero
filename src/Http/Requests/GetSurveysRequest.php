<?php

namespace Statikbe\Surveyhero\Http\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class GetSurveysRequest extends Request
{
    protected Method $method = Method::GET;

    public function resolveEndpoint(): string
    {
        return 'surveys';
    }
}
