<?php

namespace Statikbe\Surveyhero\Services\Factories\QuestionAndAnswerCreator;

use Statikbe\Surveyhero\Exceptions\AnswerNotMappedException;
use Statikbe\Surveyhero\Models\Survey;
use Statikbe\Surveyhero\Models\SurveyQuestion;

abstract class AbstractQuestionAndAnswerCreator implements QuestionAndAnswerCreator
{
    public function updateOrCreateQuestion(\stdClass $question, Survey $survey, string $lang): SurveyQuestion
    {
        return SurveyQuestion::updateOrCreate(
            [
                'surveyhero_question_id' => $question->element_id,
                'survey_id' => $survey->id,
            ],
            [
                'label' => [
                    $lang => $question->question->question_text ?? '',
                ],
            ]);
    }

    protected function getChoiceMapping(string|int $choiceId, array $questionMapping): int|string|null
    {
        if (array_key_exists($choiceId, $questionMapping['answer_mapping'])) {
            return $questionMapping['answer_mapping'][$choiceId];
        }

        return null;
    }

    /**
     * @param  mixed  $mappedChoice
     * @param  string  $dataType
     * @param  array  $responseData
     * @param  \stdClass  $surveyheroChoice
     *
     * @throws AnswerNotMappedException
     */
    protected function setChoiceAndConvertToDataType(mixed $mappedChoice,
        string $dataType,
        array &$responseData,
        \stdClass $surveyheroChoice): void
    {
        //if the choice is not mapped try to set the label as string:
        if (! $mappedChoice) {
            if ($dataType === 'string') {
                $responseData['converted_string_value'] = $surveyheroChoice->label;
            } else {
                throw AnswerNotMappedException::create($surveyheroChoice->choice_id, "The choice mapping could not be made for choice ID: $surveyheroChoice->choice_id");
            }
        } else {
            switch ($dataType) {
                case 'int':
                    $responseData['converted_int_value'] = $mappedChoice;
                    break;
                case 'string':
                    $responseData['converted_string_value'] = $mappedChoice;
                    break;
            }
        }
    }
}
