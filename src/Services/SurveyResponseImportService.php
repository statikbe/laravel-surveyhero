<?php

namespace Statikbe\Surveyhero\Services;

    use Illuminate\Support\Facades\DB;
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

        public function importSurveyResponses(Survey $survey)
        {
            try {
                DB::beginTransaction();
                $responses = $this->client->getSurveyResponses($survey->surveyhero_id);

                foreach ($responses as $response) {
                    //do not import already imported data.
                    $existingResponseRecord = SurveyResponse::where('surveyhero_id', $response->response_id)->first();
                    if ($existingResponseRecord && $existingResponseRecord->survey_completed) {
                        break;
                    }

                    $responseData = $this->client->getSurveyResponseAnswers($survey->surveyhero_id, $response->response_id);
                    if ($responseData) {
                        $surveyResponse = $this->createOrUpdateSurveyResponse($responseData, $survey, $existingResponseRecord);

                        foreach ($response->answers as $answer) {
                            $questionMapping = $this->getQuestionMapping($answer->question_id);
                            $questionResponseCreator = $this->getQuestionResponseCreator($response->type);
                            if ($questionResponseCreator) {
                                $questionResponseCreator->updateOrCreateQuestionResponse($answer, $surveyResponse, $questionMapping);
                            } else {
                                throw new ResponseCreatorNotImplemented('There is no response creator implemented for surveyhero field type: '.$response->type);
                            }
                        }
                    }
                }
                DB::commit();
            } catch (\Exception $exception) {
                DB::rollBack();
                throw $exception;
            }
        }

        private function createOrUpdateSurveyResponse(\stdClass $surveyheroResponse, Survey $survey, SurveyResponse $existingResponse): SurveyResponse
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
            });
            if (count($foundQuestions) > 0) {
                return reset($foundQuestions);
            }

            return null;
        }
    }
