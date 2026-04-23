<?php

namespace Statikbe\Surveyhero\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase as Orchestra;
use Saloon\Http\Faking\MockClient;
use Saloon\MockConfig;
use Statikbe\Surveyhero\Http\Connector\SurveyheroConnector;
use Statikbe\Surveyhero\Http\SurveyheroClient;
use Statikbe\Surveyhero\SurveyheroServiceProvider;

class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        MockConfig::setFixturePath(__DIR__.'/Fixtures/Saloon');

        cache()->flush();

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->artisan('migrate')->run();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Statikbe\\Surveyhero\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    /**
     * @return array{0: SurveyheroClient, 1: MockClient}
     */
    protected function makeSurveyheroClient(array $responses): array
    {
        $mockClient = new MockClient($responses);
        $connector = new SurveyheroConnector;
        $connector->withMockClient($mockClient);

        return [new SurveyheroClient($connector), $mockClient];
    }

    protected function getPackageProviders($app)
    {
        return [
            SurveyheroServiceProvider::class,
        ];
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');
        config()->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        config()->set('surveyhero.api_username', 'test-user');
        config()->set('surveyhero.api_password', 'test-pass');
        config()->set('surveyhero.api_url', 'https://api.surveyhero.com/v1/');

        // Test question_mapping matching fixture data
        config()->set('surveyhero.question_mapping', [
            [
                'survey_id' => 1234567,
                'collectors' => [9876543],
                'use_resume_link' => false,
                'questions' => [
                    1000002 => [
                        'question_id' => 1000002,
                        'type' => 'choices',
                        'field' => 'question_2',
                        'answer_mapping' => [
                            13509166 => 1,
                            13509167 => 2,
                            13509168 => 3,
                        ],
                        'mapped_data_type' => 'int',
                    ],
                    1000005 => [
                        'question_id' => 1000005,
                        'type' => 'input',
                        'field' => 'question_5',
                        'mapped_data_type' => 'string',
                    ],
                    1000006 => [
                        'question_id' => 1000006,
                        'type' => 'input',
                        'field' => 'question_6',
                        'mapped_data_type' => 'int',
                    ],
                ],
            ],
        ]);
    }
}
