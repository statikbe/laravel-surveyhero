<?php

namespace Statikbe\Surveyhero\Services\Factories\ResponseCreator;

use Statikbe\Surveyhero\Models\SurveyQuestionResponse;
use Statikbe\Surveyhero\Models\SurveyResponse;

interface QuestionResponseCreator
{
    /**
     * @param  \stdClass  $surveyheroQuestionResponse
     * @param  SurveyResponse  $response
     * @param  array  $questionMapping
     * @return SurveyQuestionResponse|array<int, SurveyQuestionResponse>
     *
     * @throws \Statikbe\Surveyhero\Exceptions\AnswerNotImportedException
     * @throws \Statikbe\Surveyhero\Exceptions\AnswerNotMappedException
     * @throws \Statikbe\Surveyhero\Exceptions\QuestionNotImportedException
     */
    public function updateOrCreateQuestionResponse(\stdClass $surveyheroQuestionResponse,
        SurveyResponse $response,
        array $questionMapping): SurveyQuestionResponse|array;
}
