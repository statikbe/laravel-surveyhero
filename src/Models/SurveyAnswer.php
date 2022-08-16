<?php

namespace Statikbe\Surveyhero\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

/**
 * @property int $id
 * @property SurveyQuestion $survey_question_id
 * @property int $surveyhero_question_id
 * @property string $label
 */
class SurveyAnswer extends Model
{
    use HasFactory;
    use HasTranslations;

    protected $translatable = ['label'];

    protected $guarded = [];

    public function surveyQuestion(): BelongsTo
    {
        return $this->belongsTo(SurveyQuestion::class);
    }
}
