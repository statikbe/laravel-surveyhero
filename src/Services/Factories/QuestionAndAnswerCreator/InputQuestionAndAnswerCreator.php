<?php

    namespace Statikbe\Surveyhero\Services\Factories\QuestionAndAnswerCreator;

    use Statikbe\Surveyhero\Exceptions\AnswerNotMappedException;
    use Statikbe\Surveyhero\Models\Survey;
    use Statikbe\Surveyhero\Models\SurveyQuestion;
    use Statikbe\Surveyhero\Services\SurveyMappingService;

    class InputQuestionAndAnswerCreator extends AbstractQuestionAndAnswerCreator {
        const TYPE = 'input';

        public function updateOrCreateQuestionAndAnswer(\stdClass $question, Survey $survey, string $lang): SurveyQuestion|array {
            $surveyQuestion = $this->updateOrCreateQuestion($question, $survey, $lang);
            //the answer is different for each user entry, so we cannot save answers for this type of question.
            return $surveyQuestion;
        }
    }
