<?php

use Saloon\Enums\Method;
use Statikbe\Surveyhero\Http\Requests\GetSurveysRequest;

it('resolves the correct endpoint', function () {
    $request = new GetSurveysRequest;
    expect($request->resolveEndpoint())->toBe('surveys');
});

it('uses the GET method', function () {
    $request = new GetSurveysRequest;
    expect($request->getMethod())->toBe(Method::GET);
});
