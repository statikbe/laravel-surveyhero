<?php

namespace Statikbe\Surveyhero\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Statikbe\Surveyhero\Contracts\SurveyResponseContract;
use Statikbe\Surveyhero\Models\SurveyResponse;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

class SurveyResponseIncompletelyImported implements ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        private SurveyResponseContract $surveyResponse
    ){}

    public function getSurveyResponse(): SurveyResponse
    {
        return $this->surveyResponse;
    }
}
