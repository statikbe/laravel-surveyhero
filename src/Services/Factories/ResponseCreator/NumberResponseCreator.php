<?php

namespace Statikbe\Surveyhero\Services\Factories\ResponseCreator;

use Statikbe\Surveyhero\Models\SurveyAnswer;
use Statikbe\Surveyhero\Models\SurveyQuestionResponse;
use Statikbe\Surveyhero\Models\SurveyResponse;

class NumberResponseCreator extends AbstractQuestionResponseCreator
{
    const TYPE = 'number';

    public function updateOrCreateQuestionResponse(\stdClass $surveyheroQuestionResponse,
        SurveyResponse $response,
        array $questionMapping): SurveyQuestionResponse|array
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
            $questionMapping['mapped_data_type'] ?? SurveyAnswer::CONVERTED_TYPE_INT,
            $surveyheroQuestionResponse->number);

        $responseData = $this->createSurveyQuestionResponseData($surveyQuestion, $response, $surveyAnswer);

        return SurveyQuestionResponse::updateOrCreate([
            'id' => $existingQuestionResponse->id ?? null,
        ], $responseData);
    }
}
