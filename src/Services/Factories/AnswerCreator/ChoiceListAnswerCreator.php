<?php

namespace Statikbe\Surveyhero\Services\Factories\AnswerCreator;

use Statikbe\Surveyhero\Models\SurveyAnswer;
use Statikbe\Surveyhero\Models\SurveyQuestion;
use Statikbe\Surveyhero\Services\SurveyMappingService;

class ChoiceListAnswerCreator extends AbstractAnswerCreator
{
    const TYPE = 'choice_list';

    /**
     * @throws \Statikbe\Surveyhero\Exceptions\AnswerNotMappedException
     * @throws \Statikbe\Surveyhero\Exceptions\AnswerNotImportedException
     * @throws \Statikbe\Surveyhero\Exceptions\QuestionNotImportedException|\Statikbe\Surveyhero\Exceptions\SurveyNotMappedException
     */
    public function updateOrCreateAnswer(\stdClass $question, SurveyQuestion $surveyQuestion, \stdClass $lang)
    {
        foreach ($question->question->choice_list->choices as $choice) {
            $responseData = [
                'survey_question_id' => $surveyQuestion->id,
                'surveyhero_answer_id' => $choice->choice_id,
                'label' => [
                    $lang->code => $choice->label,
                ],
            ];

            $surveyQuestionMapping = (new SurveyMappingService())->getSurveyQuestionMapping($surveyQuestion->survey);
            $questionMapping = (new SurveyMappingService())->getQuestionMapping($surveyQuestionMapping, $question->element_id);
            $mappedChoice = $this->getChoiceMapping($choice->choice_id, $questionMapping);

            $this->setChoiceAndConvertToDataType($mappedChoice, $questionMapping['mapped_data_type'], $responseData, $choice);

            SurveyAnswer::updateOrCreate([
                'survey_question_id' => $surveyQuestion->id,
                'surveyhero_answer_id' => $choice->choice_id,
            ], $responseData);
        }
    }
}
