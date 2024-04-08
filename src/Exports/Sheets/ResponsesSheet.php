<?php

namespace Statikbe\Surveyhero\Exports\Sheets;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Statikbe\Surveyhero\Contracts\SurveyContract;

class ResponsesSheet implements FromCollection, ShouldAutoSize, WithHeadings, WithTitle
{
    private SurveyContract $survey;

    private array $linkParameters;

    private array $extraResponseColumns;

    private array $questionFields = [];

    private ?string $title;

    public function __construct(SurveyContract $survey, array $linkParameters, array $extraResponseColumns)
    {
        $this->survey = $survey;
        $this->linkParameters = $linkParameters;
        $this->extraResponseColumns = $extraResponseColumns;
        $this->title = null;
    }

    public function collection()
    {
        $query = $this->query();
        $responses = $query->get();

        $groupedResponses = $responses->mapToGroups(function ($item, $key) {
            return [$item->surveyhero_response_id => $item];
        });

        return $groupedResponses->map(function ($responses, $key) {
            return $this->transposeResponse($responses, $key);
        });
    }

    public function query()
    {
        $surveyTable = config('surveyhero.table_names.surveys');
        $questionTable = config('surveyhero.table_names.survey_questions');
        $responsesTable = config('surveyhero.table_names.survey_responses');
        $answersTable = config('surveyhero.table_names.survey_answers');
        $questionResponseTable = config('surveyhero.table_names.survey_question_responses');

        $selectRows = [
            $responsesTable['name'].'.surveyhero_id as surveyhero_response_id',
            $questionTable['name'].'.surveyhero_question_id',
            $questionTable['name'].'.field as question_field',
            $answersTable['name'].'.surveyhero_answer_id',
            $answersTable['name'].'.converted_string_value',
            $answersTable['name'].'.converted_int_value',
            $responsesTable['name'].'.surveyhero_link_parameters',
        ];

        foreach ($this->extraResponseColumns as $extraResponseColumn) {
            $selectRows[] = $responsesTable['name'].'.'.$extraResponseColumn;
        }

        //TODO filter on link parameters
        return DB::table($responsesTable['name'])
            ->join($questionResponseTable['name'], $questionResponseTable['name'].'.'.$responsesTable['foreign_key'], '=', $responsesTable['name'].'.id')
            ->join($questionTable['name'], $questionResponseTable['name'].'.'.$questionTable['foreign_key'], '=', $questionTable['name'].'.id')
            ->join($answersTable['name'], $questionResponseTable['name'].'.'.$answersTable['foreign_key'], $answersTable['name'].'.id')
            ->select($selectRows)
            ->where($responsesTable['name'].'.'.$surveyTable['foreign_key'], '=', $this->survey->id)
            ->orderBy('surveyhero_response_id', 'asc')
            ->orderBy($questionTable['name'].'.surveyhero_question_id', 'asc');
    }

    public function headings(): array
    {
        $headings = [
            'surveyhero_response_id',
        ];

        //link parameters
        $headings = array_merge($headings, $this->linkParameters);

        //extra response columns
        $headings = array_merge($headings, $this->extraResponseColumns);

        foreach ($this->survey->surveyQuestions as $question) {
            $headings[] = $question->field;
            $this->questionFields[] = $question->field;
        }

        return $headings;
    }

    public function title(): string
    {
        if ($this->title) {
            return $this->title;
        }

        return 'Responses';
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    private function transposeResponse(Collection $responses, int $key): array
    {
        $responseData = [
            $key,
        ];
        //link parameters
        $linkParameterValues = [];
        foreach ($this->linkParameters as $linkParameter) {
            foreach ($responses as $response) {
                $jsonData = json_decode($response->surveyhero_link_parameters);
                $linkParameterValues[$linkParameter] = ($jsonData->{$linkParameter} ?? null);
            }
        }
        //  make sure the link parameters are inserted in the same order as in the headers:
        foreach ($this->linkParameters as $linkParameter) {
            if ($linkParameterValues[$linkParameter]) {
                $responseData[] = $linkParameterValues[$linkParameter];
            } else {
                $responseData[] = null;
            }
        }

        //extra response colummns:
        $extraResponseValues = [];
        foreach ($this->extraResponseColumns as $extraResponseColumn) {
            foreach ($responses as $response) {
                $extraResponseValues[$extraResponseColumn] = $response->{$extraResponseColumn};
            }
        }
        //  make sure the extra columns are inserted in the same order as in the headers:
        foreach ($this->extraResponseColumns as $extraResponseColumn) {
            if ($extraResponseValues[$extraResponseColumn]) {
                $responseData[] = $extraResponseValues[$extraResponseColumn];
            } else {
                $responseData[] = null;
            }
        }

        //questions
        foreach ($this->questionFields as $questionField) {
            $answers = [];
            foreach ($responses as $response) {
                if ($response->question_field === $questionField) {
                    $answers[] = $response->converted_string_value ?? $response->converted_int_value;
                }
            }
            $responseData[] = implode('|', $answers);
        }

        return $responseData;
    }
}
