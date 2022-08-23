<?php

namespace Statikbe\Surveyhero\Services;

use Illuminate\Support\Facades\DB;
use Statikbe\Surveyhero\Exceptions\AnswerNotImportedException;
use Statikbe\Surveyhero\Exceptions\AnswerNotMappedException;
use Statikbe\Surveyhero\Exceptions\QuestionNotImportedException;
use Statikbe\Surveyhero\Exceptions\ResponseCreatorNotImplemented;
use Statikbe\Surveyhero\Exceptions\SurveyNotMappedException;
use Statikbe\Surveyhero\Http\SurveyheroClient;
use Statikbe\Surveyhero\Models\Survey;
use Statikbe\Surveyhero\Models\SurveyResponse;
use Statikbe\Surveyhero\Services\Factories\ResponseCreator\ChoicesResponseCreator;
use Statikbe\Surveyhero\Services\Factories\ResponseCreator\ChoiceTableResponseCreator;
use Statikbe\Surveyhero\Services\Factories\ResponseCreator\NumberResponseCreator;
use Statikbe\Surveyhero\Services\Factories\ResponseCreator\QuestionResponseCreator;
use Statikbe\Surveyhero\Services\Factories\ResponseCreator\TextResponseCreator;

class SurveyResponseImportService
{
    const SURVEYHERO_STATUS_COMPLETED = 'completed';

    private SurveyheroClient $client;

    /**
     * @var \Statikbe\Surveyhero\Services\SurveyMappingService
     */
    private SurveyMappingService $surveyMappingService;

    public function __construct(SurveyheroClient $client, SurveyMappingService $surveyMappingService)
    {
        $this->client = $client;
        $this->surveyMappingService = $surveyMappingService;
    }

    /**
     * @param  Survey  $survey
     * @return array{'questions': array, 'answers': array}        A list of surveyhero question ids that could not be imported.
     *
     * @throws ResponseCreatorNotImplemented
     * @throws SurveyNotMappedException
     */
    public function importSurveyResponses(Survey $survey): array
    {
        $notImported = [
            'questions' => [],
            'answers' => [],
        ];
        $surveyQuestionMapping = $this->surveyMappingService->getSurveyQuestionMapping($survey);

        try {
            DB::beginTransaction();
            $responses = $this->client->getSurveyResponses($survey->surveyhero_id);

            foreach ($responses as $response) {
                $this->importSurveyResponse($response->response_id, $survey, $surveyQuestionMapping);
            }
            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            throw $exception;
        }

        return $notImported;
    }

    /**
     * @throws \Statikbe\Surveyhero\Exceptions\SurveyNotMappedException
     * @throws \Statikbe\Surveyhero\Exceptions\ResponseCreatorNotImplemented
     */
    public function importSurveyResponse($responseId, Survey $survey, $surveyQuestionMapping = null): void
    {
        if (! $surveyQuestionMapping) {
            $surveyQuestionMapping =  $this->surveyMappingService->getSurveyQuestionMapping($survey);
        }

        //do not import already imported data.
        $existingResponseRecord = SurveyResponse::where('surveyhero_id', $responseId)->first();
        if ($existingResponseRecord && $existingResponseRecord->survey_completed) {
            return;
        }

        $responseAnswers = $this->client->getSurveyResponseAnswers($survey->surveyhero_id, $responseId);
        if ($responseAnswers) {
            $surveyResponse = $this->createOrUpdateSurveyResponse($responseAnswers, $survey, $existingResponseRecord);

            foreach ($responseAnswers->answers as $answer) {
                $questionMapping = $this->surveyMappingService->getQuestionMapping($surveyQuestionMapping, $answer->element_id);
                if (! empty($questionMapping)) {
                    $questionResponseCreator = $this->getQuestionResponseCreator($answer->type);
                    if ($questionResponseCreator) {
                        try {
                            $questionResponseCreator->updateOrCreateQuestionResponse($answer, $surveyResponse, $questionMapping);
                        } catch (AnswerNotMappedException $ex) {
                            $notImported['answers'][] = [$ex->answerId, $ex->getMessage()];
                            //set survey response as incomplete, because we could not completely import it.
                            $this->setResponseAsIncomplete($surveyResponse);
                        } catch (QuestionNotImportedException $ex) {
                            $notImported['questions'][$answer->element_id] = [$answer->element_id];
                            //set survey response as incomplete, because we could not completely import it.
                            $this->setResponseAsIncomplete($surveyResponse);
                        } catch (AnswerNotImportedException $ex) {
                            $notImported['answers'][$answer->element_id] = [$answer->element_id];
                            //set survey response as incomplete, because we could not completely import it.
                            $this->setResponseAsIncomplete($surveyResponse);
                        }
                    } else {
                        throw new ResponseCreatorNotImplemented("There is no response creator implemented for surveyhero field type: $answer->type");
                    }
                } else {
                    $notImported['questions'][$answer->element_id] = [$answer->element_id];
                }
            }
        }
    }

    private function createOrUpdateSurveyResponse(\stdClass $surveyheroResponse, Survey $survey, ?SurveyResponse $existingResponse): SurveyResponse
    {
        $responseData = [
            'surveyhero_id' => $surveyheroResponse->response_id,
            'survey_id' => $survey->id,
            'survey_language' => optional($surveyheroResponse->language)->code,
            'survey_completed' => $surveyheroResponse->status == self::SURVEYHERO_STATUS_COMPLETED,
            'survey_start_date' => $this->client->transformAPITimestamp($surveyheroResponse->started_on),
            'survey_last_updated' => $this->client->transformAPITimestamp($surveyheroResponse->last_updated_on),
            'surveyhero_link_parameters' => json_encode($surveyheroResponse->link_parameters),
        ];

        //map link parameters:
        $linkParametersConfig = config('surveyhero.surveyhero_link_parameters_mapping', []);
        foreach ($linkParametersConfig as $surveyheroLinkParameter => $settings) {
            if (isset($surveyheroResponse->link_parameters->{$surveyheroLinkParameter})) {
                if (isset($settings['entity']) && isset($settings['value']) && isset($settings['field'])) {
                    //Map parameter to value of associated model
                    $responseData[$settings['name']] = optional($settings['entity']::where($settings['value'], $surveyheroResponse->link_parameters->{$surveyheroLinkParameter})->first())->id;
                } else {
                    //map parameter directly to database column
                    $responseData[$settings['name']] = $surveyheroResponse->link_parameters->{$surveyheroLinkParameter};
                }
            }
        }

        return SurveyResponse::updateOrCreate([
            'id' => $existingResponse->id ?? null,
        ], $responseData);
    }

    private function getQuestionResponseCreator(string $surveyheroFieldType): ?QuestionResponseCreator
    {
        return match ($surveyheroFieldType) {
            TextResponseCreator::TYPE => new TextResponseCreator(),
            NumberResponseCreator::TYPE => new NumberResponseCreator(),
            ChoicesResponseCreator::TYPE => new ChoicesResponseCreator(),
            ChoiceTableResponseCreator::TYPE => new ChoiceTableResponseCreator(),
            default => null,
        };
    }



    private function setResponseAsIncomplete(SurveyResponse $surveyResponse): void
    {
        $surveyResponse->survey_completed = false;
        $surveyResponse->save();
    }
}
