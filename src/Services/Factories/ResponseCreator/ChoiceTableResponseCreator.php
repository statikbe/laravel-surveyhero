<?php

namespace Statikbe\Surveyhero\Services\Factories\ResponseCreator;

use Statikbe\Surveyhero\Contracts\SurveyQuestionResponseContract;
use Statikbe\Surveyhero\Contracts\SurveyResponseContract;
use Statikbe\Surveyhero\Services\SurveyMappingService;
use Statikbe\Surveyhero\SurveyheroRegistrar;

class ChoiceTableResponseCreator extends AbstractQuestionResponseCreator
{
    const TYPE = 'choice_table';

    /**
     * {@inheritDoc}
     */
    public function updateOrCreateQuestionResponse(\stdClass $surveyheroQuestionResponse,
        SurveyResponseContract $response,
        array $questionMapping): SurveyQuestionResponseContract|array
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
        $mappingService = new SurveyMappingService;

        foreach ($surveyheroQuestionResponse->choice_table as $surveyheroChoiceQuestion) {
            $subquestionMapping = $mappingService->getSubquestionMapping($surveyheroChoiceQuestion->row_id, $questionMapping);

            $notUpdatedResponses = $this->findAllExistingQuestionResponses($subquestionMapping['question_id'], $response);
            foreach ($surveyheroChoiceQuestion->choices as $surveyheroChoice) {
                $existingQuestionResponse = $this->findExistingQuestionResponse($subquestionMapping['question_id'], $response, $surveyheroChoice->choice_id);
                $surveyQuestion = $this->findSurveyQuestion($surveyheroChoiceQuestion->row_id);
                $surveyAnswer = $this->findSurveyAnswer($surveyQuestion, $surveyheroChoice->choice_id);

                $responseData = $this->createSurveyQuestionResponseData($surveyQuestion, $response, $surveyAnswer);

                $questionResponse = app(SurveyheroRegistrar::class)->getSurveyQuestionResponseClass()::updateOrCreate([
                    'id' => $existingQuestionResponse->id ?? null,
                ], $responseData);
                $responseList[] = $questionResponse;

                //remove from list of not updated responses:
                if ($notUpdatedResponses->contains($questionResponse->id)) {
                    $index = $notUpdatedResponses->search(fn ($item) => $item->id === $questionResponse->id);
                    $notUpdatedResponses->pull($index);
                }
            }

            //remove all not updated responses:
            foreach ($notUpdatedResponses as $notUpdatedResponse) {
                /* @var SurveyQuestionResponseContract $notUpdatedResponse */
                $notUpdatedResponse->delete();
            }
        }

        return $responseList;
    }
}
