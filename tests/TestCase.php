<?php

namespace Statikbe\Surveyhero\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Orchestra\Testbench\TestCase as Orchestra;
use Statikbe\Surveyhero\SurveyheroServiceProvider;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Statikbe\\Surveyhero\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            SurveyheroServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');
        config()->set('surveyhero.api_url', 'https://api.surveyhero.com/v1/');
        config()->set('surveyhero.api_username', 'test-user');
        config()->set('surveyhero.api_password', 'test-pass');
        config()->set('surveyhero.rate_limit_fallback_seconds', 60);
    }
}
