<?php

namespace Statikbe\Surveyhero;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Statikbe\Surveyhero\Commands\SurveyheroMapperCommand;
use Statikbe\Surveyhero\Commands\SurveyheroQuestionsAndAnswersImportCommand;
use Statikbe\Surveyhero\Commands\SurveyheroResponseImportCommand;

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
            ->hasCommands([
                SurveyheroResponseImportCommand::class,
                SurveyheroQuestionsAndAnswersImportCommand::class,
                SurveyheroMapperCommand::class,
            ]);
    }
}
