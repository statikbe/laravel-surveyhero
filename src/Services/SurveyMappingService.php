<?php

namespace Statikbe\Surveyhero\Services;

use Statikbe\Surveyhero\Exceptions\SurveyNotMappedException;
use Statikbe\Surveyhero\Http\SurveyheroClient;
use Statikbe\Surveyhero\Models\Survey;

class SurveyMappingService
{
    /**
     * @var \Statikbe\Surveyhero\Http\SurveyheroClient
     */
    private SurveyheroClient $client;

    private array $questionMapping;

    public function __construct()
    {
        $this->client = new SurveyheroClient();
        $this->questionMapping = config('surveyhero.question_mapping', []);
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
                    $mapping['questions'][$index]['answer_mapping'][$choice->choice_id] = $choiceKey + 1;
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

    public function getSurveyQuestionMapping(Survey $survey): array
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

    public function getQuestionMapping(array $surveyQuestionMapping, int|string $questionId): ?array
    {
        $foundQuestions = array_filter($surveyQuestionMapping, function ($question, $key) use ($questionId) {
            return $question['question_id'] == $questionId;
        }, ARRAY_FILTER_USE_BOTH);
        if (! empty($foundQuestions)) {
            return reset($foundQuestions);
        }

        return null;
    }
}
