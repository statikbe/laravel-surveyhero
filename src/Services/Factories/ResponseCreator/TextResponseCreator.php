<?php

namespace Statikbe\Surveyhero\Services\Factories\ResponseCreator;

use Statikbe\Surveyhero\Exceptions\AnswerNotImportedException;
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

        $responseData = $this->createSurveyQuestionResponseData($surveyheroQuestionResponse, $response, $questionMapping['field']);
        //$responseData['converted_string_value'] = $surveyheroQuestionResponse->text;

        $surveyAnswer = SurveyAnswer::where('converted_string_value', $surveyheroQuestionResponse->text)
                                    ->where('survey_question_id', $responseData['survey_question_id'])
                                    ->first();

        if (! $surveyAnswer) {
            throw AnswerNotImportedException::create(intval($surveyheroQuestionResponse->text), "Make sure to import survey answer with Surveyhero ID $surveyheroQuestionResponse->element_id in the survey_answers table");
        }
        $responseData['survey_answer_id'] = $surveyAnswer->id;

        return SurveyQuestionResponse::updateOrCreate([
            'id' => $existingQuestionResponse->id ?? null,
        ], $responseData);
    }
}
