<?php

namespace Statikbe\Surveyhero\Services\Factories\QuestionAndAnswerCreator;

use Statikbe\Surveyhero\Contracts\SurveyContract;
use Statikbe\Surveyhero\Contracts\SurveyQuestionContract;
use Statikbe\Surveyhero\Exceptions\QuestionNotMappedException;
use Statikbe\Surveyhero\Exceptions\SurveyNotMappedException;
use Statikbe\Surveyhero\Http\DTO\SurveyElementDTO;

class InputListQuestionAndAnswerCreator extends AbstractQuestionAndAnswerCreator
{
    const TYPE = 'input_list';

    /**
     * @throws SurveyNotMappedException
     * @throws QuestionNotMappedException
     *
     * Surveyhero API data:
     * {
     *   "element_id": 667012,
     *   "type": "question",
     *   "question": {
     *     "question_id": 667012,
     *     "type": "input_list",
     *     "question_text": "Please enter your contact details:",
     *     "input_list": {
     *       "accepts": { "type": "text", "text": { "max_number_of_characters": null } },
     *       "inputs": [
     *         { "input_id": 1745983, "label": "First and last name" },
     *         { "input_id": 1745984, "label": "Street address" }
     *       ]
     *     }
     *   }
     * }
     *
     * Each input is imported as a separate SurveyQuestion row (keyed by input_id).
     * No fixed SurveyAnswer rows are created — answers are stored dynamically per response.
     */
    public function updateOrCreateQuestionAndAnswer(SurveyElementDTO $question, SurveyContract $survey, string $lang): SurveyQuestionContract|array
    {
        $questions = [];
        foreach ($question->question->input_list->inputs as $input) {
            $questions[] = $this->updateOrCreateQuestion(
                $survey, $lang,
                $question->element_id,
                $input->label,
                $input->input_id
            );
        }

        return $questions;
    }
}
