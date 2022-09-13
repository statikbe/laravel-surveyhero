<?php

namespace Statikbe\Surveyhero\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Statikbe\Surveyhero\Contracts\SurveyContract;
use Statikbe\Surveyhero\SurveyheroRegistrar;

/**
 * @property int $id
 * @property int $surveyhero_id
 * @property string $name
 * @property Carbon|null $survey_last_imported
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Collection $surveyResponses
 */
class Survey extends Model implements SurveyContract
{
    use HasFactory;

    protected $guarded = [];

    public function getTable(): string
    {
        return config('surveyhero.table_names.surveys', parent::getTable());
    }

    public function surveyResponses(): HasMany
    {
        return $this->hasMany(app(SurveyheroRegistrar::class)->getSurveyResponseClass());
    }

    public function surveyQuestions(): HasMany
    {
        return $this->hasMany(app(SurveyheroRegistrar::class)->getSurveyQuestionClass());
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
