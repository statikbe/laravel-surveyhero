<?php

namespace Statikbe\Surveyhero\Services\Factories\ResponseCreator;

use Statikbe\Surveyhero\Models\SurveyAnswer;
use Statikbe\Surveyhero\Models\SurveyQuestionResponse;
use Statikbe\Surveyhero\Models\SurveyResponse;

class TextResponseCreator extends AbstractQuestionResponseCreator
{
    const TYPE = 'text';

    public function updateOrCreateQuestionResponse(\stdClass $surveyheroQuestionResponse,
        SurveyResponse $response,
        array $questionMapping): SurveyQuestionResponse|array
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
            $questionMapping['mapped_data_type'] ?? SurveyAnswer::CONVERTED_TYPE_STRING,
            $surveyheroQuestionResponse->text);

        $responseData = $this->createSurveyQuestionResponseData($surveyQuestion, $response, $surveyAnswer);

        return SurveyQuestionResponse::updateOrCreate([
            'id' => $existingQuestionResponse->id ?? null,
        ], $responseData);
    }
}
