<?php

namespace Statikbe\Surveyhero\Services;

use Statikbe\Surveyhero\Http\SurveyheroClient;
use Statikbe\Surveyhero\SurveyheroConfig;

class AbstractSurveyheroAPIService
{
    public function __construct(
        protected readonly SurveyheroClient $client
    ) {}

    public function getApiClient(): SurveyheroClient
    {
        return $this->client;
    }
}
