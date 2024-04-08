<?php

namespace Statikbe\Surveyhero;

use Statikbe\Surveyhero\Contracts\SurveyAnswerContract;
use Statikbe\Surveyhero\Contracts\SurveyContract;
use Statikbe\Surveyhero\Contracts\SurveyQuestionContract;
use Statikbe\Surveyhero\Contracts\SurveyQuestionResponseContract;
use Statikbe\Surveyhero\Contracts\SurveyResponseContract;
use Statikbe\Surveyhero\Models\Survey;
use Statikbe\Surveyhero\Models\SurveyAnswer;
use Statikbe\Surveyhero\Models\SurveyQuestion;
use Statikbe\Surveyhero\Models\SurveyQuestionResponse;
use Statikbe\Surveyhero\Models\SurveyResponse;

class SurveyheroRegistrar
{
    protected string $surveyClass;

    protected string $surveyQuestionClass;

    protected string $surveyAnswerClass;

    protected string $surveyResponseClass;

    protected string $surveyQuestionResponseClass;

    public function __construct()
    {
        $this->surveyClass = config('surveyhero.models.survey', Survey::class);
        $this->surveyQuestionClass = config('surveyhero.models.survey_question', SurveyQuestion::class);
        $this->surveyAnswerClass = config('surveyhero.models.survey_answer', SurveyAnswer::class);
        $this->surveyResponseClass = config('surveyhero.models.survey_response', SurveyResponse::class);
        $this->surveyQuestionResponseClass = config('surveyhero.models.survey_question_response', SurveyQuestionResponse::class);
    }

    /**
     * Get an instance of the survey class.
     */
    public function getSurveyClass(): SurveyContract
    {
        return app($this->surveyClass);
    }

    public function setSurveyClass($surveyClass): self
    {
        $this->surveyClass = $surveyClass;
        config()->set('surveyhero.models.survey', $surveyClass);
        app()->bind(SurveyContract::class, $surveyClass);

        return $this;
    }

    /**
     * Get an instance of the survey question class.
     */
    public function getSurveyQuestionClass(): SurveyQuestionContract
    {
        return app($this->surveyQuestionClass);
    }

    public function setSurveyQuestionClass($surveyQuestionClass): self
    {
        $this->surveyQuestionClass = $surveyQuestionClass;
        config()->set('surveyhero.models.survey_question', $surveyQuestionClass);
        app()->bind(SurveyQuestionContract::class, $surveyQuestionClass);

        return $this;
    }

    /**
     * Get an instance of the survey answer class.
     */
    public function getSurveyAnswerClass(): SurveyAnswerContract
    {
        return app($this->surveyAnswerClass);
    }

    public function setSurveyAnswerClass($surveyAnswerClass): self
    {
        $this->surveyAnswerClass = $surveyAnswerClass;
        config()->set('surveyhero.models.survey_answer', $surveyAnswerClass);
        app()->bind(SurveyAnswerContract::class, $surveyAnswerClass);

        return $this;
    }

    /**
     * Get an instance of the survey class.
     */
    public function getSurveyResponseClass(): SurveyResponseContract
    {
        return app($this->surveyResponseClass);
    }

    public function setSurveyResponseClass($surveyResponseClass): self
    {
        $this->surveyResponseClass = $surveyResponseClass;
        config()->set('surveyhero.models.survey_response', $surveyResponseClass);
        app()->bind(SurveyResponseContract::class, $surveyResponseClass);

        return $this;
    }

    /**
     * Get an instance of the survey question response class.
     */
    public function getSurveyQuestionResponseClass(): SurveyQuestionResponseContract
    {
        return app($this->surveyQuestionResponseClass);
    }

    public function setSurveyQuestionResponseClass($surveyQuestionResponseClass): self
    {
        $this->surveyQuestionResponseClass = $surveyQuestionResponseClass;
        config()->set('surveyhero.models.survey_question_response', $surveyQuestionResponseClass);
        app()->bind(SurveyQuestionResponseContract::class, $surveyQuestionResponseClass);

        return $this;
    }
}
