<?php

namespace Statikbe\Surveyhero\Services\Factories\QuestionMapper;

interface QuestionMapper
{
    public function mapQuestion(\stdClass $question, int $questionCounter): array;
}
