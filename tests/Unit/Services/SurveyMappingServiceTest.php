<?php

use Statikbe\Surveyhero\Exceptions\SurveyNotMappedException;
use Statikbe\Surveyhero\Models\Survey;
use Statikbe\Surveyhero\Services\SurveyMappingService;

it('returns the full question mapping for a mapped survey', function () {
    $survey = Survey::factory()->create(['surveyhero_id' => 1234567]);
    $service = new SurveyMappingService;

    $mapping = $service->getSurveyQuestionMapping($survey);

    expect($mapping)->toBeArray()->not->toBeEmpty();
});

it('throws SurveyNotMappedException when no mapping exists for survey', function () {
    $survey = Survey::factory()->create(['surveyhero_id' => 9999999]);
    $service = new SurveyMappingService;

    $service->getSurveyQuestionMapping($survey);
})->throws(SurveyNotMappedException::class);

it('returns the question mapping for a known question ID', function () {
    $survey = Survey::factory()->create(['surveyhero_id' => 1234567]);
    $service = new SurveyMappingService;

    $mapping = $service->getQuestionMappingForSurvey($survey, 1000002);

    expect($mapping)->toBeArray()
        ->and($mapping['type'])->toBe('choices')
        ->and($mapping['field'])->toBe('question_2');
});

it('returns null for an unknown question ID', function () {
    $survey = Survey::factory()->create(['surveyhero_id' => 1234567]);
    $service = new SurveyMappingService;

    $mapping = $service->getQuestionMappingForSurvey($survey, 9999);

    expect($mapping)->toBeNull();
});

it('returns collectors for a mapped survey from config', function () {
    $survey = Survey::factory()->create(['surveyhero_id' => 1234567]);
    $service = new SurveyMappingService;

    $collectors = $service->getSurveyCollectors($survey);

    expect($collectors)->toBeArray()
        ->and($collectors[0])->toBe(9876543);
});

it('returns the question mapping by question ID from array', function () {
    $service = new SurveyMappingService;
    $surveyQuestionMapping = [
        1000002 => ['question_id' => 1000002, 'type' => 'choices', 'field' => 'question_2'],
        1000005 => ['question_id' => 1000005, 'type' => 'input', 'field' => 'question_5'],
    ];

    $result = $service->getQuestionMapping($surveyQuestionMapping, 1000005);

    expect($result['type'])->toBe('input');
});

it('returns null from getQuestionMapping for an unmapped question', function () {
    $service = new SurveyMappingService;

    $result = $service->getQuestionMapping([], 999);

    expect($result)->toBeNull();
});

it('returns a field name via findQuestionField', function () {
    $survey = Survey::factory()->create(['surveyhero_id' => 1234567]);
    $service = new SurveyMappingService;

    $field = $service->findQuestionField($survey, '1000005');

    expect($field)->toBe('question_5');
});
