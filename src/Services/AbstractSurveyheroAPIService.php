<?php

namespace Statikbe\Surveyhero\Services;

use Statikbe\Surveyhero\Http\SurveyheroClient;
use Statikbe\Surveyhero\SurveyheroConfig;

class AbstractSurveyheroAPIService
{
    protected SurveyheroClient $client;

    protected SurveyheroConfig $config;

    public function __construct()
    {
        $this->client = app(SurveyheroClient::class);
        $this->config = app(SurveyheroConfig::class);
    }

    public function getApiClient(): SurveyheroClient
    {
        return $this->client;
    }
}
