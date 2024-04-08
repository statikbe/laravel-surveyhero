<?php

namespace Statikbe\Surveyhero\Services\Info;

use Carbon\Carbon;

class ResponseImportInfo
{
    private int $totalResponsesImported;

    private array $unimportedQuestions;

    private array $unimportedAnswers;

    private ?Carbon $surveyLastUpdatedAt;

    public function __construct()
    {
        $this->totalResponsesImported = 0;
        $this->unimportedAnswers = [];
        $this->unimportedQuestions = [];
        $this->surveyLastUpdatedAt = null;
    }

    public function addInfo(?ResponseImportInfo $newInfo): self
    {
        if ($newInfo) {
            $this->totalResponsesImported += $newInfo->getTotalResponsesImported();
            $this->unimportedQuestions = array_merge($this->unimportedQuestions, $newInfo->getUnimportedQuestions());
            $this->unimportedAnswers = array_merge($this->unimportedAnswers, $newInfo->getUnimportedAnswers());

            if ($newInfo->surveyLastUpdatedAt) {
                if (! $this->surveyLastUpdatedAt) {
                    $this->surveyLastUpdatedAt = $newInfo->surveyLastUpdatedAt;
                } else {
                    $this->surveyLastUpdatedAt = $newInfo->surveyLastUpdatedAt->max($this->surveyLastUpdatedAt);
                }
            }
        }

        return $this;
    }

    public function addUnimportedAnswer(int $answerId, string $errorInfo): void
    {
        $this->unimportedAnswers[$answerId] = $errorInfo;
    }

    public function addUnimportedQuestion(int $questionId, string $errorInfo): void
    {
        $this->unimportedQuestions[$questionId] = $errorInfo;
    }

    public function increaseTotalResponses()
    {
        $this->totalResponsesImported++;
    }

    public function hasUnimportedQuestions(): bool
    {
        return ! empty($this->unimportedQuestions);
    }

    public function hasUnimportedAnswers(): bool
    {
        return ! empty($this->unimportedAnswers);
    }

    public function getTotalResponsesImported(): int
    {
        return $this->totalResponsesImported;
    }

    public function setTotalResponsesImported(int $totalResponsesImported): void
    {
        $this->totalResponsesImported = $totalResponsesImported;
    }

    public function getUnimportedQuestions(): array
    {
        return $this->unimportedQuestions;
    }

    public function getUnimportedAnswers(): array
    {
        return $this->unimportedAnswers;
    }

    public function getSurveyLastUpdatedAt(): ?Carbon
    {
        return $this->surveyLastUpdatedAt;
    }

    public function setSurveyLastUpdatedAt(?Carbon $surveyLastUpdatedAt): void
    {
        if ($surveyLastUpdatedAt) {
            if ($this->surveyLastUpdatedAt) {
                $this->surveyLastUpdatedAt = $surveyLastUpdatedAt;
            } else {
                $this->surveyLastUpdatedAt = $surveyLastUpdatedAt->max($this->surveyLastUpdatedAt);
            }
        }
    }
}
