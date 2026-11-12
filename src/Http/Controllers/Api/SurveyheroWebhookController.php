<?php

namespace Statikbe\Surveyhero\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Support\Facades\Log;
use Statikbe\Surveyhero\Exceptions\UnwantedResponseNotImportedException;
use Statikbe\Surveyhero\Http\Middleware\VerifySurveyheroWebhookToken;
use Statikbe\Surveyhero\Models\Survey;
use Statikbe\Surveyhero\Services\Info\ResponseImportInfo;
use Statikbe\Surveyhero\Services\SurveyResponseImportService;
use Statikbe\Surveyhero\SurveyheroRegistrar;

class SurveyheroWebhookController implements HasMiddleware
{
    /**
     * The IDs in the webhook payload that are required and must be positive integers.
     */
    protected const REQUIRED_IDS = ['survey_id', 'collector_id', 'response_id'];

    /**
     * Every call to this controller is authenticated with the webhook secret, also when the host app registers the
     * route itself. If you extend this controller and override this method, merge in `parent::middleware()`.
     */
    public static function middleware(): array
    {
        return [VerifySurveyheroWebhookToken::class];
    }

    /**
     * This API is used by the Surveyhero webhook, to import response when they are submitted.
     *
     * Upon import events are dispatched:
     *
     * @event SurveyResponseImported                when the response is successfully imported.
     * @event SurveyResponseIncompletelyImported    when the response could not be fully imported.
     */
    public function handleResponseCompletedWebhook(SurveyResponseImportService $surveyHeroService, Request $request): JsonResponse
    {
        Log::info('Surveyhero webhook called');

        // Check if response data is valid
        $responseData = $request->input('data');
        if (! $this->isValidResponseData($responseData)) {
            Log::error('Surveyhero response data is not valid.', $this->loggableIds($responseData));

            return response()->json([
                'error' => 'Surveyhero response data is not valid.',
            ], Response::HTTP_BAD_REQUEST);
        }

        foreach (self::REQUIRED_IDS as $key) {
            $responseData[$key] = (int) $responseData[$key];
        }

        try {
            // Check if response is from an imported survey, if not imported, we do not import the response.
            $survey = app(SurveyheroRegistrar::class)->getSurveyClass()::where('surveyhero_id', $responseData['survey_id'])->first();
            if (! $survey) {
                throw UnwantedResponseNotImportedException::create(
                    $this->loggableIds($responseData),
                    'Response survey_id does not match an imported survey.'
                );
            }

            $collectors = $survey->getCollectors();

            // Check if response is from a configured collector, if not configured, we do not import the response.
            // The import service checks this again against the response fetched from the Surveyhero API.
            if (count($collectors) > 0 && ! in_array($responseData['collector_id'], array_map('intval', $collectors), true)) {
                throw UnwantedResponseNotImportedException::create(
                    $this->loggableIds($responseData) + ['configured_collectors' => $collectors],
                    'Response collector does not match the configured collectors.'
                );
            }

            $this->handlePreImport($survey, $collectors, $responseData);

            // Import response
            $responseImportInfo = $surveyHeroService->importSurveyResponse($responseData['response_id'], $survey);
            Log::info('Surveyhero webhook import finished.');

            $this->handlePostImport($survey, $collectors, $responseData, $responseImportInfo);

            return response()->json([
                'message' => 'Response imported.',
            ], Response::HTTP_OK);
        } catch (UnwantedResponseNotImportedException $ex) {
            // The response is not imported but that is our decision and Surveyhero does not need to retry.
            // Every such case gets the same reply, so callers cannot probe which surveys or collectors exist.
            Log::warning('Surveyhero response not imported: '.$ex->getMessage(), is_array($ex->responseData) ? $ex->responseData : []);

            return response()->json([
                'message' => 'Response not imported.',
            ], Response::HTTP_OK);
        } catch (\Throwable $ex) {
            report($ex);

            return response()->json([
                'error' => 'Import failed.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Extend this controller and override this function if you want to add extra functionality before the import.
     * e.g. deal with link_parameters
     * If you do not want to import the response data, you can throw an UnwantedResponseNotImportedException.
     *
     * The `survey_id`, `collector_id` and `response_id` in $responseData are validated and cast to int.
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

    /**
     * Checks that the payload contains the survey, collector and response IDs as positive integers.
     */
    protected function isValidResponseData(mixed $responseData): bool
    {
        if (! is_array($responseData)) {
            return false;
        }

        foreach (self::REQUIRED_IDS as $key) {
            if (! $this->isPositiveInteger($responseData[$key] ?? null)) {
                return false;
            }
        }

        return true;
    }

    private function isPositiveInteger(mixed $value): bool
    {
        if (is_int($value)) {
            return $value > 0;
        }

        // Digits only, and short enough to fit in a 64-bit int.
        return is_string($value) && preg_match('/^[0-9]{1,18}$/', $value) === 1 && (int) $value > 0;
    }

    /**
     * The webhook payload contains respondent data (e.g. email and IP address), so only the IDs are logged.
     *
     * @return array<string, int|string|float|bool>
     */
    private function loggableIds(mixed $responseData): array
    {
        if (! is_array($responseData)) {
            return [];
        }

        $ids = [];
        foreach (self::REQUIRED_IDS as $key) {
            if (isset($responseData[$key]) && is_scalar($responseData[$key])) {
                $ids[$key] = $responseData[$key];
            }
        }

        return $ids;
    }
}
