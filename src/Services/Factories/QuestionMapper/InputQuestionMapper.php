<?php

namespace Statikbe\Surveyhero\Services\Factories\QuestionMapper;

use Statikbe\Surveyhero\Contracts\SurveyAnswerContract;
use Statikbe\Surveyhero\Http\DTO\SurveyElementDTO;

class InputQuestionMapper extends AbstractQuestionMapper
{
    const TYPE = 'input';

    public function mapQuestion(SurveyElementDTO $question, int $questionCounter): array
    {
        $questionData = $this->createQuestionMap($question->element_id,
            $question->question->type,
            SurveyAnswerContract::CONVERTED_TYPE_STRING,
            $questionCounter);

        return $questionData;
    }
}
