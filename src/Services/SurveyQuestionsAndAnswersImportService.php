<?php

namespace Statikbe\Surveyhero\Services;

use Illuminate\Support\Facades\DB;
use Statikbe\Surveyhero\Contracts\SurveyContract;
use Statikbe\Surveyhero\Services\Factories\QuestionAndAnswerCreator\ChoiceListQuestionAndAnswerCreator;
use Statikbe\Surveyhero\Services\Factories\QuestionAndAnswerCreator\ChoiceTableQuestionAndAnswerCreator;
use Statikbe\Surveyhero\Services\Factories\QuestionAndAnswerCreator\InputQuestionAndAnswerCreator;
use Statikbe\Surveyhero\Services\Factories\QuestionAndAnswerCreator\QuestionAndAnswerCreator;
use Statikbe\Surveyhero\Services\Factories\QuestionAndAnswerCreator\RatingScaleQuestionAndAnswerCreator;

class SurveyQuestionsAndAnswersImportService extends AbstractSurveyheroAPIService
{
    /**
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

    private function getQuestionAndAnswerCreator(string $surveyheroFieldType): ?QuestionAndAnswerCreator
    {
        return match ($surveyheroFieldType) {
            ChoiceListQuestionAndAnswerCreator::TYPE => new ChoiceListQuestionAndAnswerCreator,
            ChoiceTableQuestionAndAnswerCreator::TYPE => new ChoiceTableQuestionAndAnswerCreator,
            RatingScaleQuestionAndAnswerCreator::TYPE => new RatingScaleQuestionAndAnswerCreator,
            InputQuestionAndAnswerCreator::TYPE => new InputQuestionAndAnswerCreator,
            default => null,
        };
    }
}
