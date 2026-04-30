<?php

use Statikbe\Surveyhero\Http\DTO\SurveyElementDTO;
use Statikbe\Surveyhero\Services\Factories\QuestionMapper\FileUploadQuestionMapper;

function makeFileUploadQuestion(int $elementId): SurveyElementDTO
{
    return SurveyElementDTO::fromResponseObject((object) [
        'element_id' => $elementId,
        'type' => 'question',
        'question' => (object) [
            'type' => 'file_upload',
            'question_text' => 'Please upload your image here:',
            'file_upload' => (object) [
                'max_file_size_in_mb' => 25,
                'accepted_file_types' => ['gif', 'jpg', 'jpeg', 'png'],
            ],
        ],
    ]);
}

it('maps a file_upload question to an array', function () {
    $mapper = new FileUploadQuestionMapper;
    $result = $mapper->mapQuestion(makeFileUploadQuestion(1387752), 1);

    expect($result)->toBeArray()
        ->and($result['question_id'])->toBe(1387752)
        ->and($result['type'])->toBe('file_upload')
        ->and($result['mapped_data_type'])->toBe('string');
});

it('generates a field name based on the question counter', function () {
    $mapper = new FileUploadQuestionMapper;
    $result = $mapper->mapQuestion(makeFileUploadQuestion(1387752), 3);

    expect($result['field'])->toContain('3');
});
