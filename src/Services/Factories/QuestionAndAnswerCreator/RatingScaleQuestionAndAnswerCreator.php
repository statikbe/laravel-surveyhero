<?php

namespace Statikbe\Surveyhero\Services\Factories\QuestionAndAnswerCreator;

use Statikbe\Surveyhero\Models\SurveyAnswer;
use Statikbe\Surveyhero\Models\Survey;
use Statikbe\Surveyhero\Models\SurveyQuestion;

class RatingScaleQuestionAndAnswerCreator extends AbstractQuestionAndAnswerCreator
{
    const TYPE = 'rating_scale';

    public function updateOrCreateQuestionAndAnswer(\stdClass $question, Survey $survey, string $lang): SurveyQuestion|array
    {
        $surveyQuestion = $this->updateOrCreateQuestion($question, $survey, $lang);

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
                        $lang => $i,
                    ],
                ]);
        }
        return $surveyQuestion;
    }
}
