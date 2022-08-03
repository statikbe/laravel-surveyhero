<?php

namespace Statikbe\Surveyhero\Services\Factories;

use Statikbe\Surveyhero\Models\SurveyQuestionResponse;
use Statikbe\Surveyhero\Models\SurveyResponse;

class ChoiceTableResponseCreator extends AbstractQuestionResponseCreator
{
    const TYPE = 'choice_table';

    public function updateOrCreateQuestionResponse(\stdClass $surveyheroQuestionResponse,
        SurveyResponse $response,
        array $questionMapping): SurveyQuestionResponse|array
    {
        /* Config question_mapping data structure:
         * [
         *   'question_id' => 5410053,
         *   'type' => 'choice_table',
         *   'subquestion_mapping' => [
         *       [
         *           'question_id' => 13509163,
         *           'field' => 'role_conflict_1',
         *       ],
         *       [
         *           'question_id' => 13509164,
         *           'field' => 'role_conflict_2',
         *       ],
         *       [
         *           'question_id' => 13509165,
         *           'field' => 'role_conflict_3',
         *       ],
         *   ],
         *   'answer_mapping' => [
         *       13509163 => 1,
         *       13509164 => 2,
         *       13509165 => 3,
         *   ],
         * ],
         *
         * Surveyhero API data:
         * {
         *   "element_id": 666978,
         *   "question_text": "On which days do you use following products:",
         *   "type": "choice_table",
         *   "choice_table": [
         *     {
         *       "row_id": 1745892,
         *       "label": "Product A",
         *       "choices": [
         *         {
         *           "choice_id": 177295,
         *           "label": "Tue"
         *         },
         *         {
         *           "choice_id": 177297,
         *           "label": "Thu"
         *         }
         *       ]
         *     },
         *     {
         *       "row_id": 1745894,
         *       "label": "Product C",
         *       "choices": [
         *         {
         *           "choice_id": 177296,
         *           "label": "Wed"
         *         }
         *       ]
         *     }
         *   ]
         * }
         */

        $responseList = [];
        //TODO remove deselected answers
        foreach ($surveyheroQuestionResponse->choice_table as $surveyheroChoiceQuestion) {
            $subquestionMapping = $this->getSubquestionMapping($surveyheroChoiceQuestion->row_id, $questionMapping);
            foreach ($surveyheroChoiceQuestion->choices as $surveyheroChoice) {
                $existingQuestionResponse = $this->findExistingQuestionResponse($subquestionMapping['question_id'], $response, $surveyheroChoice->choice_id);
                $responseData = $this->createSurveyQuestionResponseData($surveyheroQuestionResponse, $response, $subquestionMapping['field']);
                $mappedChoice = $this->getChoiceMapping($surveyheroChoice->choice_id, $questionMapping);

                $this->setChoiceAndConvertToDataType($mappedChoice, $questionMapping['mapped_data_type'], $responseData, $surveyheroChoice);
                $responseData['surveyhero_answer_lbl'] = $surveyheroChoice->label;

                $responseList[] = SurveyQuestionResponse::updateOrCreate([
                    'id' => $existingQuestionResponse->id ?? null,
                ], $responseData);
            }
        }

        return $responseList;
    }

    protected function getSubquestionMapping(string|int $questionId, array $questionMapping): array
    {
        $questionMap = array_filter($questionMapping['subquestion_mapping'], function ($question, $key) use ($questionId) {
            return $question['question_id'] == $questionId;
        }, ARRAY_FILTER_USE_BOTH);

        if (count($questionMapping) > 0) {
            $questionMap = reset($questionMap);
        }

        return $questionMap;
    }
}
