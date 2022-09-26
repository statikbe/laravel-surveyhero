<?php

namespace Statikbe\Surveyhero\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;
use Statikbe\Surveyhero\Contracts\SurveyAnswerContract;
use Statikbe\Surveyhero\SurveyheroRegistrar;

class SurveyAnswer extends Model implements SurveyAnswerContract
{
    use HasFactory;
    use HasTranslations;

    protected $translatable = ['label'];

    protected $guarded = [];

    public function getTable(): string
    {
        return config('surveyhero.table_names.survey_answers.name', parent::getTable());
    }

    public function surveyQuestion(): BelongsTo
    {
        return $this->belongsTo(app(SurveyheroRegistrar::class)->getSurveyQuestionClass(),
            config('surveyhero.table_names.survey_questions.foreign_key', 'survey_question_id'));
    }

    public function convertedValue(): int|string|null
    {
        if ($this->converted_string_value) {
            return $this->converted_string_value;
        } else {
            return $this->converted_int_value;
        }
    }
}
