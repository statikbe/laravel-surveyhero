<?php

namespace Statikbe\Surveyhero\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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
 * @property Survey $survey
 * @property Collection $surveyAnswerResponses
 */
class SurveyResponse extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $dates = [
        'survey_start_date',
        'survey_last_updated',
    ];

    public function survey(): BelongsTo
    {
        return $this->belongsTo(Survey::class);
    }

    public function surveyAnswerResponses(): HasMany
    {
        return $this->hasMany(SurveyQuestionResponse::class);
    }
}
