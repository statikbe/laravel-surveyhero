<?php

namespace Statikbe\Surveyhero\Services\Factories;

use Statikbe\Surveyhero\Models\SurveyQuestionResponse;
use Statikbe\Surveyhero\Models\SurveyResponse;

class ChoicesResponseCreator extends AbstractQuestionResponseCreator
{
    const TYPE = 'choices';

    public function updateOrCreateQuestionResponse(
        \stdClass $surveyheroQuestionResponse,
        SurveyResponse $response,
        array $questionMapping): SurveyQuestionResponse|array
    {
        /* Config question_mapping data structure:
         * [
         *   'question_id' => 5410054,
         *   'type' => 'choices',
         *   'field' => 'team_conflict',
         *   'answer_mapping' => [
         *       13509166 => 1,
         *       13509167 => 2,
         *       13509168 => 3,
         *   ],
         * ],
         *
         * Surveyhero API data:
         * {
         *   "element_id": 666809,
         *   "question_text": "In which department do you work?",
         *   "type": "choices",
         *   "choices": [
         *     {
         *       "choice_id": 1745223,
         *       "label": "Department B"
         *     }
         *   ]
         * }
         */

        $responseList = [];

        //TODO remove deselected answers
        foreach ($surveyheroQuestionResponse->choices as $surveyheroChoice) {
            $existingQuestionResponse = $this->findExistingQuestionResponse($questionMapping['question_id'], $response, $surveyheroChoice->choice_id);
            $responseData = $this->createSurveyQuestionResponseData($surveyheroQuestionResponse, $response, $questionMapping['field']);
            $mappedChoice = $this->getChoiceMapping($surveyheroChoice->choice_id, $questionMapping);
            $responseData['surveyhero_answer_id'] = $surveyheroChoice->choice_id;

            $this->setChoiceAndConvertToDataType($mappedChoice, $questionMapping['mapped_data_type'], $responseData, $surveyheroChoice);
            //$responseData['surveyhero_answer_lbl'] = $surveyheroChoice->label;

            $responseList[] = SurveyQuestionResponse::updateOrCreate([
                'id' => $existingQuestionResponse->id ?? null,
            ], $responseData);
        }

        return $responseList;
    }
}
