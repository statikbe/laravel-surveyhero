<?php

namespace Statikbe\Surveyhero\Services\Factories;

use Statikbe\Surveyhero\Exceptions\AnswerNotMappedException;
use Statikbe\Surveyhero\Models\SurveyQuestionResponse;
use Statikbe\Surveyhero\Models\SurveyResponse;

abstract class AbstractQuestionResponseCreator implements QuestionResponseCreator
{
    protected function findExistingQuestionResponse(string|int $surveyheroQuestionId,
        SurveyResponse $response,
        string|int $surveyheroAnswerId = null): ?SurveyQuestionResponse
    {
        $query = SurveyQuestionResponse::where('surveyhero_question_id', $surveyheroQuestionId)
            ->where('survey_response_id', $response->id);
        if ($surveyheroAnswerId) {
            $query->where('surveyhero_answer_id', $surveyheroAnswerId);
        }

        return $query->first();
    }

    protected function createSurveyQuestionResponseData(\stdClass $surveyheroQuestionResponse,
        SurveyResponse $response,
        string $field): array
    {
        return [
            'surveyhero_question_id' => $surveyheroQuestionResponse->element_id,
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

    protected function setChoiceAndConvertToDataType(mixed $mappedChoice, string $dataType, array &$responseData, \stdClass $surveyheroChoice): void
    {
        //if the choice is not mapped try to set the label as string:
        if(!$mappedChoice) {
            if($dataType === 'string') {
                $responseData['converted_string_value'] = $surveyheroChoice->label;
            }
            else {
                throw AnswerNotMappedException::create($surveyheroChoice->choice_id, "The choice mapping could not be made for choice ID: $surveyheroChoice->choice_id");
            }
        }
        else {
            switch($dataType) {
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
