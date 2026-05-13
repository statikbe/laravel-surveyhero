<?php

use Statikbe\Surveyhero\Http\DTO\SurveyElementDTO;
use Statikbe\Surveyhero\Services\Factories\QuestionMapper\ImageChoiceListQuestionMapper;

function makeImageChoiceListQuestion(int $elementId, array $choices): SurveyElementDTO
{
    return SurveyElementDTO::fromResponseObject((object) [
        'element_id' => $elementId,
        'type' => 'question',
        'question' => (object) [
            'type' => 'image_choice_list',
            'question_text' => 'Pick your favourite image',
            'image_choice_list' => (object) [
                'choices' => array_map(fn ($c) => (object) $c, $choices),
                'settings' => (object) [
                    'allows_multiple_choices' => false,
                ],
            ],
        ],
    ]);
}

it('maps an image_choice_list question', function () {
    $mapper = new ImageChoiceListQuestionMapper;
    $result = $mapper->mapQuestion(makeImageChoiceListQuestion(666941, [
        ['choice_id' => 6244, 'label' => 'Beach', 'image_url' => 'https://example.com/beach.jpg'],
        ['choice_id' => 6245, 'label' => 'Woods', 'image_url' => 'https://example.com/woods.jpg'],
    ]), 1);

    expect($result['question_id'])->toBe(666941)
        ->and($result['type'])->toBe('image_choice_list')
        ->and($result['field'])->toBe('question_1')
        ->and($result['mapped_data_type'])->toBe('int');
});

it('generates an answer_mapping from choice IDs to sequential integers', function () {
    $mapper = new ImageChoiceListQuestionMapper;
    $result = $mapper->mapQuestion(makeImageChoiceListQuestion(666941, [
        ['choice_id' => 6244, 'label' => 'Beach', 'image_url' => 'https://example.com/beach.jpg'],
        ['choice_id' => 6245, 'label' => 'Woods', 'image_url' => 'https://example.com/woods.jpg'],
        ['choice_id' => 6246, 'label' => 'Mountains', 'image_url' => 'https://example.com/mountains.jpg'],
    ]), 1);

    expect($result['answer_mapping'])->toBe([
        6244 => 1,
        6245 => 2,
        6246 => 3,
    ]);
});
