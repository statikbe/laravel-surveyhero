<?php

namespace Statikbe\Surveyhero\Services\Factories\QuestionMapper;

use Statikbe\Surveyhero\Contracts\SurveyAnswerContract;
use Statikbe\Surveyhero\Http\DTO\SurveyElementDTO;

class InputListQuestionMapper extends AbstractQuestionMapper
{
    const TYPE = 'input_list';

    public function mapQuestion(SurveyElementDTO $question, int $questionCounter): array
    {
        /* Config question_mapping data structure:
         * [
         *   'question_id' => 667012,
         *   'type' => 'input_list',
         *   'mapped_data_type' => 'string',   // or 'int' when accepts->type is 'number'
         *   'subquestion_mapping' => [
         *     1745983 => ['question_id' => 1745983, 'field' => 'question_1_1'],
         *     1745984 => ['question_id' => 1745984, 'field' => 'question_1_2'],
         *   ],
         * ],
         *
         * Surveyhero API data:
         * {
         *   "element_id": 667012,
         *   "type": "question",
         *   "question": {
         *     "question_id": 667012,
         *     "type": "input_list",
         *     "question_text": "Please enter your contact details:",
         *     "input_list": {
         *       "accepts": { "type": "text", "text": { "max_number_of_characters": null } },
         *       "inputs": [
         *         { "input_id": 1745983, "label": "First and last name" },
         *         { "input_id": 1745984, "label": "Street address" }
         *       ]
         *     }
         *   }
         * }
         *
         * Note: accepts->type can be 'text', 'number', 'email', 'url', or 'date'.
         * Only 'number' maps to CONVERTED_TYPE_INT; all others (email, url, date, text)
         * arrive under answer->text and map to CONVERTED_TYPE_STRING.
         */
        $acceptsType = $question->question->input_list->accepts->type;
        $mappedDataType = $acceptsType === 'number'
            ? SurveyAnswerContract::CONVERTED_TYPE_INT
            : SurveyAnswerContract::CONVERTED_TYPE_STRING;

        $mapping = [
            'question_id' => $question->element_id,
            'type' => $question->question->type,
            'subquestion_mapping' => [],
            'mapped_data_type' => $mappedDataType,
        ];

        $subquestionIndex = 1;
        foreach ($question->question->input_list->inputs as $input) {
            $mapping['subquestion_mapping'][$input->input_id] = [
                'question_id' => $input->input_id,
                'field' => "question_{$questionCounter}_{$subquestionIndex}",
            ];
            $subquestionIndex++;
        }

        return $mapping;
    }
}
