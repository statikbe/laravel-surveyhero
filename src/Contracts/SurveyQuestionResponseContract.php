<?php

    namespace Statikbe\Surveyhero\Contracts;

    use Illuminate\Database\Eloquent\Relations\BelongsTo;

    interface SurveyQuestionResponseContract {
        public function surveyResponse(): BelongsTo;

        public function surveyQuestion(): BelongsTo;

        public function surveyAnswer(): BelongsTo;
    }
