<?php

namespace Statikbe\Surveyhero;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Statikbe\Surveyhero\Commands\SurveyheroMapperCommand;
use Statikbe\Surveyhero\Commands\SurveyheroQuestionsAndAnswersImportCommand;
use Statikbe\Surveyhero\Commands\SurveyheroResponseImportCommand;
use Statikbe\Surveyhero\Commands\SurveyheroSurveyImportCommand;
use Statikbe\Surveyhero\Commands\SurveyheroWebhookAddCommand;
use Statikbe\Surveyhero\Commands\SurveyheroWebhookDeleteCommand;
use Statikbe\Surveyhero\Commands\SurveyheroWebhookListCommand;
use Statikbe\Surveyhero\Commands\SurveyResponseExportCommand;

class SurveyheroServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-surveyhero')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_surveyhero_tables')
            ->hasMigration('create_unique_surveyhero_id_indices')
            ->hasCommands([
                SurveyheroSurveyImportCommand::class,
                SurveyheroResponseImportCommand::class,
                SurveyheroQuestionsAndAnswersImportCommand::class,
                SurveyheroMapperCommand::class,
                SurveyheroWebhookAddCommand::class,
                SurveyheroWebhookListCommand::class,
                SurveyheroWebhookDeleteCommand::class,
                SurveyResponseExportCommand::class,
            ]);
    }

    public function packageBooted()
    {
        parent::packageBooted();

        $this->app->singleton(SurveyheroRegistrar::class, function ($app) {
            return new SurveyheroRegistrar;
        });
    }
}
