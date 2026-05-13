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
     *
     * Config question_mapping data structure:
     * [
     *   'question_id' => 667012,
     *   'type' => 'input_list',
     *   'mapped_data_type' => 'string',
     *   'subquestion_mapping' => [
     *     1745983 => ['question_id' => 1745983, 'field' => 'question_1_1'],
     *     1745984 => ['question_id' => 1745984, 'field' => 'question_1_2'],
     *   ],
     * ],
     *
     * Surveyhero API response data:
     * {
     *   "element_id": 667012,
     *   "question_text": "Please enter your contact details:",
     *   "type": "inputs",
     *   "inputs": [
     *     { "input_id": 1745983, "label": "First and last name", "answer": { "type": "text", "text": "John Smith" } },
     *     { "input_id": 1745984, "label": "Street address",      "answer": { "type": "text", "text": "Main St 1" } }
     *   ]
     * }
     *
     * Each input maps to its own SurveyQuestion row, so responses are updated in-place
     * without pruning (unlike ChoicesResponseCreator). This is intentional — there is no
     * multi-select scenario here, so findExistingQuestionResponse(input_id) always targets
     * the correct row.
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
