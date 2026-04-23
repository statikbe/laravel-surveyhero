<?php

use Statikbe\Surveyhero\Http\DTO\SurveyElementDTO;
use Statikbe\Surveyhero\Services\Factories\QuestionMapper\InputQuestionMapper;

function makeInputQuestion(int $elementId): SurveyElementDTO
{
    return SurveyElementDTO::fromResponseObject((object) [
        'element_id' => $elementId,
        'type' => 'question',
        'question' => (object) [
            'type' => 'input',
            'question_text' => 'Test question',
        ],
    ]);
}

it('maps an input question to an array', function () {
    $mapper = new InputQuestionMapper;
    $result = $mapper->mapQuestion(makeInputQuestion(1000005), 1);

    expect($result)->toBeArray()
        ->and($result['question_id'])->toBe(1000005)
        ->and($result['type'])->toBe('input')
        ->and($result['mapped_data_type'])->toBe('string');
});

it('generates a field name based on the question counter', function () {
    $mapper = new InputQuestionMapper;
    $result = $mapper->mapQuestion(makeInputQuestion(1000005), 3);

    expect($result['field'])->toContain('3');
});
