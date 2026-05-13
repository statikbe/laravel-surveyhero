<?php

namespace Statikbe\Surveyhero\Services\Factories\QuestionMapper;

use Statikbe\Surveyhero\Contracts\SurveyAnswerContract;
use Statikbe\Surveyhero\Http\DTO\SurveyElementDTO;

class ImageChoiceListQuestionMapper extends AbstractQuestionMapper
{
    const TYPE = 'image_choice_list';

    public function mapQuestion(SurveyElementDTO $question, int $questionCounter): array
    {
        /* Config question_mapping data structure:
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
        $questionData = $this->createQuestionMap(
            $question->element_id,
            $question->question->type,
            SurveyAnswerContract::CONVERTED_TYPE_INT,
            $questionCounter
        );

        foreach ($question->question->image_choice_list->choices as $choiceKey => $choice) {
            $questionData['answer_mapping'][$choice->choice_id] = $choiceKey + 1;
        }

        return $questionData;
    }
}
