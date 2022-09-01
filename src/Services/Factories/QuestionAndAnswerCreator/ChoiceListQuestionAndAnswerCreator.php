<?php

namespace Statikbe\Surveyhero\Services\Factories\QuestionAndAnswerCreator;

use Statikbe\Surveyhero\Models\Survey;
use Statikbe\Surveyhero\Models\SurveyAnswer;
use Statikbe\Surveyhero\Models\SurveyQuestion;
use Statikbe\Surveyhero\Services\SurveyMappingService;

class ChoiceListQuestionAndAnswerCreator extends AbstractQuestionAndAnswerCreator
{
    const TYPE = 'choice_list';

    /**
     * @throws \Statikbe\Surveyhero\Exceptions\AnswerNotMappedException
     * @throws \Statikbe\Surveyhero\Exceptions\AnswerNotImportedException
     * @throws \Statikbe\Surveyhero\Exceptions\QuestionNotImportedException|\Statikbe\Surveyhero\Exceptions\SurveyNotMappedException
     */
    public function updateOrCreateQuestionAndAnswer(\stdClass $question, Survey $survey, string $lang): SurveyQuestion|array
    {
        $surveyQuestion = $this->updateOrCreateQuestion($question, $survey, $lang);

        foreach ($question->question->choice_list->choices as $choice) {
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

        return $surveyQuestion;
    }
}
