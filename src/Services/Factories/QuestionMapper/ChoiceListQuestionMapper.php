<?php

    namespace Statikbe\Surveyhero\Services\Factories\QuestionMapper;

    use Statikbe\Surveyhero\Models\SurveyAnswer;
    use Statikbe\Surveyhero\Services\Factories\QuestionAndAnswerCreator\ChoiceListQuestionAndAnswerCreator;

    class ChoiceListQuestionMapper extends AbstractQuestionMapper {
        const TYPE = 'choice_list';

        public function mapQuestion(\stdClass $question, int $questionCounter): array {
            $questionData = $this->createQuestionMap($question->element_id,
                $question->question->type,
                SurveyAnswer::CONVERTED_TYPE_INT,
                $questionCounter);

            foreach ($question->question->choice_list->choices as $choiceKey => $choice) {
                $questionData['answer_mapping'][$choice->choice_id] = $choiceKey + 1;
            }
            return $questionData;
        }
    }
