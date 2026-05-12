<?php

namespace Statikbe\Surveyhero\Services\Factories\QuestionAndAnswerCreator;

use Statikbe\Surveyhero\Contracts\SurveyContract;
use Statikbe\Surveyhero\Contracts\SurveyQuestionContract;
use Statikbe\Surveyhero\Exceptions\AnswerNotMappedException;
use Statikbe\Surveyhero\Exceptions\QuestionNotMappedException;
use Statikbe\Surveyhero\Exceptions\SurveyNotMappedException;
use Statikbe\Surveyhero\Http\DTO\SurveyElementDTO;
use Statikbe\Surveyhero\Services\SurveyMappingService;
use Statikbe\Surveyhero\SurveyheroRegistrar;

class ImageChoiceListQuestionAndAnswerCreator extends AbstractQuestionAndAnswerCreator
{
    const TYPE = 'image_choice_list';

    /**
     * @throws AnswerNotMappedException
     * @throws QuestionNotMappedException
     * @throws SurveyNotMappedException
     *
     * Config question_mapping data structure:
     * [
     *   'question_id' => 555001,
     *   'type' => 'image_choice_list',
     *   'field' => 'question_1',
     *   'mapped_data_type' => 'int',
     *   'answer_mapping' => [
     *     13509100 => 1,
     *     13509101 => 2,
     *   ],
     * ],
     *
     * Surveyhero API data:
     * {
     *   "element_id": 555001,
     *   "type": "question",
     *   "question": {
     *     "question_id": 555001,
     *     "type": "image_choice_list",
     *     "question_text": "Pick an image:",
     *     "image_choice_list": {
     *       "choices": [
     *         { "choice_id": 13509100, "label": "Option A", "image": { "url": "https://..." } },
     *         { "choice_id": 13509101, "label": "Option B", "image": { "url": "https://..." } }
     *       ]
     *     }
     *   }
     * }
     */
    public function updateOrCreateQuestionAndAnswer(SurveyElementDTO $question, SurveyContract $survey, string $lang): SurveyQuestionContract|array
    {
        $surveyQuestion = $this->updateOrCreateQuestion($survey, $lang, $question->element_id, $question->question->question_text);
        $questionMapping = (new SurveyMappingService)->getQuestionMappingForSurvey($survey, $question->element_id);

        foreach ($question->question->image_choice_list->choices as $choice) {
            $responseData = [
                'survey_question_id' => $surveyQuestion->id,
                'surveyhero_answer_id' => $choice->choice_id,
                'label' => [
                    $lang => $choice->label,
                ],
            ];

            $mappedChoice = $this->getChoiceMapping($choice->choice_id, $question->element_id, $questionMapping);

            $this->setChoiceAndConvertToDataType($mappedChoice, $questionMapping['mapped_data_type'], $responseData, $choice);

            app(SurveyheroRegistrar::class)->getSurveyAnswerClass()::updateOrCreate([
                'survey_question_id' => $surveyQuestion->id,
                'surveyhero_answer_id' => $choice->choice_id,
            ], $responseData);
        }

        return $surveyQuestion;
    }
}
