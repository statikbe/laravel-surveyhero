<?php

namespace Statikbe\Surveyhero\Exceptions;

class QuestionNotImportedException extends \Exception
{
    public int $answerId;

    public static function create(int $answerId, string $message): self
    {
        $ex = new self($message);
        $ex->answerId = $answerId;

        return $ex;
    }
}
