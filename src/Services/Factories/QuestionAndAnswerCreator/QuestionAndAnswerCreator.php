<?php

namespace Statikbe\Surveyhero\Services\Factories\QuestionAndAnswerCreator;

use Statikbe\Surveyhero\Exceptions\AnswerNotMappedException;
use Statikbe\Surveyhero\Models\Survey;
use Statikbe\Surveyhero\Models\SurveyQuestion;

interface QuestionAndAnswerCreator
{
    /**
     * @param  \stdClass  $question
     * @param Survey $survey
     * @param string $lang
     *
     * @throws AnswerNotMappedException
     */
    public function updateOrCreateQuestionAndAnswer(\stdClass $question, Survey $survey, string $lang): SurveyQuestion|array;
}
