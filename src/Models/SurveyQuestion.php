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
 * @property Survey $survey_id
 * @property int $surveyhero_question_id
 * @property string $label
 */
class SurveyQuestion extends Model
{
    use HasFactory;
    use HasTranslations;

    protected $translatable = ['label'];

    protected $guarded = [];

    public function survey(): BelongsTo
    {
        return $this->belongsTo(Survey::class);
    }

    public function surveyAnswers(): HasMany
    {
        return $this->hasMany(SurveyAnswer::class);
    }
}
