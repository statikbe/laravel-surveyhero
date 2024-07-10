<?php

namespace Statikbe\Surveyhero\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Statikbe\Surveyhero\Contracts\SurveyResponseContract;

class SurveyResponseImported implements ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        private SurveyResponseContract $surveyResponse
    ) {}

    public function getSurveyResponse(): SurveyResponseContract
    {
        return $this->surveyResponse;
    }
}
