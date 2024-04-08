<?php

namespace Statikbe\Surveyhero\Exports\Sheets;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Statikbe\Surveyhero\Contracts\SurveyAnswerContract;
use Statikbe\Surveyhero\Contracts\SurveyContract;
use Statikbe\Surveyhero\SurveyheroRegistrar;

class AnswersSheet implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithTitle
{
    private SurveyContract $survey;

    private array $locales;

    private ?string $title;

    public function __construct(SurveyContract $survey)
    {
        $this->survey = $survey;
        $this->locales = $this->getLabelLocales();
        $this->title = null;
    }

    public function title(): string
    {
        if ($this->title) {
            return $this->title;
        }

        return 'Answers';
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function query()
    {
        $questionTable = config('surveyhero.table_names.survey_questions');
        $surveyTable = config('surveyhero.table_names.surveys');

        return app(SurveyheroRegistrar::class)->getSurveyAnswerClass()::query()
            ->join($questionTable['name'], $questionTable['name'].'.id', '=', $questionTable['foreign_key'])
            ->where($surveyTable['foreign_key'], '=', $this->survey->id)
            ->whereNotNull('surveyhero_answer_id')
            ->with('surveyQuestion');
    }

    public function headings(): array
    {
        $headings = [
            'surveyhero_question_id',
            'surveyhero_answer_id',
            'converted_string_value',
            'converted_int_value',
        ];

        foreach ($this->locales as $locale) {
            $headings[] = "answer_$locale";
        }

        return $headings;
    }

    /**
     * @param  SurveyAnswerContract  $surveyAnswer
     */
    public function map($surveyAnswer): array
    {
        $data = [
            $surveyAnswer->surveyQuestion->surveyhero_question_id,
            $surveyAnswer->surveyhero_answer_id,
            $surveyAnswer->converted_string_value,
            $surveyAnswer->converted_int_value,
        ];

        foreach ($this->locales as $locale) {
            $data[] = $surveyAnswer->translate('label', $locale);
        }

        return $data;
    }

    private function getLabelLocales(): array
    {
        $answerTable = config('surveyhero.table_names.survey_answers');
        $labelLocales = DB::select('select distinct JSON_KEYS(label) as label from '.$answerTable['name']);

        $locales = [];
        foreach ($labelLocales as $labelLocale) {
            if ($labelLocale && $labelLocale->label) {
                $jsonData = json_decode($labelLocale->label);
                foreach ($jsonData as $locale) {
                    $locales[$locale] = $locale;
                }
            }
        }

        return $locales;
    }
}
