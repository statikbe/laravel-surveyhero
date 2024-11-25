<?php

namespace Statikbe\Surveyhero\Services\Factories\QuestionAndAnswerCreator;

use Statikbe\Surveyhero\Contracts\SurveyAnswerContract;
use Statikbe\Surveyhero\Contracts\SurveyContract;
use Statikbe\Surveyhero\Contracts\SurveyQuestionContract;
use Statikbe\Surveyhero\Services\SurveyMappingService;
use Statikbe\Surveyhero\SurveyheroRegistrar;

class RatingScaleQuestionAndAnswerCreator extends AbstractQuestionAndAnswerCreator
{
    const TYPE = 'rating_scale';

    /**
     * @throws \Statikbe\Surveyhero\Exceptions\SurveyNotMappedException
     * @throws \Statikbe\Surveyhero\Exceptions\QuestionNotMappedException
     */
    public function updateOrCreateQuestionAndAnswer(\stdClass $question, SurveyContract $survey, string $lang): SurveyQuestionContract|array
    {
        $surveyQuestion = $this->updateOrCreateQuestion($survey, $lang, $question->element_id, $question->question->question_text);

        $ratingScale = $question->question->rating_scale;
        $minValue = $ratingScale->left->value;
        $maxValue = $ratingScale->right->value;
        $stepSize = $ratingScale->step_size;

        $questionMapping = (new SurveyMappingService)->getQuestionMappingForSurvey($survey, $question->element_id);
        $mappedDataType = $questionMapping['mapped_data_type'] ?? SurveyAnswerContract::CONVERTED_TYPE_INT;

        for ($i = $minValue; $i <= $maxValue; $i += $stepSize) {

            app(SurveyheroRegistrar::class)->getSurveyAnswerClass()::updateOrCreate(
                [
                    'survey_question_id' => $surveyQuestion->id,
                    //make sure the answer is searched for in the right mapped column:
                    "converted_{$mappedDataType}_value" => $mappedDataType === SurveyAnswerContract::CONVERTED_TYPE_INT ? $i : str($i),
                ],
                [
                    'surveyhero_answer_id' => null,
                    'label' => [
                        $lang => $i,
                    ],
                ]);
        }

        return $surveyQuestion;
    }
}
