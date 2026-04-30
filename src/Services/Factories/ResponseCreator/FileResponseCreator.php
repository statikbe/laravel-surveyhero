<?php

namespace Statikbe\Surveyhero\Services\Factories\ResponseCreator;

use Statikbe\Surveyhero\Contracts\SurveyAnswerContract;
use Statikbe\Surveyhero\Contracts\SurveyQuestionResponseContract;
use Statikbe\Surveyhero\Contracts\SurveyResponseContract;
use Statikbe\Surveyhero\SurveyheroRegistrar;

class FileResponseCreator extends AbstractQuestionResponseCreator
{
    const TYPE = 'file';

    /**
     * {@inheritDoc}
     */
    public function updateOrCreateQuestionResponse(
        \stdClass $surveyheroQuestionResponse,
        SurveyResponseContract $response,
        array $questionMapping): SurveyQuestionResponseContract|array
    {
        $existingResponse = $this->findExistingQuestionResponse($questionMapping['question_id'], $response);
        $surveyQuestion = $this->findSurveyQuestion($surveyheroQuestionResponse->element_id);
        $surveyAnswer = $this->fetchOrCreateInputAnswer(
            $surveyQuestion,
            SurveyAnswerContract::CONVERTED_TYPE_STRING,
            $surveyheroQuestionResponse->file->path
        );

        $responseData = $this->createSurveyQuestionResponseData($surveyQuestion, $response, $surveyAnswer);

        return app(SurveyheroRegistrar::class)->getSurveyQuestionResponseClass()::updateOrCreate(
            ['id' => $existingResponse->id ?? null],
            $responseData
        );
    }
}
