<?php

namespace Statikbe\Surveyhero\Services\Factories\QuestionAndAnswerCreator;

use Statikbe\Surveyhero\Contracts\SurveyContract;
use Statikbe\Surveyhero\Contracts\SurveyQuestionContract;
use Statikbe\Surveyhero\Exceptions\AnswerNotMappedException;

interface QuestionAndAnswerCreator
{
    /**
     * @throws AnswerNotMappedException
     */
    public function updateOrCreateQuestionAndAnswer(\stdClass $question, SurveyContract $survey, string $lang): SurveyQuestionContract|array;
}
