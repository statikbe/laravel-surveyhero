<?php

namespace Statikbe\Surveyhero\Services;

use Illuminate\Support\Collection;
use Statikbe\Surveyhero\Contracts\SurveyContract;
use Statikbe\Surveyhero\SurveyheroRegistrar;

class SurveyImportService extends AbstractSurveyheroAPIService
{
    /**
     * @return array{ 'imported': array<int>, 'notImported': array<int> }
     */
    public function importSurveys(?Collection $surveyIdsToImport): array
    {
        $surveyheroSurveys = $this->getApiClient()->getSurveys();
        $response = [
            'imported' => [],
            'notImported' => [],
        ];

        foreach ($surveyheroSurveys as $surveyheroSurvey) {
            if ($surveyIdsToImport && $surveyIdsToImport->isNotEmpty()) {
                //only import the  surveys
                if ($surveyIdsToImport->contains($surveyheroSurvey->survey_id)) {
                    $survey = $this->updateOrCreateSurvey($surveyheroSurvey);
                    $response['imported'][] = $survey->surveyhero_id;
                }
            } else {
                $survey = $this->updateOrCreateSurvey($surveyheroSurvey);
                $response['imported'][] = $survey->surveyhero_id;
            }
        }

        return $response;
    }

    public function updateOrCreateSurvey(\stdClass $surveyheroSurvey): SurveyContract
    {
        //check if the config has settings for this survey:
        $questionMapping = collect(config('surveyhero.question_mapping'));
        $surveyConfig = $questionMapping->filter(function ($elem) use ($surveyheroSurvey) {
            return $elem['survey_id'] == $surveyheroSurvey->survey_id;
        })->first();

        return app(SurveyheroRegistrar::class)->getSurveyClass()::updateOrCreate([
            'surveyhero_id' => $surveyheroSurvey->survey_id,
        ], [
            'name' => $surveyheroSurvey->title,
            'use_resume_link' => data_get($surveyConfig, 'use_resume_link', false),
        ]);
    }
}
