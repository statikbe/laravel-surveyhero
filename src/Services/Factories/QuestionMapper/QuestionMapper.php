<?php

namespace Statikbe\Surveyhero\Services\Factories\QuestionMapper;

use Statikbe\Surveyhero\Http\DTO\SurveyElementDTO;

interface QuestionMapper
{
    public function mapQuestion(SurveyElementDTO $question, int $questionCounter): array;
}
