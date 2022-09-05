<?php

namespace Statikbe\Surveyhero\Exceptions;

class QuestionNotImportedException extends \Exception
{
    public int $questionId;

    public static function create(int $questionId, string $message): self
    {
        $ex = new self($message);
        $ex->questionId = $questionId;

        return $ex;
    }
}
