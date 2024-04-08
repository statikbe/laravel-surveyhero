<?php

namespace Statikbe\Surveyhero\Services\Factories\ResponseCreator;

use Statikbe\Surveyhero\Contracts\SurveyAnswerContract;
use Statikbe\Surveyhero\Contracts\SurveyQuestionResponseContract;
use Statikbe\Surveyhero\Contracts\SurveyResponseContract;
use Statikbe\Surveyhero\SurveyheroRegistrar;

class TextResponseCreator extends AbstractQuestionResponseCreator
{
    const TYPE = 'text';

    /**
     * {@inheritDoc}
     */
    public function updateOrCreateQuestionResponse(\stdClass $surveyheroQuestionResponse,
        SurveyResponseContract $response,
        array $questionMapping): SurveyQuestionResponseContract|array
    {
        /* Config question_mapping data structure:
         * [
         *   'question_id' => 5410055,
         *   'type' => 'text',
         *   'field' => 'job_description',
         * ],
         */

        $existingQuestionResponse = $this->findExistingQuestionResponse($questionMapping['question_id'], $response);
        $surveyQuestion = $this->findSurveyQuestion($surveyheroQuestionResponse->element_id);
        $surveyAnswer = $this->fetchOrCreateInputAnswer($surveyQuestion,
            $questionMapping['mapped_data_type'] ?? SurveyAnswerContract::CONVERTED_TYPE_STRING,
            $surveyheroQuestionResponse->text);

        $responseData = $this->createSurveyQuestionResponseData($surveyQuestion, $response, $surveyAnswer);

        return app(SurveyheroRegistrar::class)->getSurveyQuestionResponseClass()::updateOrCreate([
            'id' => $existingQuestionResponse->id ?? null,
        ], $responseData);
    }
}
