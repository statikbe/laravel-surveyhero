<?php

namespace Statikbe\Surveyhero\Contracts;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property SurveyContract $survey_id
 * @property int $surveyhero_element_id
 * @property int $surveyhero_question_id
 * @property string $field
 * @property string $label
 */
interface SurveyQuestionContract extends ModelContract
{
    public function survey(): BelongsTo;

    public function surveyAnswers(): HasMany;

    /**
     * Translate a variable with $key to $locale
     */
    public function translate(string $key, string $locale = '', bool $useFallbackLocale = true): mixed;
}
