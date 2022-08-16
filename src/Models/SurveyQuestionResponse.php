<?php

namespace Statikbe\Surveyhero\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
class SurveyQuestionResponse extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function surveyResponse(): BelongsTo
    {
        return $this->belongsTo(SurveyResponse::class);
    }
}
