<?php

namespace Statikbe\Surveyhero\Services;

use Statikbe\Surveyhero\Http\SurveyheroClient;

class AbstractSurveyheroAPIService
{
    protected SurveyheroClient $client;

    public function __construct()
    {
        $this->client = app(SurveyheroClient::class);
    }

    public function getApiClient(): SurveyheroClient
    {
        return $this->client;
    }
}
