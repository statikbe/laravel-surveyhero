<?php

namespace Statikbe\Surveyhero\Services\Factories\QuestionMapper;

use Statikbe\Surveyhero\Contracts\SurveyAnswerContract;

class ChoiceTableQuestionMapper extends AbstractQuestionMapper
{
    const TYPE = 'choice_table';

    public function mapQuestion(\stdClass $question, int $questionCounter): array
    {
        /*
         * [
                'question_id' => 1000001,
                'type' => 'choice_table',
                'subquestion_mapping' => [
                    [
                        'question_id' => 13509163,
                        'field' => 'question_1',
                    ],
                    [
                        'question_id' => 13509164,
                        'field' => 'question_2',
                    ],
                    [
                        'question_id' => 13509165,
                        'field' => 'question_3',
                    ],
                ],
                'answer_mapping' => [
                    13509163 => 1,
                    13509164 => 2,
                    13509165 => 3,
                ],
                'mapped_data_type' => 'int', //can also be string if the values are strings in answer_mapping
            ]
         */

        $mapping = [
            'question_id' => $question->element_id,
            'type' => $question->question->type,
            'subquestion_mapping' => [],
            'answer_mapping' => [],
            'mapped_data_type' => SurveyAnswerContract::CONVERTED_TYPE_INT,
        ];

        // make answer mapping which is the same for each question:
        $choiceCounter = 1;
        foreach ($question->question->choice_table->choices as $questionChoice) {
            $mapping['answer_mapping'][$questionChoice->choice_id] = $choiceCounter;
            $choiceCounter++;
        }

        //create subquestions:
        $subquestionIndex = 1;
        foreach ($question->question->choice_table->rows as $rowQuestion) {
            $mapping['subquestion_mapping'][$rowQuestion->row_id] = [
                'question_id' => $rowQuestion->row_id,
                'field' => "question_{$questionCounter}_{$subquestionIndex}",
            ];
            $subquestionIndex++;
        }

        return $mapping;
    }
}
