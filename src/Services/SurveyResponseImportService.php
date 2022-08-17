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

class SurveyResponseImportService
{
    const SURVEYHERO_STATUS_COMPLETED = 'completed';

    private SurveyheroClient $client;

    private array $questionMapping;

    public function __construct(SurveyheroClient $client)
    {
        $this->client = $client;
        $this->questionMapping = config('surveyhero.question_mapping', []);
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
        $surveyQuestionMapping = $this->getSurveyQuestionMapping($survey);

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

    public function importSurveyResponse($responseId, Survey $survey, $surveyQuestionMapping = null): void
    {
        if (! $surveyQuestionMapping) {
            $surveyQuestionMapping = $this->getSurveyQuestionMapping($survey);
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
                $questionMapping = $this->getQuestionMapping($surveyQuestionMapping, $answer->element_id);
                if (! empty($questionMapping)) {
                    $questionResponseCreator = $this->getQuestionResponseCreator($answer->type);
                    if ($questionResponseCreator) {
                        try {
                            $questionResponseCreator->updateOrCreateQuestionResponse($answer, $surveyResponse, $questionMapping);
                        } catch (AnswerNotMappedException $ex) {
                            $notImported['answers'][] = [$ex->answerId, $ex->getMessage()];
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
        foreach ($linkParametersConfig as $surveyheroLinkParameter => $dbColumn) {
            if (isset($surveyheroResponse->link_parameters->{$surveyheroLinkParameter})) {
                $responseData[$dbColumn] = $surveyheroResponse->link_parameters->{$surveyheroLinkParameter};
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

    private function getQuestionMapping(array $surveyQuestionMapping, int|string $questionId): ?array
    {
        $foundQuestions = array_filter($surveyQuestionMapping, function ($question, $key) use ($questionId) {
            return $question['question_id'] == $questionId;
        }, ARRAY_FILTER_USE_BOTH);
        if (! empty($foundQuestions)) {
            return reset($foundQuestions);
        }

        return null;
    }

    private function getSurveyQuestionMapping(Survey $survey): array
    {
        $foundSurveys = null;
        try {
            $foundSurveys = array_filter($this->questionMapping, function ($surveyMapping, $key) use ($survey) {
                return $surveyMapping['survey_id'] == $survey->surveyhero_id;
            }, ARRAY_FILTER_USE_BOTH);
        } catch (\Exception $exception) {
            throw SurveyNotMappedException::create($survey, 'The question mapping configuration is not well-formed.');
        }

        if (! empty($foundSurveys)) {
            $mapping = reset($foundSurveys);
            if (array_key_exists('questions', $mapping)) {
                return $mapping['questions'];
            } else {
                throw SurveyNotMappedException::create($survey, 'Survey mapping found but its question mapping configuration is not well-formed.');
            }
        } else {
            throw SurveyNotMappedException::create($survey, 'Survey has no question mapping in config.');
        }
    }

    private function setResponseAsIncomplete(SurveyResponse $surveyResponse): void
    {
        $surveyResponse->survey_completed = false;
        $surveyResponse->save();
    }
}
