<?php

namespace Statikbe\Surveyhero\Contracts;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Statikbe\Surveyhero\Models\SurveyQuestion;

/**
 * @property int $id
 * @property SurveyQuestion $survey_question_id
 * @property int $surveyhero_answer_id
 * @property int $converted_int_value
 * @property string $converted_string_value
 * @property string $label
 */
interface SurveyAnswerContract extends ModelContract
{
    const CONVERTED_TYPE_INT = 'int';

    const CONVERTED_TYPE_STRING = 'string';

    public function surveyQuestion(): BelongsTo;
}
