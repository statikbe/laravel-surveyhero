<?php

namespace Statikbe\Surveyhero\Commands;

use Illuminate\Console\Command;

class SurveyheroCommand extends Command
{
    public $signature = 'laravel-surveyhero';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
