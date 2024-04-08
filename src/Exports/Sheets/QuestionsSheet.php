<?php

namespace Statikbe\Surveyhero\Exports\Sheets;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Statikbe\Surveyhero\Contracts\SurveyContract;
use Statikbe\Surveyhero\Contracts\SurveyQuestionContract;

class QuestionsSheet implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithTitle
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

        return 'Questions';
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function query()
    {
        return $this->survey->surveyQuestions();
    }

    public function headings(): array
    {
        $headings = [
            'surveyhero_question_id',
            'field',
        ];

        foreach ($this->locales as $locale) {
            $headings[] = "question_$locale";
        }

        return $headings;
    }

    /**
     * @param  SurveyQuestionContract  $surveyQuestion
     */
    public function map($surveyQuestion): array
    {
        $data = [
            $surveyQuestion->surveyhero_question_id,
            $surveyQuestion->field,
        ];

        foreach ($this->locales as $locale) {
            $data[] = $surveyQuestion->translate('label', $locale);
        }

        return $data;
    }

    private function getLabelLocales(): array
    {
        $questionTable = config('surveyhero.table_names.survey_questions');
        $labelLocales = DB::select('select distinct JSON_KEYS(label) as label from '.$questionTable['name']);

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
