<?php

namespace Statikbe\Surveyhero\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;
use Statikbe\Surveyhero\Contracts\SurveyQuestionContract;
use Statikbe\Surveyhero\SurveyheroRegistrar;

class SurveyQuestion extends Model implements SurveyQuestionContract
{
    use HasFactory;
    use HasTranslations;

    protected $translatable = ['label'];

    protected $guarded = [];

    public function getTable(): string
    {
        return config('surveyhero.table_names.survey_questions.name', parent::getTable());
    }

    public function survey(): BelongsTo
    {
        return $this->belongsTo(app(SurveyheroRegistrar::class)->getSurveyClass(),
            config('surveyhero.table_names.surveys.foreign_key', 'survey_id'));
    }

    public function surveyAnswers(): HasMany
    {
        return $this->hasMany(app(SurveyheroRegistrar::class)->getSurveyAnswerClass(),
            config('surveyhero.table_names.survey_questions.foreign_key', 'survey_question_id'));
    }
}
