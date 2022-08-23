<?php

namespace Statikbe\Surveyhero\Services\Factories\ResponseCreator;

use Statikbe\Surveyhero\Exceptions\AnswerNotImportedException;
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
         */

        $existingQuestionResponse = $this->findExistingQuestionResponse($questionMapping['question_id'], $response);

        $responseData = $this->createSurveyQuestionResponseData($surveyheroQuestionResponse, $response, $questionMapping['field']);
        //if later other numeric types are needed, this should decide in which column to store and format the value accordingly:
        //$responseData['converted_int_value'] = intval($surveyheroQuestionResponse->number);

        $surveyAnswer = SurveyAnswer::where('converted_int_value', intval($surveyheroQuestionResponse->number))
                                    ->where('survey_question_id', $responseData['survey_question_id'])
                                    ->first();

        if (! $surveyAnswer) {
            throw AnswerNotImportedException::create(intval($surveyheroQuestionResponse->number), "Make sure to import survey answer with Surveyhero ID $surveyheroQuestionResponse->element_id in the survey_answers table");
        }
        $responseData['survey_answer_id'] = $surveyAnswer->id;

        return SurveyQuestionResponse::updateOrCreate([
            'id' => $existingQuestionResponse->id ?? null,
        ], $responseData);
    }
}
