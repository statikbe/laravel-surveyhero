<?php

namespace Statikbe\Surveyhero\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Survey extends Model
{
    use HasFactory;

    public function surveyResponses(): HasMany {
        return $this->hasMany(SurveyResponse::class);
    }

    public function completedResponses(): Collection {
        return $this->surveyResponses()->where('survey_completed', 1)->get();
    }

    public function hasResponses(): bool {
        return $this->surveyResponses()->count() > 0;
    }
}
