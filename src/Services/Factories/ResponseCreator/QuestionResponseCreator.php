<?php

namespace Statikbe\Surveyhero\Services\Factories\ResponseCreator;

use Statikbe\Surveyhero\Contracts\SurveyQuestionResponseContract;
use Statikbe\Surveyhero\Contracts\SurveyResponseContract;

interface QuestionResponseCreator
{
    /**
     * @param  \stdClass  $surveyheroQuestionResponse
     * @param  SurveyResponseContract  $response
     * @param  array  $questionMapping
     * @return SurveyQuestionResponseContract|array<int, SurveyQuestionResponseContract>
     *
     * @throws \Statikbe\Surveyhero\Exceptions\AnswerNotImportedException
     * @throws \Statikbe\Surveyhero\Exceptions\AnswerNotMappedException
     * @throws \Statikbe\Surveyhero\Exceptions\QuestionNotImportedException
     */
    public function updateOrCreateQuestionResponse(\stdClass      $surveyheroQuestionResponse,
                                                   SurveyResponseContract $response,
                                                   array          $questionMapping): SurveyQuestionResponseContract|array;
}
