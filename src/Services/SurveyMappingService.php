<?php

namespace Statikbe\Surveyhero\Services;

use Statikbe\Surveyhero\Contracts\SurveyContract;
use Statikbe\Surveyhero\Exceptions\QuestionMapperNotImplementedException;
use Statikbe\Surveyhero\Exceptions\QuestionNotMappedException;
use Statikbe\Surveyhero\Exceptions\SurveyNotMappedException;
use Statikbe\Surveyhero\Services\Factories\QuestionMapper\ChoiceListQuestionMapper;
use Statikbe\Surveyhero\Services\Factories\QuestionMapper\ChoiceTableQuestionMapper;
use Statikbe\Surveyhero\Services\Factories\QuestionMapper\InputQuestionMapper;
use Statikbe\Surveyhero\Services\Factories\QuestionMapper\QuestionMapper;
use Statikbe\Surveyhero\Services\Factories\QuestionMapper\RatingScaleQuestionMapper;

class SurveyMappingService extends AbstractSurveyheroAPIService
{
    private array $questionMapping;

    public function __construct()
    {
        parent::__construct();
        $this->questionMapping = config('surveyhero.question_mapping', []);
    }

    /**
     * Creates a basic question mapping based on the API to kickstart the configuration.
     *
     * @param  SurveyContract  $survey
     * @return array
     *
     * @see SurveyheroMapperCommand
     */
    public function map(SurveyContract $survey): array
    {
        $questions = $this->client->getSurveyQuestions($survey->surveyhero_id);
        $mapping = [
            'survey_id' => (int) $survey->surveyhero_id,
            'questions' => [],
        ];
        $questionCounter = 1;
        foreach ($questions as $question) {
            $mapper = $this->getQuestionMapper($question->question->type);

            if ($mapper) {
                //a mapper can return one question or multiple.
                $mappedQuestions = $mapper->mapQuestion($question, $questionCounter);
                if (! empty($mappedQuestions)) {
                    if (is_array(array_values($mappedQuestions)[0])) {
                        //multiple questions mapped:
                        $mapping['questions'] = array_merge($mapping['questions'], $mappedQuestions);
                    } else {
                        //only one question mapped:
                        $mapping['questions'][] = $mappedQuestions;
                    }
                }
                $questionCounter++;
            } else {
                throw QuestionMapperNotImplementedException::create($question->question->type);
            }
        }

        return $mapping;
    }

    /**
     * Returns the question mapping from the configuration for the given survey
     *
     * @param  SurveyContract  $survey
     * @return array
     *
     * @throws SurveyNotMappedException
     */
    public function getSurveyQuestionMapping(SurveyContract $survey): array
    {
        $surveyMapping = $this->getSurveyMapping($survey);
        if (array_key_exists('questions', $surveyMapping)) {
            return $surveyMapping['questions'];
        } else {
            throw SurveyNotMappedException::create($survey, 'Survey mapping found but its question mapping configuration is not well-formed.');
        }
    }

    /**
     * Returns the survey mapping from the configuration for the given survey.
     *
     * @param  SurveyContract  $survey
     * @return array|null
     *
     * @throws SurveyNotMappedException
     */
    public function getSurveyMapping(SurveyContract $survey): ?array
    {
        try {
            $foundSurveys = array_filter($this->questionMapping, function ($surveyMapping, $key) use ($survey) {
                return $surveyMapping['survey_id'] == $survey->surveyhero_id;
            }, ARRAY_FILTER_USE_BOTH);
        } catch (\Exception $exception) {
            throw SurveyNotMappedException::create($survey, 'The question mapping configuration is not well-formed.');
        }

        if (! empty($foundSurveys)) {
            $mapping = reset($foundSurveys);

            return $mapping;
        } else {
            throw SurveyNotMappedException::create($survey, 'Survey has no question mapping in config.');
        }
    }

    /**
     * Returns the question mapping from the configuration for a given survey and question ID.
     *
     * @param  SurveyContract  $survey
     * @param  int|string  $questionId
     * @return array|null
     *
     * @throws SurveyNotMappedException
     */
    public function getQuestionMappingForSurvey(SurveyContract $survey, int|string $questionId): ?array
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
     * Returns the subquestion mapping for questions with subquestions.
     *
     * @param  string|int  $questionId The subquestion ID.
     * @param  array  $surveyQuestionMapping  The question mapping. It needs to have a subquestion_mapping key.
     * @return array
     */
    public function getSubquestionMapping(string|int $questionId, array $surveyQuestionMapping): array
    {
        $questionMap = array_filter($surveyQuestionMapping['subquestion_mapping'], function ($question, $key) use ($questionId) {
            return $question['question_id'] == $questionId;
        }, ARRAY_FILTER_USE_BOTH);

        if (! empty($questionMap)) {
            $questionMap = reset($questionMap);
        }

        return $questionMap;
    }

    /**
     * @param  SurveyContract  $survey
     * @param  string  $questionId
     * @param  string|null  $subquestionId
     * @return string
     *
     * @throws QuestionNotMappedException
     * @throws SurveyNotMappedException
     */
    public function findQuestionField(SurveyContract $survey, string $questionId, ?string $subquestionId = null): string
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

    private function getQuestionMapper(string $surveyheroFieldType): ?QuestionMapper
    {
        return match ($surveyheroFieldType) {
            InputQuestionMapper::TYPE => new InputQuestionMapper(),
            RatingScaleQuestionMapper::TYPE => new RatingScaleQuestionMapper(),
            ChoiceListQuestionMapper::TYPE => new ChoiceListQuestionMapper(),
            ChoiceTableQuestionMapper::TYPE => new ChoiceTableQuestionMapper(),
            default => null,
        };
    }
}
