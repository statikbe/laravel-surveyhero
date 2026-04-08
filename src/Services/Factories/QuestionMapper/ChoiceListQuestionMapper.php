<?php

namespace Statikbe\Surveyhero\Services\Factories\QuestionMapper;

use Statikbe\Surveyhero\Contracts\SurveyAnswerContract;

class ChoiceListQuestionMapper extends AbstractQuestionMapper
{
    const TYPE = 'choice_list';

    public function mapQuestion(\stdClass $question, int $questionCounter): array
    {
        $questionData = $this->createQuestionMap($question->element_id,
            $question->question->type,
            SurveyAnswerContract::CONVERTED_TYPE_INT,
            $questionCounter);

        foreach ($question->question->choice_list->choices as $choiceKey => $choice) {
            $questionData['answer_mapping'][$choice->choice_id] = $choiceKey + 1;
        }

        return $questionData;
    }
}
