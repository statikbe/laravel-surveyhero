<?php

namespace Statikbe\Surveyhero\Services\Factories\AnswerCreator;

use Statikbe\Surveyhero\Exceptions\AnswerNotMappedException;
use Statikbe\Surveyhero\Models\SurveyQuestion;

interface AnswerCreator
{
    /**
     * @param  \stdClass  $question
     * @param  \Statikbe\Surveyhero\Models\SurveyQuestion  $surveyQuestion
     * @param  \stdClass  $lang
     *
     * @throws AnswerNotMappedException
     */
    public function updateOrCreateAnswer(\stdClass $question, SurveyQuestion $surveyQuestion, \stdClass $lang);
}
