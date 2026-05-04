<?php

use Statikbe\Surveyhero\Http\DTO\SurveyElementDTO;
use Statikbe\Surveyhero\Services\Factories\QuestionMapper\InputListQuestionMapper;

function makeInputListQuestion(int $elementId, array $inputs, string $acceptsType = 'text'): SurveyElementDTO
{
    return SurveyElementDTO::fromResponseObject((object) [
        'element_id' => $elementId,
        'type' => 'question',
        'question' => (object) [
            'type' => 'input_list',
            'question_text' => 'Please enter your contact details:',
            'input_list' => (object) [
                'accepts' => (object) [
                    'type' => $acceptsType,
                    $acceptsType => (object) [],
                ],
                'inputs' => array_map(fn ($i) => (object) $i, $inputs),
            ],
        ],
    ]);
}

it('maps an input_list question with correct type and subquestion_mapping', function () {
    $mapper = new InputListQuestionMapper;
    $result = $mapper->mapQuestion(makeInputListQuestion(667012, [
        ['input_id' => 1745983, 'label' => 'First and last name'],
        ['input_id' => 1745984, 'label' => 'Street address'],
        ['input_id' => 1745985, 'label' => 'Postal code and city'],
    ]), 1);

    expect($result['question_id'])->toBe(667012)
        ->and($result['type'])->toBe('input_list')
        ->and($result['subquestion_mapping'])->toHaveCount(3)
        ->and($result['subquestion_mapping'][1745983])->toBe(['question_id' => 1745983, 'field' => 'question_1_1'])
        ->and($result['subquestion_mapping'][1745984])->toBe(['question_id' => 1745984, 'field' => 'question_1_2'])
        ->and($result['subquestion_mapping'][1745985])->toBe(['question_id' => 1745985, 'field' => 'question_1_3']);
});

it('sets mapped_data_type to string when accepts.type is text', function () {
    $mapper = new InputListQuestionMapper;
    $result = $mapper->mapQuestion(makeInputListQuestion(667012, [
        ['input_id' => 1745983, 'label' => 'Name'],
    ], 'text'), 1);

    expect($result['mapped_data_type'])->toBe('string');
});

it('sets mapped_data_type to int when accepts.type is number', function () {
    $mapper = new InputListQuestionMapper;
    $result = $mapper->mapQuestion(makeInputListQuestion(667012, [
        ['input_id' => 1745983, 'label' => 'Age'],
    ], 'number'), 1);

    expect($result['mapped_data_type'])->toBe('int');
});

it('encodes the question counter in all field names', function () {
    $mapper = new InputListQuestionMapper;
    $result = $mapper->mapQuestion(makeInputListQuestion(667012, [
        ['input_id' => 1745983, 'label' => 'Name'],
        ['input_id' => 1745984, 'label' => 'Address'],
    ]), 3);

    expect($result['subquestion_mapping'][1745983]['field'])->toBe('question_3_1')
        ->and($result['subquestion_mapping'][1745984]['field'])->toBe('question_3_2');
});
