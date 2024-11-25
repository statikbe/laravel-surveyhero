<?php

namespace Statikbe\Surveyhero\Services;

use Illuminate\Support\Facades\DB;
use Statikbe\Surveyhero\Contracts\SurveyContract;
use Statikbe\Surveyhero\Contracts\SurveyResponseContract;
use Statikbe\Surveyhero\Events\SurveyResponseImported;
use Statikbe\Surveyhero\Events\SurveyResponseIncompletelyImported;
use Statikbe\Surveyhero\Exceptions\AnswerNotImportedException;
use Statikbe\Surveyhero\Exceptions\AnswerNotMappedException;
use Statikbe\Surveyhero\Exceptions\QuestionNotImportedException;
use Statikbe\Surveyhero\Exceptions\ResponseCreatorNotImplemented;
use Statikbe\Surveyhero\Exceptions\SurveyNotMappedException;
use Statikbe\Surveyhero\Services\Factories\ResponseCreator\ChoicesResponseCreator;
use Statikbe\Surveyhero\Services\Factories\ResponseCreator\ChoiceTableResponseCreator;
use Statikbe\Surveyhero\Services\Factories\ResponseCreator\NumberResponseCreator;
use Statikbe\Surveyhero\Services\Factories\ResponseCreator\QuestionResponseCreator;
use Statikbe\Surveyhero\Services\Factories\ResponseCreator\TextResponseCreator;
use Statikbe\Surveyhero\Services\Info\ResponseImportInfo;
use Statikbe\Surveyhero\SurveyheroRegistrar;

class SurveyResponseImportService extends AbstractSurveyheroAPIService
{
    const SURVEYHERO_STATUS_COMPLETED = 'completed';

    private SurveyMappingService $surveyMappingService;

    public function __construct(SurveyMappingService $surveyMappingService)
    {
        parent::__construct();
        $this->surveyMappingService = $surveyMappingService;
    }

    /**
     * @throws ResponseCreatorNotImplemented
     * @throws SurveyNotMappedException
     */
    public function importSurveyResponses(SurveyContract $survey): ResponseImportInfo
    {
        $surveyCollectorIds = $this->surveyMappingService->getSurveyCollectors($survey);
        $surveyQuestionMapping = $this->surveyMappingService->getSurveyQuestionMapping($survey);
        $responseImportInfo = new ResponseImportInfo;

        try {
            DB::beginTransaction();

            $responses = $this->client->getSurveyResponses($survey->surveyhero_id, $survey->survey_last_imported, $surveyCollectorIds);

            foreach ($responses as $response) {
                $responseImportInfo->addInfo($this->importSurveyResponse($response->response_id, $survey, $surveyQuestionMapping));
            }

            //update new survey last updated timestamp:
            if ($responseImportInfo->getSurveyLastUpdatedAt()) {
                if (! $survey->survey_last_imported) {
                    $survey->survey_last_imported = $responseImportInfo->getSurveyLastUpdatedAt();
                } else {
                    $survey->survey_last_imported = $responseImportInfo->getSurveyLastUpdatedAt()->max($survey->survey_last_imported);
                }
                $survey->save();
            }

            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            throw $exception;
        }

        return $responseImportInfo;
    }

    /**
     * @return ResponseImportInfo | null A list of surveyhero question ids that could not be imported.
     *
     * @throws ResponseCreatorNotImplemented
     * @throws SurveyNotMappedException
     */
    public function importSurveyResponse($responseId, SurveyContract $survey, ?array $surveyQuestionMapping = null): ?ResponseImportInfo
    {
        $importInfo = new ResponseImportInfo;

        if (! $surveyQuestionMapping) {
            $surveyQuestionMapping = $this->surveyMappingService->getSurveyQuestionMapping($survey);
        }

        $responseAnswers = $this->client->getSurveyResponseAnswers($survey->surveyhero_id, $responseId);
        if ($responseAnswers && $survey->doesResponseNeedsToBeUpdated($responseAnswers->last_updated_on)) {
            //do not import already imported data that is not updated.
            /* @var SurveyResponseContract $existingResponseRecord */
            $existingResponseRecord = app(SurveyheroRegistrar::class)->getSurveyResponseClass()::where('surveyhero_id', $responseId)->first();
            if ($existingResponseRecord && $existingResponseRecord->survey_completed) {
                return null;
            }

            $surveyResponse = $this->createOrUpdateSurveyResponse($responseAnswers, $survey, $existingResponseRecord);

            foreach ($responseAnswers->answers as $answer) {
                $questionMapping = $this->surveyMappingService->getQuestionMapping($surveyQuestionMapping, $answer->element_id);
                if (! empty($questionMapping)) {
                    $questionResponseCreator = $this->getQuestionResponseCreator($answer->type);
                    if ($questionResponseCreator) {
                        try {
                            $questionResponseCreator->updateOrCreateQuestionResponse($answer, $surveyResponse, $questionMapping);
                        } catch (AnswerNotMappedException $ex) {
                            $importInfo->addUnimportedAnswer($ex->answerId, $ex->getMessage());
                            //set survey response as incomplete, because we could not completely import it.
                            $this->setResponseAsIncomplete($surveyResponse);
                        } catch (QuestionNotImportedException $ex) {
                            $importInfo->addUnimportedQuestion($answer->element_id, $ex->getMessage());
                            //set survey response as incomplete, because we could not completely import it.
                            $this->setResponseAsIncomplete($surveyResponse);
                        } catch (AnswerNotImportedException $ex) {
                            $importInfo->addUnimportedAnswer($answer->element_id, $ex->getMessage());
                            //set survey response as incomplete, because we could not completely import it.
                            $this->setResponseAsIncomplete($surveyResponse);
                        }
                    } else {
                        throw new ResponseCreatorNotImplemented("There is no response creator implemented for surveyhero field type: $answer->type");
                    }
                } else {
                    $importInfo->addUnimportedQuestion($answer->element_id, 'No question mapping available in configuration file.');
                }
            }

            $importInfo->increaseTotalResponses();

            //increase survey last updated timestamp:
            $responseLastUpdatedOn = $this->client->transformAPITimestamp($responseAnswers->last_updated_on);
            $importInfo->setSurveyLastUpdatedAt($responseLastUpdatedOn);

            //dispatch event:
            if ($surveyResponse->survey_completed) {
                SurveyResponseImported::dispatch($surveyResponse);
            } else {
                SurveyResponseIncompletelyImported::dispatch($surveyResponse);
            }
        }

        return $importInfo;
    }

    private function createOrUpdateSurveyResponse(\stdClass $surveyheroResponse, SurveyContract $survey, ?SurveyResponseContract $existingResponse): SurveyResponseContract
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
        if (isset($surveyheroResponse->link_parameters)) {
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
        }

        //add resume link if configured:
        if ($survey->use_resume_link) {
            $resumeLink = $this->client->getResumeLink($survey->surveyhero_id, $surveyheroResponse->response_id);
            $responseData['resume_link'] = $resumeLink;
        }

        return app(SurveyheroRegistrar::class)->getSurveyResponseClass()::updateOrCreate([
            'id' => $existingResponse->id ?? null,
        ], $responseData);
    }

    public function deleteSurveyResponse($surveyId, $responseId)
    {
        $this->client->deleteResponse($surveyId, $responseId);
    }

    private function getQuestionResponseCreator(string $surveyheroFieldType): ?QuestionResponseCreator
    {
        return match ($surveyheroFieldType) {
            TextResponseCreator::TYPE => new TextResponseCreator,
            NumberResponseCreator::TYPE => new NumberResponseCreator,
            ChoicesResponseCreator::TYPE => new ChoicesResponseCreator,
            ChoiceTableResponseCreator::TYPE => new ChoiceTableResponseCreator,
            default => null,
        };
    }

    private function setResponseAsIncomplete(SurveyResponseContract $surveyResponse): void
    {
        $surveyResponse->survey_completed = false;
        $surveyResponse->save();
    }
}
