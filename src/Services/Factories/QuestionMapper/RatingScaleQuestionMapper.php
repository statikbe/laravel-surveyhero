<?php

    namespace Statikbe\Surveyhero\Services\Factories\QuestionMapper;

    use Statikbe\Surveyhero\Models\SurveyAnswer;

    class RatingScaleQuestionMapper extends AbstractQuestionMapper {
        const TYPE = 'rating_scale';

        public function mapQuestion(\stdClass $question, int $questionCounter): array {
            $questionData = $this->createQuestionMap($question->element_id,
                $question->question->type,
                SurveyAnswer::CONVERTED_TYPE_STRING,
                $questionCounter);

            if ($question->question->rating_scale->style == 'numerical_scale') {
                $questionData['mapped_data_type'] = SurveyAnswer::CONVERTED_TYPE_INT;
            }

            return $questionData;
        }
    }
