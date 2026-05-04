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
