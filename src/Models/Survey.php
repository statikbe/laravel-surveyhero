<?php

namespace Statikbe\Surveyhero\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Statikbe\Surveyhero\Contracts\SurveyContract;
use Statikbe\Surveyhero\SurveyheroRegistrar;
use Statikbe\Surveyhero\Traits\HasCollectors;
use Statikbe\Surveyhero\Traits\HasQuestionMapping;

class Survey extends Model implements SurveyContract
{
    use HasCollectors;
    use HasFactory;
    use HasQuestionMapping;

    protected $guarded = [];

    protected $casts = [
        'survey_last_imported' => 'datetime',
        'question_mapping' => 'array',
        'collector_ids' => 'array',
    ];

    public function getTable(): string
    {
        return config('surveyhero.table_names.surveys.name', parent::getTable());
    }

    public function surveyResponses(): HasMany
    {
        return $this->hasMany(app(SurveyheroRegistrar::class)->getSurveyResponseClass(),
            config('surveyhero.table_names.surveys.foreign_key', 'survey_id'));
    }

    public function surveyQuestions(): HasMany
    {
        return $this->hasMany(app(SurveyheroRegistrar::class)->getSurveyQuestionClass(),
            config('surveyhero.table_names.surveys.foreign_key', 'survey_id'));
    }

    public function completedResponses(): Collection
    {
        return $this->surveyResponses()->where('survey_completed', 1)->get();
    }

    public function hasResponses(): bool
    {
        return $this->surveyResponses()->count() > 0;
    }

    /**
     * {@inheritDoc}
     */
    public function doesResponseNeedsToBeUpdated(string $responseLastUpdatedIsoTimestamp): bool
    {
        if ($this->survey_last_imported) {
            return Carbon::parse($responseLastUpdatedIsoTimestamp)->gt($this->survey_last_imported);
        } else {
            return true;
        }
    }
}
