<?php

namespace Statikbe\Surveyhero\Services\Factories\ResponseCreator;

use Statikbe\Surveyhero\Contracts\SurveyAnswerContract;
use Statikbe\Surveyhero\Contracts\SurveyQuestionResponseContract;
use Statikbe\Surveyhero\Contracts\SurveyResponseContract;
use Statikbe\Surveyhero\Exceptions\QuestionNotImportedException;
use Statikbe\Surveyhero\Services\SurveyMappingService;
use Statikbe\Surveyhero\SurveyheroRegistrar;

class InputsResponseCreator extends AbstractQuestionResponseCreator
{
    const TYPE = 'inputs';

    /**
     * {@inheritDoc}
     */
    public function updateOrCreateQuestionResponse(
        \stdClass $surveyheroQuestionResponse,
        SurveyResponseContract $response,
        array $questionMapping): SurveyQuestionResponseContract|array
    {
        $responseList = [];
        $mappingService = new SurveyMappingService;
        $dataType = $questionMapping['mapped_data_type'] ?? SurveyAnswerContract::CONVERTED_TYPE_STRING;

        foreach ($surveyheroQuestionResponse->inputs as $inputAnswer) {
            $subquestionMapping = $mappingService->getSubquestionMapping($inputAnswer->input_id, $questionMapping);

            // ChoiceTableResponseCreator has the same latent issue (pre-existing).
            if (empty($subquestionMapping) || ! isset($subquestionMapping['question_id'])) {
                throw QuestionNotImportedException::create((int) $inputAnswer->input_id, "No subquestion mapping found for input_id {$inputAnswer->input_id}");
            }

            $surveyQuestion = $this->findSurveyQuestion($inputAnswer->input_id);

            $rawValue = match ($inputAnswer->answer->type) {
                'number' => $inputAnswer->answer->number,
                default => $inputAnswer->answer->text,
            };

            $surveyAnswer = $this->fetchOrCreateInputAnswer($surveyQuestion, $dataType, $rawValue);
            $existingResponse = $this->findExistingQuestionResponse($subquestionMapping['question_id'], $response);
            $responseData = $this->createSurveyQuestionResponseData($surveyQuestion, $response, $surveyAnswer);

            $responseList[] = app(SurveyheroRegistrar::class)->getSurveyQuestionResponseClass()::updateOrCreate(
                ['id' => $existingResponse->id ?? null],
                $responseData
            );
        }

        return $responseList;
    }
}
