<?php

namespace Statikbe\Surveyhero\Contracts;

    use Illuminate\Database\Eloquent\Relations\BelongsTo;

    interface SurveyAnswerContract
    {
        const CONVERTED_TYPE_INT = 'int';

        const CONVERTED_TYPE_STRING = 'string';

        public function surveyQuestion(): BelongsTo;
    }
