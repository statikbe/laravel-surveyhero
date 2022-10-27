<?php

namespace Statikbe\Surveyhero\Http\Controllers\Api;

    use Illuminate\Http\JsonResponse;
    use Illuminate\Http\Request;
    use Illuminate\Http\Response;
    use Illuminate\Routing\Controller;
    use Illuminate\Support\Facades\Log;
    use Statikbe\Surveyhero\Models\Survey;
    use Statikbe\Surveyhero\Services\SurveyResponseImportService;

    class SurveyheroWebhookController extends Controller
    {
        public function handleResponseCompletedWebhook(SurveyResponseImportService $surveyHeroService, Request $request): JsonResponse
        {
            Log::info('Surveyhero webhook called');

            //Check if response data is valid
            $responseData = $request->input('data');
            if (! ($responseData && isset($responseData['collector_id']) && isset($responseData['response_id']) && isset($responseData['survey_id']))) {
                return response()->json([
                    'error' => 'Surveyhero response data is not valid.',
                ], Response::HTTP_BAD_REQUEST);
            }

            //Filter out webhook calls from other collectors
            $questionMapping = config('surveyhero.question_mapping');
            if (count($questionMapping) == 0) {
                return response()->json([
                    'error' => 'Question mapping not set up.',
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            //Check if response is from a configured collector, if not configured we do not import the response.
            $collectors = $questionMapping[0]['collectors'];
            Log::info($collectors);
            if ($collectors && count($collectors) > 0 && ! in_array($responseData['collector_id'], $collectors)) {
                return response()->json([
                    'message' => 'Response collector does not match configured collectors. So we do not import this response.',
                ], Response::HTTP_OK);
            }

            try {
                //Import response
                $surveyHeroService->importSurveyResponse($responseData['response_id'], Survey::where('surveyhero_id', $responseData['survey_id'])->first());
                Log::info('Surveyhero webhook import finished.');

                return response()->json([
                    'message' => 'Response imported.',
                ], Response::HTTP_OK);
            } catch (\Exception $ex) {
                Log::error($ex);

                return response()->json([
                    'error' => "Import exception: {$ex->getMessage()}",
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }
    }
