<?php

namespace Statikbe\Surveyhero\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $surveyhero_id
 * @property string $name
 * @property Carbon $survey_last_updated
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Collection $surveyResponses
 */
class Survey extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function surveyResponses(): HasMany
    {
        return $this->hasMany(SurveyResponse::class);
    }

    public function surveyQuestions(): HasMany
    {
        return $this->hasMany(SurveyQuestion::class);
    }

    public function completedResponses(): Collection
    {
        return $this->surveyResponses()->where('survey_completed', 1)->get();
    }

    public function hasResponses(): bool
    {
        return $this->surveyResponses()->count() > 0;
    }
}
