<?php

namespace Statikbe\Surveyhero\Services\Factories\QuestionMapper;

use Statikbe\Surveyhero\Contracts\SurveyAnswerContract;
use Statikbe\Surveyhero\Http\DTO\SurveyElementDTO;

class ImageChoiceListQuestionMapper extends AbstractQuestionMapper
{
    const TYPE = 'image_choice_list';

    public function mapQuestion(SurveyElementDTO $question, int $questionCounter): array
    {
        $questionData = $this->createQuestionMap(
            $question->element_id,
            $question->question->type,
            SurveyAnswerContract::CONVERTED_TYPE_INT,
            $questionCounter
        );

        foreach ($question->question->image_choice_list->choices as $choiceKey => $choice) {
            $questionData['answer_mapping'][$choice->choice_id] = $choiceKey + 1;
        }

        return $questionData;
    }
}
