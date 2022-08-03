<?php

    namespace Statikbe\Surveyhero\Services\Factories;

    use Statikbe\Surveyhero\Models\SurveyQuestionResponse;
    use Statikbe\Surveyhero\Models\SurveyResponse;

    class TextResponseCreator extends AbstractQuestionResponseCreator {
        const TYPE = 'text';

        public function updateOrCreateQuestionResponse(\stdClass $surveyheroQuestionResponse,
                                                       SurveyResponse $response,
                                                       array $questionMapping): SurveyQuestionResponse|array {
            /* Config question_mapping data structure:
             * [
             *   'question_id' => 5410055,
             *   'type' => 'text',
             *   'field' => 'job_description',
             * ],
             */

            $existingQuestionResponse = $this->findExistingQuestionResponse($questionMapping['question_id'], $response);

            $responseData = $this->createSurveyQuestionResponseData($surveyheroQuestionResponse, $response,  $questionMapping['field']);
            $responseData['converted_string_value'] = $surveyheroQuestionResponse->text;

            return SurveyQuestionResponse::updateOrCreate([
                'id' => $existingQuestionResponse->id ?? null,
            ], $responseData);
        }
    }
