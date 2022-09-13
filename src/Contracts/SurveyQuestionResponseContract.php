<?php

namespace Statikbe\Surveyhero\Contracts;

use Carbon\Carbon;
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
 * @property SurveyResponseContract $surveyResponse
 * @property SurveyQuestionContract $surveyQuestion
 * @property SurveyAnswerContract $surveyAnswer
 */
interface SurveyQuestionResponseContract extends ModelContract
{
    public function surveyResponse(): BelongsTo;

    public function surveyQuestion(): BelongsTo;

    public function surveyAnswer(): BelongsTo;
}
