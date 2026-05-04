<?php

namespace Statikbe\Surveyhero\Services\Factories\QuestionMapper;

use Statikbe\Surveyhero\Contracts\SurveyAnswerContract;
use Statikbe\Surveyhero\Http\DTO\SurveyElementDTO;

class RatingScaleQuestionMapper extends AbstractQuestionMapper
{
    const TYPE = 'rating_scale';

    public function mapQuestion(SurveyElementDTO $question, int $questionCounter): array
    {
        $questionData = $this->createQuestionMap($question->element_id,
            $question->question->type,
            SurveyAnswerContract::CONVERTED_TYPE_STRING,
            $questionCounter);

        if (in_array($question->question->rating_scale->style, ['numerical_scale', 'slider'])) {
            $questionData['mapped_data_type'] = SurveyAnswerContract::CONVERTED_TYPE_INT;
        }

        return $questionData;
    }
}
