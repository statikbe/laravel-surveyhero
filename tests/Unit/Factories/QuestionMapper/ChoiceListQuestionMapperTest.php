<?php

use Statikbe\Surveyhero\Services\Factories\QuestionMapper\ChoiceListQuestionMapper;

function makeChoiceListQuestion(int $elementId, array $choices): stdClass
{
    return (object) [
        'element_id' => $elementId,
        'question' => (object) [
            'type' => 'choice_list',
            'question_text' => 'Pick one',
            'choice_list' => (object) [
                'choices' => array_map(fn ($c) => (object) $c, $choices),
            ],
        ],
    ];
}

it('maps a choice_list question', function () {
    $mapper = new ChoiceListQuestionMapper;
    $result = $mapper->mapQuestion(makeChoiceListQuestion(1000002, [
        ['choice_id' => 13509166, 'label' => 'A'],
        ['choice_id' => 13509167, 'label' => 'B'],
    ]), 1);

    expect($result['question_id'])->toBe(1000002)
        ->and($result['type'])->toBe('choice_list')
        ->and($result['mapped_data_type'])->toBe('int');
});

it('generates an answer_mapping from choice IDs to sequential integers', function () {
    $mapper = new ChoiceListQuestionMapper;
    $result = $mapper->mapQuestion(makeChoiceListQuestion(1000002, [
        ['choice_id' => 13509166, 'label' => 'A'],
        ['choice_id' => 13509167, 'label' => 'B'],
        ['choice_id' => 13509168, 'label' => 'C'],
    ]), 1);

    expect($result['answer_mapping'])->toBe([
        13509166 => 1,
        13509167 => 2,
        13509168 => 3,
    ]);
});
