<?php

namespace Statikbe\Surveyhero\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Statikbe\Surveyhero\Exceptions\UnwantedResponseNotImportedException;
use Statikbe\Surveyhero\Models\Survey;
use Statikbe\Surveyhero\Services\Info\ResponseImportInfo;
use Statikbe\Surveyhero\Services\SurveyResponseImportService;
use Statikbe\Surveyhero\SurveyheroRegistrar;

class SurveyheroWebhookController extends Controller
{
    /**
     * This API is used by the Surveyhero webhook, to import response when they are submitted.
     *
     * Upon import events are dispatched:
     * @event SurveyResponseImported                when the response is successfully imported.
     * @event SurveyResponseIncompletelyImported    when the response could not be fully imported.
     */
    public function handleResponseCompletedWebhook(SurveyResponseImportService $surveyHeroService, Request $request): JsonResponse
    {
        Log::info('Surveyhero webhook called');

        // Check if response data is valid
        $responseData = $request->input('data');
        if (! $this->isValidResponseData($responseData)) {
            Log::error('Surveyhero response data is not valid.', [
                'response_data' => $responseData,
            ]);

            return response()->json([
                'error' => 'Surveyhero response data is not valid.',
            ], Response::HTTP_BAD_REQUEST);
        }

        // Filter out webhook calls from other collectors

        // Check if response is from an imported survey, if not imported, we do not import the response.
        $survey = app(SurveyheroRegistrar::class)->getSurveyClass()->where('surveyhero_id', $responseData['survey_id'])->first();
        if (! $survey) {
            Log::error('Response survey_id does not match imported survey. So we do not import this response.', [
                'surveyhero_survey_id' => $responseData['survey_id'],
            ]);

            return response()->json([
                'message' => 'Response survey_id does not match imported survey. So we do not import this response.',
            ], Response::HTTP_OK);
        }

        $collectors = $survey->getCollectors();

        // Check if response is from a configured collector, if not configured, we do not import the response.
        if ($collectors && count($collectors) > 0 && ! in_array((int) $responseData['collector_id'], $collectors)) {
            Log::error('Response collector does not match configured collectors. So we do not import this response.', [
                'response_data' => $responseData,
                'configured_collectors' => $collectors]);

            return response()->json([
                'message' => 'Response collector does not match configured collectors. So we do not import this response.',
            ], Response::HTTP_OK);
        }

        try {
            $this->handlePreImport($survey, $collectors, $responseData);

            // Import response
            $responseImportInfo = $surveyHeroService->importSurveyResponse($responseData['response_id'], $survey);
            Log::info('Surveyhero webhook import finished.');

            $this->handlePostImport($survey, $collectors, $responseData, $responseImportInfo);

            return response()->json([
                'message' => 'Response imported.',
            ], Response::HTTP_OK);
        } catch (UnwantedResponseNotImportedException $ex) {
            // the response is not imported but that is our decision and Surveyhero does not need to retry.
            return response()->json([
                'message' => 'Response not imported.',
            ], Response::HTTP_OK);
        } catch (\Exception $ex) {
            report($ex);

            return response()->json([
                'error' => "Import exception: {$ex->getMessage()}",
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Extend this controller and override this function if you want to add extra functionality before the import.
     * e.g. deal with link_parameters
     * If you do not want to import the response data, you can throw an UnwantedResponseNotImportedException.
     *
     * @throws UnwantedResponseNotImportedException
     */
    protected function handlePreImport(Survey $survey, array $collectors, array $responseData): void {}

    /**
     * Extend this controller and override this function if you want to add extra functionality after the import.
     * **NOTE**: the $responseInfo var will be null when the response was already imported before.
     *
     * You could also use events to deal with extra processing after a response is imported.
     */
    protected function handlePostImport(Survey $survey, array $collectors, array $responseData, ?ResponseImportInfo $responseInfo): void
    {
        // extend this controller and override this function if you want to add extra functionality after the import.
    }

    protected function isValidResponseData(?array $responseData)
    {
        return $responseData && isset($responseData['collector_id']) && isset($responseData['response_id']) && isset($responseData['survey_id']);
    }
}
