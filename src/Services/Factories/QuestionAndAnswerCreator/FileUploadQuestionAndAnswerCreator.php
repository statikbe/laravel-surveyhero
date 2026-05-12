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
     *
     * Surveyhero API data:
     * {
     *   "element_id": 1387752,
     *   "type": "question",
     *   "question": {
     *     "question_id": 1387752,
     *     "type": "file_upload",
     *     "question_text": "Please upload your image here:",
     *     "file_upload": {
     *       "max_file_size_in_mb": 25,
     *       "accepted_file_types": ["gif", "jpg", "jpeg", "png"]
     *     }
     *   }
     * }
     *
     * No fixed answers are created for file_upload questions — each response
     * stores the uploaded file URL as a dynamic SurveyAnswer.
     */
    public function updateOrCreateQuestionAndAnswer(SurveyElementDTO $question, SurveyContract $survey, string $lang): SurveyQuestionContract|array
    {
        return $this->updateOrCreateQuestion($survey, $lang, $question->element_id, $question->question->question_text);
    }
}
