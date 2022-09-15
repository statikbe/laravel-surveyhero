<?php

namespace Statikbe\Surveyhero\Services\Factories\ResponseCreator;

use Statikbe\Surveyhero\Contracts\SurveyQuestionResponseContract;
use Statikbe\Surveyhero\Contracts\SurveyResponseContract;
use Statikbe\Surveyhero\SurveyheroRegistrar;

class ChoicesResponseCreator extends AbstractQuestionResponseCreator
{
    const TYPE = 'choices';

    /**
     * {@inheritDoc}
     */
    public function updateOrCreateQuestionResponse(
        \stdClass $surveyheroQuestionResponse,
        SurveyResponseContract $response,
        array $questionMapping): SurveyQuestionResponseContract|array
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

        $notUpdatedResponses = $this->findAllExistingQuestionResponses($questionMapping['question_id'], $response);

        foreach ($surveyheroQuestionResponse->choices as $surveyheroChoice) {
            $existingQuestionResponse = $this->findExistingQuestionResponse($questionMapping['question_id'], $response, $surveyheroChoice->choice_id);
            $surveyQuestion = $this->findSurveyQuestion($surveyheroQuestionResponse->element_id);
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

        return $responseList;
    }
}
