<?php

namespace Statikbe\Surveyhero\Services;

use Illuminate\Support\Facades\DB;
use Statikbe\Surveyhero\Contracts\SurveyContract;
use Statikbe\Surveyhero\Http\SurveyheroClient;
use Statikbe\Surveyhero\Services\Factories\QuestionAndAnswerCreator\ChoiceListQuestionAndAnswerCreator;
use Statikbe\Surveyhero\Services\Factories\QuestionAndAnswerCreator\ChoiceTableQuestionAndAnswerCreator;
use Statikbe\Surveyhero\Services\Factories\QuestionAndAnswerCreator\InputQuestionAndAnswerCreator;
use Statikbe\Surveyhero\Services\Factories\QuestionAndAnswerCreator\QuestionAndAnswerCreator;
use Statikbe\Surveyhero\Services\Factories\QuestionAndAnswerCreator\RatingScaleQuestionAndAnswerCreator;

class SurveyQuestionsAndAnswersImportService extends AbstractSurveyheroAPIService
{
    /**
     * @param  SurveyContract  $survey
     *
     * @throws \Exception
     */
    public function importSurveyQuestionsAndAnswers(SurveyContract $survey): array
    {
        $notImported = [
            'question' => [],
        ];
        $languages = $this->client->getSurveyLanguages($survey->surveyhero_id);

        foreach ($languages as $lang) {
            $questions = $this->client->getSurveyQuestions($survey->surveyhero_id, $lang->code);

            foreach ($questions as $question) {
                try {
                    DB::beginTransaction();

                    $QAndACreator = $this->getQuestionAndAnswerCreator($question->question->type);
                    if ($QAndACreator) {
                        $QAndACreator->updateOrCreateQuestionAndAnswer($question, $survey, $lang->code);
                    } else {
                        $notImported['question'][] = [$question->element_id, "Question type {$question->question->type} not supported"];
                    }

                    DB::commit();
                } catch (\Exception $exception) {
                    DB::rollBack();
                    throw $exception;
                }
            }
        }

        return $notImported;
    }

    /*private function updateOrCreateChoiceListAnswer($question, $surveyQuestion, $lang): void
    {
        foreach ($question->choice_list->choices as $choice) {
            $responseData = [
                'survey_question_id' => $surveyQuestion->id,
                'surveyhero_answer_id' => $choice->choice_id,
                'label' => [
                    $lang->code => $choice->label,
                ],
            ];

            SurveyAnswer::updateOrCreate([
                'survey_question_id' => $surveyQuestion->id,
                'surveyhero_answer_id' => $choice->choice_id,
            ], $responseData);
        }
    }*/

    /*private function updateOrCreateNumericRatingScaleAnswer($question, $surveyQuestion, $lang): void
    {
        $ratingScale = $question->rating_scale;
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
    }*/

    private function getQuestionAndAnswerCreator(string $surveyheroFieldType): QuestionAndAnswerCreator|null
    {
        return match ($surveyheroFieldType) {
            ChoiceListQuestionAndAnswerCreator::TYPE => new ChoiceListQuestionAndAnswerCreator(),
            ChoiceTableQuestionAndAnswerCreator::TYPE => new ChoiceTableQuestionAndAnswerCreator(),
            RatingScaleQuestionAndAnswerCreator::TYPE => new RatingScaleQuestionAndAnswerCreator(),
            InputQuestionAndAnswerCreator::TYPE => new InputQuestionAndAnswerCreator(),
            default => null,
        };
    }
}
