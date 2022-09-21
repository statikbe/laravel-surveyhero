<?php

namespace Statikbe\Surveyhero\Contracts;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $surveyhero_id
 * @property string $name
 * @property Carbon|null $survey_last_imported
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Collection $surveyResponses
 */
interface SurveyContract extends ModelContract
{
    public function surveyResponses(): HasMany;

    public function surveyQuestions(): HasMany;

    public function completedResponses(): Collection;

    public function hasResponses(): bool;

    /**
     * Checks if the response timestamp is more recent than the last updated survey timestamp.
     *
     * @param  string  $responseLastUpdatedIsoTimestamp
     * @return bool
     */
    public function doesResponseNeedsToBeUpdated(string $responseLastUpdatedIsoTimestamp): bool;
}
