<?php

namespace Statikbe\Surveyhero\Services;

use Statikbe\Surveyhero\Http\SurveyheroClient;
use Statikbe\Surveyhero\SurveyheroConfig;

class AbstractSurveyheroAPIService
{
    public function __construct(
        protected readonly SurveyheroClient $client,
        protected readonly SurveyheroConfig $config = new SurveyheroConfig
    ) {}

    public function getApiClient(): SurveyheroClient
    {
        return $this->client;
    }
}
