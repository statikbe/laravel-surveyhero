<?php

namespace Statikbe\Surveyhero\Contracts;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Statikbe\Surveyhero\Models\SurveyResponse;

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
interface SurveyQuestionResponseContract extends ModelContract {
    public function surveyResponse(): BelongsTo;

    public function surveyQuestion(): BelongsTo;

    public function surveyAnswer(): BelongsTo;
}
