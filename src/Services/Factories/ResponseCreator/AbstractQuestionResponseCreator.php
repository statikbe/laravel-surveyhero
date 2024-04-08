<?php

namespace Statikbe\Surveyhero\Services\Factories\ResponseCreator;

use Illuminate\Database\Eloquent\Collection;
use Statikbe\Surveyhero\Contracts\SurveyAnswerContract;
use Statikbe\Surveyhero\Contracts\SurveyQuestionContract;
use Statikbe\Surveyhero\Contracts\SurveyQuestionResponseContract;
use Statikbe\Surveyhero\Contracts\SurveyResponseContract;
use Statikbe\Surveyhero\Exceptions\AnswerNotImportedException;
use Statikbe\Surveyhero\Exceptions\AnswerNotMappedException;
use Statikbe\Surveyhero\Exceptions\QuestionNotImportedException;
use Statikbe\Surveyhero\SurveyheroRegistrar;

abstract class AbstractQuestionResponseCreator implements QuestionResponseCreator
{
    protected function findExistingQuestionResponse(string|int $surveyheroQuestionId,
        SurveyResponseContract $response,
        string|int|null $surveyheroAnswerId = null): ?SurveyQuestionResponseContract
    {
        $query = app(SurveyheroRegistrar::class)->getSurveyQuestionResponseClass()::whereHas('surveyQuestion', function ($q) use ($surveyheroQuestionId) {
            $q->where('surveyhero_question_id', $surveyheroQuestionId);
        })->where('survey_response_id', $response->id);

        if ($surveyheroAnswerId) {
            $query->whereHas('surveyAnswer', function ($q) use ($surveyheroAnswerId) {
                $q->where('surveyhero_answer_id', $surveyheroAnswerId);
            });
        }

        return $query->first();
    }

    protected function findAllExistingQuestionResponses(string|int $surveyheroQuestionId,
        SurveyResponseContract $response): Collection
    {
        $query = app(SurveyheroRegistrar::class)->getSurveyQuestionResponseClass()::whereHas('surveyQuestion', function ($q) use ($surveyheroQuestionId) {
            $q->where('surveyhero_question_id', $surveyheroQuestionId);
        })->where('survey_response_id', $response->id);

        return $query->get();
    }

    /**
     * @throws QuestionNotImportedException
     */
    protected function findSurveyQuestion(string $surveyheroQuestionId): SurveyQuestionContract
    {
        $surveyQuestion = app(SurveyheroRegistrar::class)->getSurveyQuestionClass()::where('surveyhero_question_id', $surveyheroQuestionId)->first();
        if (! $surveyQuestion) {
            throw QuestionNotImportedException::create($surveyheroQuestionId, 'The question is not imported');
        } else {
            return $surveyQuestion;
        }
    }

    protected function findSurveyAnswer(SurveyQuestionContract $question, string $surveyheroAnswerId): SurveyAnswerContract
    {
        $surveyAnswer = app(SurveyheroRegistrar::class)->getSurveyAnswerClass()::where('survey_question_id', $question->id)
            ->where('surveyhero_answer_id', $surveyheroAnswerId)
            ->first();

        if (! $surveyAnswer) {
            throw AnswerNotImportedException::create($surveyheroAnswerId, "Make sure to import survey answer with Surveyhero ID $surveyheroAnswerId in the survey_answers table");
        } else {
            return $surveyAnswer;
        }
    }

    /**
     * @return array{ 'survey_question_id': int, 'survey_response_id': int }
     */
    protected function createSurveyQuestionResponseData(SurveyQuestionContract $question,
        SurveyResponseContract $response,
        ?SurveyAnswerContract $answer): array
    {
        return [
            'survey_question_id' => $question->id,
            'survey_response_id' => $response->id,
            'survey_answer_id' => $answer ? $answer->id : null,
        ];
    }

    /**
     * @throws AnswerNotMappedException
     */
    protected function setChoiceAndConvertToDataType(mixed $mappedChoice,
        string $dataType,
        array &$responseData,
        ?\stdClass $surveyheroChoice): void
    {
        //if the choice is not mapped try to set the label as string:
        if (! $mappedChoice && $surveyheroChoice) {
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

    protected function fetchOrCreateInputAnswer(SurveyQuestionContract $surveyQuestion, string $answerDataType, mixed $inputAnswer): SurveyAnswerContract
    {
        //fetch or create answer:
        $answerData = [];
        $this->setChoiceAndConvertToDataType($this->transformInputToDataType($inputAnswer, $answerDataType),
            $answerDataType,
            $answerData,
            null);
        $surveyAnswerQuery = app(SurveyheroRegistrar::class)->getSurveyAnswerClass()::where('survey_question_id', $surveyQuestion->id);
        if (isset($answerData['converted_int_value'])) {
            $surveyAnswerQuery->where('converted_int_value', $answerData['converted_int_value']);
        } elseif (isset($answerData['converted_string_value'])) {
            $surveyAnswerQuery->where('converted_string_value', $answerData['converted_string_value']);
        }
        $surveyAnswer = $surveyAnswerQuery->first();

        if (! $surveyAnswer) {
            $answerData['survey_question_id'] = $surveyQuestion->id;
            $answerData['surveyhero_answer_id'] = null;
            $surveyAnswer = app(SurveyheroRegistrar::class)->getSurveyAnswerClass()::create($answerData);
        }

        return $surveyAnswer;
    }

    protected function transformInputToDataType(mixed $input, string $dataType): mixed
    {
        switch ($dataType) {
            case SurveyAnswerContract::CONVERTED_TYPE_INT:
                return intval($input);
            case SurveyAnswerContract::CONVERTED_TYPE_STRING:
                return strval($input);
            default:
                return $input;
        }
    }
}
