<?php

namespace Statikbe\Surveyhero\Services;

use Illuminate\Support\Facades\DB;
use Statikbe\Surveyhero\Exceptions\AnswerNotMappedException;
use Statikbe\Surveyhero\Exceptions\ResponseCreatorNotImplemented;
use Statikbe\Surveyhero\Exceptions\SurveyNotMappedException;
use Statikbe\Surveyhero\Http\SurveyheroClient;
use Statikbe\Surveyhero\Models\Survey;
use Statikbe\Surveyhero\Models\SurveyResponse;
use Statikbe\Surveyhero\Services\Factories\ChoicesResponseCreator;
use Statikbe\Surveyhero\Services\Factories\ChoiceTableResponseCreator;
use Statikbe\Surveyhero\Services\Factories\NumberResponseCreator;
use Statikbe\Surveyhero\Services\Factories\QuestionResponseCreator;
use Statikbe\Surveyhero\Services\Factories\TextResponseCreator;
use Statikbe\Surveyhero\Models\SurveyAnswer;
use Statikbe\Surveyhero\Models\SurveyQuestion;

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

        foreach($languages as $lang) {
            $questions = $this->client->getSurveyQuestions($survey->surveyhero_id, $lang->code);

            foreach($questions as $question) {
                try {
                    DB::beginTransaction();

                    $surveyQuestion = $this->updateOrCreateQuestion($question, $survey, $lang);

                    if($question->question->type == 'choice_list') {
                        $this->updateOrCreateChoiceListAnswer($question->question, $surveyQuestion, $lang);
                    } elseif($question->question->type == 'rating_scale' && $question->question->rating_scale->style == "numerical_scale") {
                        $this->updateOrCreateNumericRatingScaleAnswer($question->question, $surveyQuestion, $lang);
                    } else {
                        $notImported['question'][] = [$question->element_id, "Question type not supported"];
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

    private function updateOrCreateQuestion($question, $survey, $lang) {
        return SurveyQuestion::updateOrCreate(
            ['surveyhero_question_id' => $question->element_id],
            [
                'survey_id' => $survey->id,
                'surveyhero_question_id' => $question->element_id,
                'label' => [
                    $lang->code => $question->question->question_text ?? "",
                ]
            ]);
    }

    private function updateOrCreateChoiceListAnswer($question, $surveyQuestion, $lang): void
    {
        foreach ($question->choice_list->choices as $choice) {
            SurveyAnswer::updateOrCreate([
                    'survey_question_id'   => $surveyQuestion->id,
                    'surveyhero_answer_id' => $choice->choice_id,
                ], [
                    'survey_question_id'   => $surveyQuestion->id,
                    'surveyhero_answer_id' => $choice->choice_id,
                    'label'                => [
                        $lang->code => $choice->label
                    ]
                ]);
        }
    }

    private function updateOrCreateNumericRatingScaleAnswer($question, $surveyQuestion, $lang): void
    {
        $ratingScale = $question->rating_scale;
        $minValue = $ratingScale->left->value;
        $maxValue = $ratingScale->right->value;
        $stepSize = $ratingScale->step_size;

        for($i = $minValue; $i <= $maxValue; $i += $stepSize) {
            SurveyAnswer::updateOrCreate(
                ['survey_question_id' => $surveyQuestion->id],
                [
                    'survey_question_id' => $surveyQuestion->id,
                    'surveyhero_answer_id' => null,
                    'label' => [
                        $lang->code => $i
                    ]
                ]);
        }
    }
}
