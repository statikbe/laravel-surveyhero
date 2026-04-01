<?php

namespace Statikbe\Surveyhero\Exceptions;

/**
 * This exception is used in the webhook when we decided to discard a response and do not ask Surveyhero to resend it later.
 */
class UnwantedResponseNotImportedException extends \Exception
{
    public mixed $responseData;

    public static function create(mixed $responseData, ?string $message): self
    {
        $ex = new self($message);
        $ex->$responseData = $responseData;

        return $ex;
    }
}
