<?php

namespace Statikbe\Surveyhero\Exceptions;

class InvalidConfigurationException extends \Exception
{
    private const PUBLISH_HINT = 'If the config file is missing, publish it with: php artisan vendor:publish --tag="laravel-surveyhero-config".';

    /**
     * @param  array<string, string>  $missingKeys  config key => env variable
     */
    public static function missingApiCredentials(array $missingKeys): self
    {
        $configKeys = implode(' and ', array_map(fn ($key) => "surveyhero.$key", array_keys($missingKeys)));
        $envKeys = implode(' and ', $missingKeys);

        return new self(sprintf(
            'The Surveyhero API credentials are not configured: %s %s empty. Set %s in your .env file. %s',
            $configKeys,
            count($missingKeys) > 1 ? 'are' : 'is',
            $envKeys,
            self::PUBLISH_HINT
        ));
    }

    public static function missingQuestionMapping(): self
    {
        return new self(sprintf(
            'The Surveyhero question mapping is not configured: surveyhero.question_mapping is empty, so no survey can be imported. '
            .'Generate a mapping with: php artisan surveyhero:map --generateConfig. %s',
            self::PUBLISH_HINT
        ));
    }

    public static function malformedQuestionMapping(string $reason): self
    {
        return new self(sprintf(
            'The Surveyhero question mapping in surveyhero.question_mapping is not well-formed: %s',
            $reason
        ));
    }
}
