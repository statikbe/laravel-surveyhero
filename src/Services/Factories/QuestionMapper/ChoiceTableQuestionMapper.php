<?php

namespace Statikbe\Surveyhero\Services\Factories\QuestionMapper;

use Statikbe\Surveyhero\Contracts\SurveyAnswerContract;

class ChoiceTableQuestionMapper extends AbstractQuestionMapper
{
    const TYPE = 'choice_table';

    public function mapQuestion(\stdClass $question, int $questionCounter): array
    {
        $mappedQuestions = [];
        $subquestionIndex = 1;
        // make answer mapping which is the same for each question:
        $answerMapping = [];
        $choiceCounter = 1;
        foreach ($question->question->choice_table->choices as $questionChoice) {
            $answerMapping[$questionChoice->choice_id] = $choiceCounter;
            $choiceCounter++;
        }

        //create subquestions:
        foreach ($question->question->choice_table->rows as $rowQuestion) {
            $questionData = $this->createQuestionMap($rowQuestion->row_id,
                $question->question->type,
                SurveyAnswerContract::CONVERTED_TYPE_INT,
                "{$questionCounter}_{$subquestionIndex}");

            $questionData['answer_mapping'] = $answerMapping;
            $mappedQuestions[] = $questionData;
        }

        return $mappedQuestions;
    }
}
