<?php

    namespace Statikbe\Surveyhero\Services;

    use Statikbe\Surveyhero\Http\SurveyheroClient;

    class AbstractSurveyheroAPIService {
        /**
         * @var \Statikbe\Surveyhero\Http\SurveyheroClient
         */
        protected SurveyheroClient $client;

        public function __construct()
        {
            $this->client = new SurveyheroClient();
        }

        public function getApiClient(): SurveyheroClient {
            return $this->client;
        }
    }
