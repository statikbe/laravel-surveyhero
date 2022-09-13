<?php

namespace Statikbe\Surveyhero\Contracts;

    use Illuminate\Database\Eloquent\Relations\BelongsTo;
    use Illuminate\Database\Eloquent\Relations\HasMany;

    interface SurveyQuestionContract extends ModelContract {
        public function survey(): BelongsTo;

        public function surveyAnswers(): HasMany;
    }
