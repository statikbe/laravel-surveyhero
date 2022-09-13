<?php

namespace Statikbe\Surveyhero\Contracts;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Statikbe\Surveyhero\Contracts\SurveyContract;

/**
 * @property int $id
 * @property SurveyContract $survey_id
 * @property int $surveyhero_question_id
 * @property string $field
 * @property string $label
 */
interface SurveyQuestionContract extends ModelContract
{
    public function survey(): BelongsTo;

    public function surveyAnswers(): HasMany;
}
