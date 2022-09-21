<?php

namespace Statikbe\Surveyhero\Services\Info;

    class ResponseImportInfo
    {
        private int $totalResponsesImported;

        private array $unimportedQuestions;

        private array $unimportedAnswers;

        public function __construct()
        {
            $this->totalResponsesImported = 0;
            $this->unimportedAnswers = [];
            $this->unimportedQuestions = [];
        }

        public function addInfo(?ResponseImportInfo $newInfo): self
        {
            if (! $newInfo) {
                $this->totalResponsesImported += $newInfo->getTotalResponsesImported();
                $this->unimportedQuestions = array_merge($this->unimportedQuestions, $newInfo->getUnimportedQuestions());
                $this->unimportedAnswers = array_merge($this->unimportedAnswers, $newInfo->getUnimportedAnswers());
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

        public function increateTotalResponses()
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

        /**
         * @return int
         */
        public function getTotalResponsesImported(): int
        {
            return $this->totalResponsesImported;
        }

        /**
         * @param  int  $totalResponsesImported
         */
        public function setTotalResponsesImported(int $totalResponsesImported): void
        {
            $this->totalResponsesImported = $totalResponsesImported;
        }

        /**
         * @return array
         */
        public function getUnimportedQuestions(): array
        {
            return $this->unimportedQuestions;
        }

        /**
         * @return array
         */
        public function getUnimportedAnswers(): array
        {
            return $this->unimportedAnswers;
        }
    }
