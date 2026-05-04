<?php

namespace Statikbe\Surveyhero\Services\Factories\QuestionMapper;

use Statikbe\Surveyhero\Contracts\SurveyAnswerContract;
use Statikbe\Surveyhero\Http\DTO\SurveyElementDTO;

class FileUploadQuestionMapper extends AbstractQuestionMapper
{
    const TYPE = 'file_upload';

    public function mapQuestion(SurveyElementDTO $question, int $questionCounter): array
    {
        return $this->createQuestionMap(
            $question->element_id,
            $question->question->type,
            SurveyAnswerContract::CONVERTED_TYPE_STRING,
            $questionCounter
        );
    }
}
