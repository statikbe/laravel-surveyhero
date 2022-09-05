<?php

namespace Statikbe\Surveyhero\Services\Factories\ResponseCreator;

use Statikbe\Surveyhero\Exceptions\AnswerNotImportedException;
use Statikbe\Surveyhero\Exceptions\QuestionNotImportedException;
use Statikbe\Surveyhero\Models\SurveyAnswer;
use Statikbe\Surveyhero\Models\SurveyQuestion;
use Statikbe\Surveyhero\Models\SurveyQuestionResponse;
use Statikbe\Surveyhero\Models\SurveyResponse;

class ChoicesResponseCreator extends AbstractQuestionResponseCreator
{
    const TYPE = 'choices';

    /**
     * @throws \Statikbe\Surveyhero\Exceptions\AnswerNotMappedException
     * @throws \Statikbe\Surveyhero\Exceptions\AnswerNotImportedException
     * @throws \Statikbe\Surveyhero\Exceptions\QuestionNotImportedException
     */
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
            $surveyQuestion = $this->findSurveyQuestion($surveyheroQuestionResponse->element_id);
            $surveyAnswer = $this->findSurveyAnswer($surveyQuestion, $surveyheroChoice->choice_id);

            $responseData = $this->createSurveyQuestionResponseData($surveyQuestion, $response, $surveyAnswer);

            $responseList[] = SurveyQuestionResponse::updateOrCreate([
                    'id' => $existingQuestionResponse->id ?? null,
                ], $responseData);
        }

        return $responseList;
    }
}
