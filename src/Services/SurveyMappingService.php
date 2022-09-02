<?php

namespace Statikbe\Surveyhero\Services;

use Statikbe\Surveyhero\Exceptions\QuestionNotMappedException;
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
     * Creates a basic question mapping based on the API to kickstart the configuration.
     *
     * @see SurveyheroMapperCommand
     *
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

    /**
     * Returns the question mapping from the configuration for the given survey
     *
     * @param  Survey  $survey
     * @return array
     *
     * @throws SurveyNotMappedException
     */
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

    /**
     * Returns the question mapping from the configuration for a given survey and question ID.
     *
     * @param  Survey  $survey
     * @param  int|string  $questionId
     * @return array|null
     *
     * @throws SurveyNotMappedException
     */
    public function getQuestionMappingForSurvey(Survey $survey, int|string $questionId): ?array
    {
        $surveyQuestionMapping = $this->getSurveyQuestionMapping($survey);
        if ($surveyQuestionMapping) {
            return $this->getQuestionMapping($surveyQuestionMapping, $questionId);
        } else {
            return null;
        }
    }

    /**
     * Returns the question mapping based on all question mappings for a survey and the question ID.
     *
     * @param  array  $surveyQuestionMapping
     * @param  int|string  $questionId
     * @return array|null
     */
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

    /**
     * @param  Survey  $survey
     * @param  string  $questionId
     * @param  string|null  $subquestionId
     * @return string
     *
     * @throws QuestionNotMappedException
     * @throws SurveyNotMappedException
     */
    public function findQuestionField(Survey $survey, string $questionId, ?string $subquestionId = null): string
    {
        $questionMapping = $this->getQuestionMappingForSurvey($survey, $questionId);
        if (isset($questionMapping['field'])) {
            return $questionMapping['field'];
        } elseif ($subquestionId && isset($questionMapping['subquestion_mapping'])) {
            $foundSubquestions = array_filter($questionMapping['subquestion_mapping'], function ($question, $key) use ($subquestionId) {
                return $question['question_id'] == $subquestionId;
            }, ARRAY_FILTER_USE_BOTH);
            $foundSubquestion = reset($foundSubquestions);
            if ($foundSubquestion && isset($foundSubquestion['field'])) {
                return $foundSubquestion['field'];
            }
        }
        //in case nothing is found there is no mapping for the question -> throw error
        throw QuestionNotMappedException::create($subquestionId ?? $questionId, 'The question mapping has no field');
    }
}
