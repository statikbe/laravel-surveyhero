<?php

    namespace Statikbe\Surveyhero\Contracts;

    use Illuminate\Database\Eloquent\Relations\BelongsTo;

    interface SurveyQuestionResponseContract extends ModelContract {
        public function surveyResponse(): BelongsTo;

        public function surveyQuestion(): BelongsTo;

        public function surveyAnswer(): BelongsTo;
    }
