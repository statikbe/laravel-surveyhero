<?php

namespace Statikbe\Surveyhero\Services\Factories\ResponseCreator;

use Statikbe\Surveyhero\Exceptions\AnswerNotMappedException;
use Statikbe\Surveyhero\Exceptions\QuestionNotImportedException;
use Statikbe\Surveyhero\Models\SurveyQuestion;
use Statikbe\Surveyhero\Models\SurveyQuestionResponse;
use Statikbe\Surveyhero\Models\SurveyResponse;

abstract class AbstractQuestionResponseCreator implements QuestionResponseCreator
{
    /**
     * @param  string|int  $surveyheroQuestionId
     * @param  SurveyResponse  $response
     * @param  string|int|null  $surveyheroAnswerId
     * @return SurveyQuestionResponse|null
     */
    protected function findExistingQuestionResponse(string|int $surveyheroQuestionId,
        SurveyResponse $response,
        string|int $surveyheroAnswerId = null): ?SurveyQuestionResponse
    {
        $query = SurveyQuestionResponse::whereHas('surveyQuestion', function($q) use ($surveyheroQuestionId) {
           $q->where('surveyhero_question_id', $surveyheroQuestionId);
        })->where('survey_response_id', $response->id);

        if ($surveyheroAnswerId) {
            $query->whereHas('surveyAnswer', function($q) use ($surveyheroAnswerId) {
                $q->where('surveyhero_answer_id', $surveyheroAnswerId);
            });
        }

        return $query->first();
    }

    /**
     * @param \stdClass $surveyheroQuestionResponse
     * @param SurveyResponse $response
     * @param string $field
     * @return array{ 'surveyhero_question_id': int, 'field': string, 'survey_response_id': int }
     * @throws \Statikbe\Surveyhero\Exceptions\QuestionNotImportedException
     */
    protected function createSurveyQuestionResponseData(\stdClass $surveyheroQuestionResponse,
        SurveyResponse $response,
        string $field): array
    {
        $surveyQuestion = SurveyQuestion::where('surveyhero_question_id', $surveyheroQuestionResponse->element_id)->first();

        if(!$surveyQuestion) {
            throw QuestionNotImportedException::create($surveyheroQuestionResponse->element_id,"Make sure to import survey question with Surveyhero ID $surveyheroQuestionResponse->element_id in the survey_questions table");
        }

        return [
            'survey_question_id' => $surveyQuestion->id,
            'field' => $field,
            'survey_response_id' => $response->id,
        ];
    }

    protected function getChoiceMapping(string|int $choiceId, array $questionMapping): int|string|null
    {
        if (array_key_exists($choiceId, $questionMapping['answer_mapping'])) {
            return $questionMapping['answer_mapping'][$choiceId];
        }

        return null;
    }

    /**
     * @param  mixed  $mappedChoice
     * @param  string  $dataType
     * @param  array  $responseData
     * @param  \stdClass  $surveyheroChoice
     *
     * @throws AnswerNotMappedException
     */
    protected function setChoiceAndConvertToDataType(mixed $mappedChoice,
        string $dataType,
        array &$responseData,
        \stdClass $surveyheroChoice): void
    {
        //if the choice is not mapped try to set the label as string:
        if (! $mappedChoice) {
            if ($dataType === 'string') {
                $responseData['converted_string_value'] = $surveyheroChoice->label;
            } else {
                throw AnswerNotMappedException::create($surveyheroChoice->choice_id, "The choice mapping could not be made for choice ID: $surveyheroChoice->choice_id");
            }
        } else {
            switch ($dataType) {
                case 'int':
                    $responseData['converted_int_value'] = $mappedChoice;
                    break;
                case 'string':
                    $responseData['converted_string_value'] = $mappedChoice;
                    break;
            }
        }
    }
}
