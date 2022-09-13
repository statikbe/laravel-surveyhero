<?php

namespace Statikbe\Surveyhero\Exceptions;

use Statikbe\Surveyhero\Contracts\SurveyContract;

class SurveyNotMappedException extends \Exception
{
    public SurveyContract $survey;

    public static function create(SurveyContract $survey, ?string $message): self
    {
        $ex = new self($message);
        $ex->survey = $survey;

        return $ex;
    }
}
