<?php

use Saloon\MockConfig;
use Statikbe\Surveyhero\Tests\TestCase;

MockConfig::setFixturePath(__DIR__.'/Fixtures/Saloon');

uses(TestCase::class)->in(__DIR__);
