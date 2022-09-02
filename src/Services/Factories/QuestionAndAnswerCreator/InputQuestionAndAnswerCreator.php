<?php

    namespace Statikbe\Surveyhero\Services\Factories\QuestionAndAnswerCreator;

    use Statikbe\Surveyhero\Exceptions\AnswerNotMappedException;
    use Statikbe\Surveyhero\Models\Survey;
    use Statikbe\Surveyhero\Models\SurveyQuestion;
    use Statikbe\Surveyhero\Services\SurveyMappingService;

    class InputQuestionAndAnswerCreator extends AbstractQuestionAndAnswerCreator {
        const TYPE = 'input';

        /**
         * @throws \Statikbe\Surveyhero\Exceptions\SurveyNotMappedException
         * @throws \Statikbe\Surveyhero\Exceptions\QuestionNotMappedException
         */
        public function updateOrCreateQuestionAndAnswer(\stdClass $question, Survey $survey, string $lang): SurveyQuestion|array {
            $surveyQuestion = $this->updateOrCreateQuestion($survey, $lang, $question->element_id, $question->question->question_text);
            //the answer is different for each user entry, so we cannot save answers for this type of question.
            return $surveyQuestion;
        }
    }
