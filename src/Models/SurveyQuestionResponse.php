<?php

namespace Statikbe\Surveyhero\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Statikbe\Surveyhero\Contracts\SurveyQuestionResponseContract;
use Statikbe\Surveyhero\SurveyheroRegistrar;

class SurveyQuestionResponse extends Model implements SurveyQuestionResponseContract
{
    use HasFactory;

    protected $guarded = [];

    public function getTable(): string
    {
        return config('surveyhero.table_names.survey_question_responses', parent::getTable());
    }

    public function surveyResponse(): BelongsTo
    {
        return $this->belongsTo(app(SurveyheroRegistrar::class)->getSurveyResponseClass());
    }

    public function surveyQuestion(): BelongsTo
    {
        return $this->belongsTo(app(SurveyheroRegistrar::class)->getSurveyQuestionClass());
    }

    public function surveyAnswer(): BelongsTo
    {
        return $this->belongsTo(app(SurveyheroRegistrar::class)->getSurveyAnswerClass());
    }
}
