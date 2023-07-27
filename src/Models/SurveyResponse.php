<?php

namespace Statikbe\Surveyhero\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Statikbe\Surveyhero\Contracts\SurveyResponseContract;
use Statikbe\Surveyhero\SurveyheroRegistrar;

class SurveyResponse extends Model implements SurveyResponseContract
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'survey_start_date' => 'datetime',
        'survey_last_updated' => 'datetime',
    ];

    public function getTable(): string
    {
        return config('surveyhero.table_names.survey_responses.name', parent::getTable());
    }

    public function survey(): BelongsTo
    {
        return $this->belongsTo(app(SurveyheroRegistrar::class)->getSurveyClass(),
            config('surveyhero.table_names.surveys.foreign_key', 'survey_id'));
    }

    public function surveyQuestionResponses(): HasMany
    {
        return $this->hasMany(app(SurveyheroRegistrar::class)->getSurveyQuestionResponseClass(),
            config('surveyhero.table_names.survey_responses.foreign_key', 'survey_response_id'));
    }
}
