<?php

namespace Statikbe\Surveyhero\Services;

use Illuminate\Support\Facades\DB;
use Statikbe\Surveyhero\Http\SurveyheroClient;
use Statikbe\Surveyhero\Models\Survey;
use Statikbe\Surveyhero\Models\SurveyAnswer;
use Statikbe\Surveyhero\Models\SurveyQuestion;
use Statikbe\Surveyhero\Services\Factories\AnswerCreator\ChoiceListAnswerCreator;
use Statikbe\Surveyhero\Services\Factories\AnswerCreator\RatingScaleAnswerCreator;

class SurveyQuestionsAndAnswersImportService
{
    private SurveyheroClient $client;

    public function __construct(SurveyheroClient $client)
    {
        $this->client = $client;
    }

    /**
     * @throws \Exception
     */
    public function importSurveyQuestionsAndAnswers(Survey $survey): array
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

                    $surveyQuestion = $this->updateOrCreateQuestion($question, $survey, $lang);

                    $answerCreator = $this->getAnswerCreator($question->question->type);
                    if ($answerCreator) {
                        $answerCreator->updateOrCreateAnswer($question, $surveyQuestion, $lang);
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

    private function updateOrCreateQuestion($question, $survey, $lang)
    {
        return SurveyQuestion::updateOrCreate(
            ['surveyhero_question_id' => $question->element_id],
            [
                'survey_id' => $survey->id,
                'surveyhero_question_id' => $question->element_id,
                'label' => [
                    $lang->code => $question->question->question_text ?? '',
                ],
            ]);
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

    private function getAnswerCreator(string $surveyheroFieldType)
    {
        return match ($surveyheroFieldType) {
            ChoiceListAnswerCreator::TYPE => new ChoiceListAnswerCreator(),
            RatingScaleAnswerCreator::TYPE => new RatingScaleAnswerCreator(),
            default => null,
        };
    }
}
