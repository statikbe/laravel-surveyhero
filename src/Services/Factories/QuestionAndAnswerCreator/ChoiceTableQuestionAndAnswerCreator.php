<?php

    namespace Statikbe\Surveyhero\Services\Factories\QuestionAndAnswerCreator;

    use Statikbe\Surveyhero\Exceptions\AnswerNotMappedException;
    use Statikbe\Surveyhero\Models\Survey;
    use Statikbe\Surveyhero\Models\SurveyAnswer;
    use Statikbe\Surveyhero\Models\SurveyQuestion;
    use Statikbe\Surveyhero\Services\SurveyMappingService;

    class ChoiceTableQuestionAndAnswerCreator extends AbstractQuestionAndAnswerCreator {
        const TYPE = 'choice_table';

        public function updateOrCreateQuestionAndAnswer(\stdClass $question, Survey $survey, string $lang): SurveyQuestion|array
        {
            $questions = [];
            foreach ($question->question->choice_table->rows as $rowQuestion) {
                $surveyQuestion = $this->updateOrCreateQuestion($survey, $lang, $question->element_id, $rowQuestion->label, $rowQuestion->row_id);

                foreach ($question->question->choice_table->choices as $choice) {
                    $responseData = [
                        'survey_question_id' => $surveyQuestion->id,
                        'surveyhero_answer_id' => $choice->choice_id,
                        'label' => [
                            $lang => $choice->label,
                        ],
                    ];

                    $questionMapping = (new SurveyMappingService())->getQuestionMappingForSurvey($survey, $question->element_id);
                    $mappedChoice = $this->getChoiceMapping($choice->choice_id, $questionMapping);

                    $this->setChoiceAndConvertToDataType($mappedChoice, $questionMapping['mapped_data_type'], $responseData, $choice);

                    SurveyAnswer::updateOrCreate([
                        'survey_question_id' => $surveyQuestion->id,
                        'surveyhero_answer_id' => $choice->choice_id,
                    ], $responseData);
                }

                $questions[] = $surveyQuestion;
            }
            return $questions;
        }
    }
