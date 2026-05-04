<?php

namespace Statikbe\Surveyhero\Services\Factories\QuestionAndAnswerCreator;

use Statikbe\Surveyhero\Contracts\SurveyContract;
use Statikbe\Surveyhero\Contracts\SurveyQuestionContract;
use Statikbe\Surveyhero\Exceptions\QuestionNotMappedException;
use Statikbe\Surveyhero\Exceptions\SurveyNotMappedException;
use Statikbe\Surveyhero\Http\DTO\SurveyElementDTO;

class FileUploadQuestionAndAnswerCreator extends AbstractQuestionAndAnswerCreator
{
    const TYPE = 'file_upload';

    /**
     * @throws SurveyNotMappedException
     * @throws QuestionNotMappedException
     */
    public function updateOrCreateQuestionAndAnswer(SurveyElementDTO $question, SurveyContract $survey, string $lang): SurveyQuestionContract|array
    {
        return $this->updateOrCreateQuestion($survey, $lang, $question->element_id, $question->question->question_text);
    }
}
