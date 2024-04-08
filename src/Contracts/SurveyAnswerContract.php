<?php

namespace Statikbe\Surveyhero\Contracts;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $survey_question_id
 * @property SurveyQuestionContract $surveyQuestion
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

    /**
     * Translate a variable with $key to $locale
     */
    public function translate(string $key, string $locale = '', bool $useFallbackLocale = true): mixed;
}
