<?php

    namespace Statikbe\Surveyhero\Exceptions;

    use Statikbe\Surveyhero\Models\Survey;

    class SurveyNotMappedException extends \Exception {
        public Survey $survey;

        public static function create(Survey $survey, ?string $message): self
        {
            $ex = new self($message);
            $ex->survey = $survey;

            return $ex;
        }
    }
