<?php

namespace Statikbe\Surveyhero\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;

/**
 * @property int $id
 * @property SurveyQuestion $survey_question_id
 * @property int $surveyhero_answer_id
 * @property int $converted_int_value
 * @property string $converted_string_value
 * @property string $label
 */
class SurveyAnswer extends Model
{
    use HasFactory;
    use HasTranslations;

    const CONVERTED_TYPE_INT = 'int';

    const CONVERTED_TYPE_STRING = 'string';

    protected $translatable = ['label'];

    protected $guarded = [];

    public function surveyQuestion(): BelongsTo
    {
        return $this->belongsTo(SurveyQuestion::class);
    }
}
