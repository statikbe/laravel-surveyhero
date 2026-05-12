<?php

namespace Statikbe\Surveyhero\Services\Factories\ResponseCreator;

use Statikbe\Surveyhero\Contracts\SurveyAnswerContract;
use Statikbe\Surveyhero\Contracts\SurveyQuestionResponseContract;
use Statikbe\Surveyhero\Contracts\SurveyResponseContract;
use Statikbe\Surveyhero\SurveyheroConfig;
use Statikbe\Surveyhero\SurveyheroRegistrar;

class FileResponseCreator extends AbstractQuestionResponseCreator
{
    const TYPE = 'file';

    /**
     * {@inheritDoc}
     *
     * Config question_mapping data structure:
     * [
     *   'question_id' => 1387752,
     *   'type' => 'file_upload',
     *   'field' => 'question_1',
     *   'mapped_data_type' => 'string',
     * ],
     *
     * Surveyhero API response data:
     * {
     *   "element_id": 1387752,
     *   "question_text": "Please upload your image here:",
     *   "type": "file",
     *   "file": {
     *     "name": "Galapagos.jpg",
     *     "size": 480447,
     *     "path": "/v1/download/element/1387752/response/3875825"
     *   }
     * }
     *
     * Only file->path is stored (as a full URL by prepending the API base host).
     * file->name and file->size are intentionally discarded — fetch the filename
     * from the URL if needed. For local storage, a future config option may be added
     * to download the file and store a local path instead of the remote URL.
     */
    public function updateOrCreateQuestionResponse(
        \stdClass $surveyheroQuestionResponse,
        SurveyResponseContract $response,
        array $questionMapping): SurveyQuestionResponseContract|array
    {
        $existingResponse = $this->findExistingQuestionResponse($questionMapping['question_id'], $response);
        $surveyQuestion = $this->findSurveyQuestion($surveyheroQuestionResponse->element_id);

        $filePath = $surveyheroQuestionResponse->file->path ?? null;
        if ($filePath === null) {
            return $this->createSurveyQuestionResponseData($surveyQuestion, $response, null);
        }

        $apiUrl = (new SurveyheroConfig)->getApiUrl();
        $scheme = parse_url($apiUrl, PHP_URL_SCHEME);
        $host = parse_url($apiUrl, PHP_URL_HOST);
        $fileUrl = ($scheme && $host) ? "{$scheme}://{$host}{$filePath}" : $filePath;

        $surveyAnswer = $this->fetchOrCreateInputAnswer(
            $surveyQuestion,
            SurveyAnswerContract::CONVERTED_TYPE_STRING,
            $fileUrl
        );

        $responseData = $this->createSurveyQuestionResponseData($surveyQuestion, $response, $surveyAnswer);

        return app(SurveyheroRegistrar::class)->getSurveyQuestionResponseClass()::updateOrCreate(
            ['id' => $existingResponse->id ?? null],
            $responseData
        );
    }
}
