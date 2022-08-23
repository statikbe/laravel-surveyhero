<?php

namespace Statikbe\Surveyhero\Services\Factories\AnswerCreator;

use Statikbe\Surveyhero\Models\SurveyAnswer;
use Statikbe\Surveyhero\Models\SurveyQuestion;

class RatingScaleAnswerCreator extends AbstractAnswerCreator
{
    const TYPE = 'rating_scale';

    public function updateOrCreateAnswer(\stdClass $question, SurveyQuestion $surveyQuestion, \stdClass $lang)
    {
        $ratingScale = $question->question->rating_scale;
        $minValue = $ratingScale->left->value;
        $maxValue = $ratingScale->right->value;
        $stepSize = $ratingScale->step_size;

        for ($i = $minValue; $i <= $maxValue; $i += $stepSize) {
            SurveyAnswer::updateOrCreate(
                [
                    'survey_question_id' => $surveyQuestion->id,
                    'converted_int_value' => $i,
                ],
                [
                    'survey_question_id' => $surveyQuestion->id,
                    'surveyhero_answer_id' => null,
                    'converted_int_value' => $i,
                    'label' => [
                        $lang->code => $i,
                    ],
                ]);
        }
    }
}
