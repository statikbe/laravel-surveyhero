<?php

namespace Statikbe\Surveyhero\Exceptions;

class QuestionMapperNotImplementedException extends \Exception
{
    public string $questionType;

    public static function create(string $questionType): self
    {
        $ex = new self("There is no mapper implementation for question type: $questionType");
        $ex->questionType = $questionType;

        return $ex;
    }
}
