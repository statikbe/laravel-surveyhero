<?php

namespace Statikbe\Surveyhero\Contracts;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $surveyhero_id
 * @property string $name
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon $survey_start_date
 * @property Carbon $survey_last_updated
 * @property string $survey_language
 * @property bool $survey_completed
 * @property int $survey_id
 * @property string $resume_link
 * @property SurveyContract $survey
 * @property Collection $surveyQuestionResponses
 */
interface SurveyResponseContract extends ModelContract
{
    public function survey(): BelongsTo;

    public function surveyQuestionResponses(): HasMany;
}
