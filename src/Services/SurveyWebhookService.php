<?php

namespace Statikbe\Surveyhero\Services;

use Statikbe\Surveyhero\Contracts\SurveyContract;
use Statikbe\Surveyhero\Exceptions\QuestionMapperNotImplementedException;
use Statikbe\Surveyhero\Exceptions\QuestionNotMappedException;
use Statikbe\Surveyhero\Exceptions\SurveyNotMappedException;
use Statikbe\Surveyhero\Models\Survey;
use Statikbe\Surveyhero\Services\Factories\QuestionMapper\ChoiceListQuestionMapper;
use Statikbe\Surveyhero\Services\Factories\QuestionMapper\ChoiceTableQuestionMapper;
use Statikbe\Surveyhero\Services\Factories\QuestionMapper\InputQuestionMapper;
use Statikbe\Surveyhero\Services\Factories\QuestionMapper\QuestionMapper;
use Statikbe\Surveyhero\Services\Factories\QuestionMapper\RatingScaleQuestionMapper;

class SurveyWebhookService extends AbstractSurveyheroAPIService
{
    /**
     * Creates a basic question mapping based on the API to kickstart the configuration.
     *
     * @param  SurveyContract  $survey
     * @return array
     *
     * @see SurveyheroMapperCommand
     */
    public function generateWebhook(Survey $survey, string $eventType, string $url) {
        $this->client->createWebhook($survey->surveyhero_id, $eventType, $url,'active');
    }
}
