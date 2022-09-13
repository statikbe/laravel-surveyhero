<?php

namespace Statikbe\Surveyhero\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;

interface SurveyContract extends ModelContract
{
    public function surveyResponses(): HasMany;

    public function surveyQuestions(): HasMany;

    public function completedResponses(): Collection;

    public function hasResponses(): bool;
}
