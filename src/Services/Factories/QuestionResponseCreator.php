<?php

namespace Statikbe\Surveyhero\Services\Factories;

use Statikbe\Surveyhero\Exceptions\AnswerNotMappedException;
use Statikbe\Surveyhero\Models\SurveyQuestionResponse;
use Statikbe\Surveyhero\Models\SurveyResponse;

interface QuestionResponseCreator
{
    /**
     * @param  \stdClass  $surveyheroQuestionResponse
     * @param  SurveyResponse  $response
     * @param  array  $questionMapping
     * @return SurveyQuestionResponse|array<int, SurveyQuestionResponse>
     * @throws AnswerNotMappedException
     */
    public function updateOrCreateQuestionResponse(\stdClass $surveyheroQuestionResponse,
        SurveyResponse $response,
        array $questionMapping): SurveyQuestionResponse|array;
}
