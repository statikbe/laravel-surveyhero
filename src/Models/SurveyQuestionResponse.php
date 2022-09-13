<?php

namespace Statikbe\Surveyhero\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Statikbe\Surveyhero\Contracts\SurveyQuestionResponseContract;
use Statikbe\Surveyhero\SurveyheroRegistrar;

/**
 * @property int $id
 * @property int $surveyhero_question_id
 * @property int $surveyhero_answer_id
 * @property string $field
 * @property string $converted_string_value
 * @property string $converted_int_value
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property int $survey_response_id
 * @property SurveyResponse $surveyResponse
 */
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
