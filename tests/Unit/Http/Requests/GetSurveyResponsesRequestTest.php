<?php

use Carbon\Carbon;
use Statikbe\Surveyhero\Http\Requests\GetSurveyResponsesRequest;

it('resolves the correct endpoint', function () {
    $request = new GetSurveyResponsesRequest(1234567);
    expect($request->resolveEndpoint())->toBe('surveys/1234567/responses');
});

it('does not include query params when none are provided', function () {
    $request = new GetSurveyResponsesRequest(1234567);
    expect($request->query()->all())->toBeEmpty();
});

it('includes last_updated_on query param when a date is given', function () {
    $date = Carbon::parse('2024-06-01T00:00:00+00:00');
    $request = new GetSurveyResponsesRequest(1234567, $date);

    expect($request->query()->all())->toHaveKey('last_updated_on[from]')
        ->and($request->query()->all()['last_updated_on[from]'])->toBe($date->toIso8601String());
});

it('includes collector_id query param when collector IDs are given', function () {
    $request = new GetSurveyResponsesRequest(1234567, null, [111, 222]);

    expect($request->query()->all())->toHaveKey('collector_id')
        ->and($request->query()->all()['collector_id'])->toBe([111, 222]);
});

it('includes both date and collector params when provided', function () {
    $date = Carbon::parse('2024-01-01T00:00:00+00:00');
    $request = new GetSurveyResponsesRequest(1234567, $date, [9876543]);
    $query = $request->query()->all();

    expect($query)->toHaveKeys(['last_updated_on[from]', 'collector_id']);
});
