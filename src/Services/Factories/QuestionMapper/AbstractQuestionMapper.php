<?php

    namespace Statikbe\Surveyhero\Services\Factories\QuestionMapper;

    use Statikbe\Surveyhero\Models\SurveyAnswer;

    abstract class AbstractQuestionMapper implements QuestionMapper {
        /**
         * @param int $questionId
         * @param string $questionType
         * @param string $mappedDataType
         * @param int|string $questionFieldSuffix
         * @return array{'question_id': int, 'type': string, 'field': string, 'mapped_data_type': string }
         */
        public function createQuestionMap(int $questionId, string $questionType, string $mappedDataType, int|string $questionFieldSuffix): array {
            return [
                'question_id' => $questionId,
                'type' => $questionType,
                'field' => 'question_'.$questionFieldSuffix,
                'mapped_data_type' => $mappedDataType,
            ];
        }
    }
