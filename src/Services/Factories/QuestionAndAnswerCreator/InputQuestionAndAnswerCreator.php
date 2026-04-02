<?php

namespace Statikbe\Surveyhero\Services\Factories\QuestionAndAnswerCreator;

use Statikbe\Surveyhero\Contracts\SurveyContract;
use Statikbe\Surveyhero\Contracts\SurveyQuestionContract;
use Statikbe\Surveyhero\Exceptions\QuestionNotMappedException;
use Statikbe\Surveyhero\Exceptions\SurveyNotMappedException;

class InputQuestionAndAnswerCreator extends AbstractQuestionAndAnswerCreator
{
    const TYPE = 'input';

    /**
     * @throws SurveyNotMappedException
     * @throws QuestionNotMappedException
     */
    public function updateOrCreateQuestionAndAnswer(\stdClass $question, SurveyContract $survey, string $lang): SurveyQuestionContract|array
    {
        $surveyQuestion = $this->updateOrCreateQuestion($survey, $lang, $question->element_id, $question->question->question_text);

        // the answer is different for each user entry, so we cannot save answers for this type of question.
        return $surveyQuestion;
    }
}
