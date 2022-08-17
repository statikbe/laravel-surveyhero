<?php

namespace Statikbe\Surveyhero\Services;

use Statikbe\Surveyhero\Http\SurveyheroClient;
use Statikbe\Surveyhero\Models\Survey;

class SurveyMappingService
{
    /**
     * @var \Statikbe\Surveyhero\Http\SurveyheroClient
     */
    private SurveyheroClient $client;

    public function __construct(SurveyheroClient $client)
    {
        $this->client = $client;
    }

    /**
     * @param  Survey  $survey
     * @return array
     */
    public function map(Survey $survey): array
    {
        $questions = $this->client->getSurveyQuestions($survey->surveyhero_id);
        $mapping['survey_id'] = (int) $survey->surveyhero_id;
        foreach ($questions as $index => $question) {
            $mapping['questions'][$index]['question_id'] = $question->element_id;
            $mapping['questions'][$index]['type'] = $question->question->type;
            $mapping['questions'][$index]['field'] = 'READABLE_NAME';
            $mapping['questions'][$index]['mapped_data_type'] = null;

            if ($question->question->type == 'choice_list') {
                foreach ($question->question->choice_list->choices as $choiceKey => $choice) {
                    $mapping['questions'][$index]['answer_mapping'][$choice->choice_id] = $choiceKey;
                }
                $mapping['questions'][$index]['mapped_data_type'] = 'int';
            }

            if ($question->question->type == 'rating_scale') {
                if ($question->question->rating_scale->style == 'numerical_scale') {
                    $mapping['questions'][$index]['mapped_data_type'] = 'int';
                }
            }
        }

        return $mapping;
    }
}
