<?php

namespace Statikbe\Surveyhero\Services;

use Illuminate\Support\Facades\DB;
use Statikbe\Surveyhero\Exceptions\AnswerNotMappedException;
use Statikbe\Surveyhero\Exceptions\ResponseCreatorNotImplemented;
use Statikbe\Surveyhero\Exceptions\SurveyNotMappedException;
use Statikbe\Surveyhero\Http\SurveyheroClient;
use Statikbe\Surveyhero\Models\Survey;
use Statikbe\Surveyhero\Models\SurveyResponse;
use Statikbe\Surveyhero\Services\Factories\ChoicesResponseCreator;
use Statikbe\Surveyhero\Services\Factories\ChoiceTableResponseCreator;
use Statikbe\Surveyhero\Services\Factories\NumberResponseCreator;
use Statikbe\Surveyhero\Services\Factories\QuestionResponseCreator;
use Statikbe\Surveyhero\Services\Factories\TextResponseCreator;
use Statikbe\Surveyhero\Models\SurveyAnswer;
use Statikbe\Surveyhero\Models\SurveyQuestion;

class SurveyMappingService
{
    private SurveyheroClient $client;

    private array $questionMapping;

    public function __construct(SurveyheroClient $client)
    {
        $this->client = $client;
        $this->questionMapping = config('surveyhero.question_mapping', []);
    }

    /**
     * @param  Survey  $survey
     * @return array{'questions': array, 'answers': array}        A list of surveyhero question ids that could not be imported.
     *
     * @throws ResponseCreatorNotImplemented
     * @throws SurveyNotMappedException
     */
    public function map(Survey $survey): array
    {
        $questions = $this->client->getSurveyQuestions($survey->surveyhero_id);
        $mapping['survey_id'] = (int)$survey->surveyhero_id;
        foreach ($questions as $index => $question) {
            $mapping['questions'][$index]['question_id'] = $question->element_id;
            $mapping['questions'][$index]['type'] = $question->question->type;
            $mapping['questions'][$index]['field'] = 'READABLE_NAME';
            $mapping['questions'][$index]['mapped_data_type'] = null;

            if($question->question->type == "choice_list") {
                foreach($question->question->choice_list->choices as $choiceKey => $choice) {
                    $mapping['questions'][$index]['answer_mapping'][$choice->choice_id] = $choiceKey;
                }
                $mapping['questions'][$index]['mapped_data_type'] = 'int';
            }

            if($question->question->type == "rating_scale") {
                if($question->question->rating_scale->style == "numerical_scale") {
                    $mapping['questions'][$index]['mapped_data_type'] = 'int';
                }
            }
        }
        return $mapping;
    }
}
