<?php

namespace Statikbe\Surveyhero\Services\Factories\QuestionMapper;

abstract class AbstractQuestionMapper implements QuestionMapper
{
    /**
     * @return array{'question_id': int, 'type': string, 'field': string, 'mapped_data_type': string }
     */
    public function createQuestionMap(int $questionId, string $questionType, string $mappedDataType, int|string $questionFieldSuffix): array
    {
        return [
            'question_id' => $questionId,
            'type' => $questionType,
            'field' => 'question_'.$questionFieldSuffix,
            'mapped_data_type' => $mappedDataType,
        ];
    }
}
