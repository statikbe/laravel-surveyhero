<?php

namespace Statikbe\Surveyhero\Services\Factories\QuestionMapper;

use Statikbe\Surveyhero\Contracts\SurveyAnswerContract;
use Statikbe\Surveyhero\Http\DTO\SurveyElementDTO;

class FileUploadQuestionMapper extends AbstractQuestionMapper
{
    const TYPE = 'file_upload';

    public function mapQuestion(SurveyElementDTO $question, int $questionCounter): array
    {
        /* Config question_mapping data structure:
         * [
         *   'question_id' => 1387752,
         *   'type' => 'file_upload',
         *   'field' => 'question_1',
         *   'mapped_data_type' => 'string',
         * ],
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
         */
        return $this->createQuestionMap(
            $question->element_id,
            $question->question->type,
            SurveyAnswerContract::CONVERTED_TYPE_STRING,
            $questionCounter
        );
    }
}
