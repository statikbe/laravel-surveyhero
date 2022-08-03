<?php

namespace Statikbe\Surveyhero\Services\Factories;

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
         */

        $existingQuestionResponse = $this->findExistingQuestionResponse($questionMapping['question_id'], $response);

        $responseData = $this->createSurveyQuestionResponseData($surveyheroQuestionResponse, $response, $questionMapping['field']);
        //if later other numeric types are needed, this should decide in which column to store and format the value accordingly:
        $responseData['converted_int_value'] = intval($surveyheroQuestionResponse->number);

        return SurveyQuestionResponse::updateOrCreate([
            'id' => $existingQuestionResponse->id ?? null,
        ], $responseData);
    }
}
