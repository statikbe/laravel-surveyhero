<?php

namespace Statikbe\Surveyhero\Services;

use Illuminate\Support\Facades\DB;
use JetBrains\PhpStorm\ArrayShape;
use Statikbe\Surveyhero\Exceptions\AnswerNotMappedException;
use Statikbe\Surveyhero\Exceptions\ResponseCreatorNotImplemented;
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
     * @param Survey $survey
     * @return array{'questions': array, 'answers': array}        A list of surveyhero question ids that could not be imported.
     * @throws ResponseCreatorNotImplemented
     */
    public function importSurveyResponses(Survey $survey): array
    {
        $notImported = [
            'questions' => [],
            'answers' => [],
        ];
        try {
            DB::beginTransaction();
            $responses = $this->client->getSurveyResponses($survey->surveyhero_id);

            foreach ($responses as $response) {
                //do not import already imported data.
                $existingResponseRecord = SurveyResponse::where('surveyhero_id', $response->response_id)->first();
                if ($existingResponseRecord && $existingResponseRecord->survey_completed) {
                    break;
                }

                $responseAnswers = $this->client->getSurveyResponseAnswers($survey->surveyhero_id, $response->response_id);
                if ($responseAnswers) {
                    $surveyResponse = $this->createOrUpdateSurveyResponse($responseAnswers, $survey, $existingResponseRecord);

                    foreach ($responseAnswers->answers as $answer) {
                        $questionMapping = $this->getQuestionMapping($answer->element_id);
                        if ($questionMapping && count($questionMapping)) {
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
            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            throw $exception;
        }

        return $notImported;
    }

    private function createOrUpdateSurveyResponse(\stdClass $surveyheroResponse, Survey $survey, ?SurveyResponse $existingResponse): SurveyResponse
    {
        return SurveyResponse::updateOrCreate([
            'id' => $existingResponse->id ?? null,
        ], [
            'surveyhero_id' => $surveyheroResponse->response_id,
            'survey_id' => $survey->id,
            'survey_language' => $surveyheroResponse->language->code,
            'survey_completed' => $surveyheroResponse->status == self::SURVEYHERO_STATUS_COMPLETED,
            'survey_start_date' => $this->client->transformAPITimestamp($surveyheroResponse->started_on),
            'survey_last_updated' => $this->client->transformAPITimestamp($surveyheroResponse->last_updated_on),
        ]);
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

    private function getQuestionMapping(int|string $questionId): ?array
    {
        $foundQuestions = array_filter($this->questionMapping, function ($question, $key) use ($questionId) {
            return $question['question_id'] == $questionId;
        }, ARRAY_FILTER_USE_BOTH);
        if (count($foundQuestions) > 0) {
            return reset($foundQuestions);
        }

        return null;
    }

    private function setResponseAsIncomplete(SurveyResponse $surveyResponse): void
    {
        $surveyResponse->survey_completed = false;
        $surveyResponse->save();
    }
}
