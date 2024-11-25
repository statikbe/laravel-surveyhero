<?php

namespace Statikbe\Surveyhero\Services\Factories\QuestionAndAnswerCreator;

use Statikbe\Surveyhero\Contracts\SurveyContract;
use Statikbe\Surveyhero\Contracts\SurveyQuestionContract;
use Statikbe\Surveyhero\Services\SurveyMappingService;
use Statikbe\Surveyhero\SurveyheroRegistrar;

class ChoiceListQuestionAndAnswerCreator extends AbstractQuestionAndAnswerCreator
{
    const TYPE = 'choice_list';

    /**
     * @throws \Statikbe\Surveyhero\Exceptions\AnswerNotMappedException
     * @throws \Statikbe\Surveyhero\Exceptions\QuestionNotMappedException
     * @throws \Statikbe\Surveyhero\Exceptions\SurveyNotMappedException
     */
    public function updateOrCreateQuestionAndAnswer(\stdClass $question, SurveyContract $survey, string $lang): SurveyQuestionContract|array
    {
        $surveyQuestion = $this->updateOrCreateQuestion($survey, $lang, $question->element_id, $question->question->question_text);

        foreach ($question->question->choice_list->choices as $choice) {
            $responseData = [
                'survey_question_id' => $surveyQuestion->id,
                'surveyhero_answer_id' => $choice->choice_id,
                'label' => [
                    $lang => $choice->label,
                ],
            ];

            $questionMapping = (new SurveyMappingService)->getQuestionMappingForSurvey($survey, $question->element_id);
            $mappedChoice = $this->getChoiceMapping($choice->choice_id, $question->element_id, $questionMapping);

            $this->setChoiceAndConvertToDataType($mappedChoice, $questionMapping['mapped_data_type'], $responseData, $choice);

            app(SurveyheroRegistrar::class)->getSurveyAnswerClass()::updateOrCreate([
                'survey_question_id' => $surveyQuestion->id,
                'surveyhero_answer_id' => $choice->choice_id,
            ], $responseData);
        }

        return $surveyQuestion;
    }
}
