<?php

namespace Statikbe\Surveyhero\Services\Factories\ResponseCreator;

use Statikbe\Surveyhero\Contracts\SurveyAnswerContract;
use Statikbe\Surveyhero\Contracts\SurveyQuestionResponseContract;
use Statikbe\Surveyhero\Contracts\SurveyResponseContract;
use Statikbe\Surveyhero\SurveyheroRegistrar;

class NumberResponseCreator extends AbstractQuestionResponseCreator
{
    const TYPE = 'number';

    /**
     * {@inheritDoc}
     */
    public function updateOrCreateQuestionResponse(\stdClass $surveyheroQuestionResponse,
        SurveyResponseContract $response,
        array $questionMapping): SurveyQuestionResponseContract|array
    {
        /* Config question_mapping data structure:
         * [
         *   'question_id' => 5410056,
         *   'type' => 'number',
         *   'field' => 'age',
         * ],
         *
         * Surveyhero API response:
         * {
         *  "element_id": 6059049,
         *  "question_text": "Your age",
         *  "type": "number",
         *  "number": 5
         * }
         */

        $existingQuestionResponse = $this->findExistingQuestionResponse($questionMapping['question_id'], $response);
        $surveyQuestion = $this->findSurveyQuestion($surveyheroQuestionResponse->element_id);
        $surveyAnswer = $this->fetchOrCreateInputAnswer($surveyQuestion,
            $questionMapping['mapped_data_type'] ?? SurveyAnswerContract::CONVERTED_TYPE_INT,
            $surveyheroQuestionResponse->number);

        $responseData = $this->createSurveyQuestionResponseData($surveyQuestion, $response, $surveyAnswer);

        return app(SurveyheroRegistrar::class)->getSurveyQuestionResponseClass()::updateOrCreate([
            'id' => $existingQuestionResponse->id ?? null,
        ], $responseData);
    }
}
