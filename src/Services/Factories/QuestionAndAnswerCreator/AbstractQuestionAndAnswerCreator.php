<?php

namespace Statikbe\Surveyhero\Services\Factories\QuestionAndAnswerCreator;

use Statikbe\Surveyhero\Contracts\SurveyAnswerContract;
use Statikbe\Surveyhero\Contracts\SurveyContract;
use Statikbe\Surveyhero\Contracts\SurveyQuestionContract;
use Statikbe\Surveyhero\Exceptions\AnswerNotMappedException;
use Statikbe\Surveyhero\Services\SurveyMappingService;
use Statikbe\Surveyhero\SurveyheroRegistrar;

abstract class AbstractQuestionAndAnswerCreator implements QuestionAndAnswerCreator
{
    /**
     * @throws \Statikbe\Surveyhero\Exceptions\QuestionNotMappedException
     * @throws \Statikbe\Surveyhero\Exceptions\SurveyNotMappedException
     */
    public function updateOrCreateQuestion(SurveyContract $survey, string $lang, string $questionId, ?string $label, ?string $subquestionId = null): SurveyQuestionContract
    {
        return app(SurveyheroRegistrar::class)->getSurveyQuestionClass()::updateOrCreate(
            [
                'surveyhero_question_id' => $subquestionId ?? $questionId,
                'survey_id' => $survey->id,
            ],
            [
                'label' => [
                    $lang => $label ?? '',
                ],
                'surveyhero_element_id' => $questionId,
                'field' => (new SurveyMappingService)->findQuestionField($survey, $questionId, $subquestionId),
            ]);
    }

    protected function getChoiceMapping(string|int $choiceId, string|int $questionId, array $questionMapping): int|string|null
    {
        $answerMapping = $questionMapping['answer_mapping'];
        $mappingService = new SurveyMappingService;

        if ($questionMapping['question_id'] !== $questionId && isset($questionMapping['subquestion_mapping'])) {
            $subquestionMapping = $mappingService->getSubquestionMapping($questionId, $questionMapping);
            if (isset($subquestionMapping['answer_mapping'])) {
                $answerMapping = $subquestionMapping['answer_mapping'];
            }
        }
        if (array_key_exists($choiceId, $answerMapping)) {
            return $answerMapping[$choiceId];
        }

        return null;
    }

    /**
     * @throws AnswerNotMappedException
     */
    protected function setChoiceAndConvertToDataType(mixed $mappedChoice,
        string $dataType,
        array &$responseData,
        \stdClass $surveyheroChoice): void
    {
        //if the choice is not mapped try to set the label as string:
        if (! $mappedChoice) {
            if ($dataType === SurveyAnswerContract::CONVERTED_TYPE_STRING) {
                $responseData['converted_string_value'] = $surveyheroChoice->label;
            } else {
                throw AnswerNotMappedException::create($surveyheroChoice->choice_id, "The choice mapping could not be made for choice ID: $surveyheroChoice->choice_id");
            }
        } else {
            switch ($dataType) {
                case SurveyAnswerContract::CONVERTED_TYPE_INT:
                    $responseData['converted_int_value'] = $mappedChoice;
                    break;
                case SurveyAnswerContract::CONVERTED_TYPE_STRING:
                    $responseData['converted_string_value'] = $mappedChoice;
                    break;
            }
        }
    }
}
