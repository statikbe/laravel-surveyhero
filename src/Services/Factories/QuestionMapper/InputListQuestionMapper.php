<?php

namespace Statikbe\Surveyhero\Services\Factories\QuestionMapper;

use Statikbe\Surveyhero\Contracts\SurveyAnswerContract;
use Statikbe\Surveyhero\Http\DTO\SurveyElementDTO;

class InputListQuestionMapper extends AbstractQuestionMapper
{
    const TYPE = 'input_list';

    public function mapQuestion(SurveyElementDTO $question, int $questionCounter): array
    {
        $acceptsType = $question->question->input_list->accepts->type;
        $mappedDataType = $acceptsType === 'number'
            ? SurveyAnswerContract::CONVERTED_TYPE_INT
            : SurveyAnswerContract::CONVERTED_TYPE_STRING;

        $mapping = [
            'question_id'         => $question->element_id,
            'type'                => $question->question->type,
            'subquestion_mapping' => [],
            'mapped_data_type'    => $mappedDataType,
        ];

        $subquestionIndex = 1;
        foreach ($question->question->input_list->inputs as $input) {
            $mapping['subquestion_mapping'][$input->input_id] = [
                'question_id' => $input->input_id,
                'field'       => "question_{$questionCounter}_{$subquestionIndex}",
            ];
            $subquestionIndex++;
        }

        return $mapping;
    }
}
