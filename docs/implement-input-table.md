# Plan: implement `input_table` question type

## Overview

`input_table` displays a grid of input fields arranged in named rows and
columns. Each **cell** (row × column intersection) holds an independent answer.
This is the most structurally complex unsupported type because neither rows nor
columns alone are the unit of storage — the cell is. The approach: encode each
(row*id, column_id) pair as a single composite sub-question, using
`"{row_id}*{column_id}"`as the`surveyhero_question_id`. This extends the existing sub-question pattern from `choice_table`/`input_list`
without requiring schema changes.

---

## API shapes

**Question definition:**

```json
{
  "element_id": 666981,
  "type": "question",
  "question": {
    "question_text": "Hours per day using each product:",
    "type": "input_table",
    "input_table": {
      "accepts": { "type": "number", "number": {} },
      "rows": [
        { "row_id": 1745901, "label": "Product A" },
        { "row_id": 1745902, "label": "Product B" }
      ],
      "columns": [
        { "column_id": 177301, "label": "Mon" },
        { "column_id": 177302, "label": "Tue" }
      ]
    },
    "settings": { "is_required": false }
  }
}
```

**Response:**

```json
{
  "element_id": 666981,
  "type": "input_table",
  "input_table": [
    {
      "row_id": 1745901,
      "label": "Product A",
      "columns": [
        {
          "column_id": 177302,
          "label": "Tue",
          "answer": { "type": "number", "number": 1.5 }
        }
      ]
    }
  ]
}
```

> Rows with no answers, and columns within a row with no answer, are omitted
> from the response.

---

## What needs to be built

### 1. `InputTableQuestionMapper`

**File:** `src/Services/Factories/QuestionMapper/InputTableQuestionMapper.php`

Iterates every (row, column) pair and creates a sub-question entry for each
cell. The composite key `"{row_id}_{column_id}"` is used as the `question_id` in
`subquestion_mapping`. The field name encodes both row and column indices.

```php
const TYPE = 'input_table';

public function mapQuestion(SurveyElementDTO $question, int $questionCounter): array
{
    $acceptsType    = $question->question->input_table->accepts->type;
    $mappedDataType = $acceptsType === 'number'
        ? SurveyAnswerContract::CONVERTED_TYPE_INT
        : SurveyAnswerContract::CONVERTED_TYPE_STRING;

    $mapping = [
        'question_id'         => $question->element_id,
        'type'                => $question->question->type,
        'subquestion_mapping' => [],
        'mapped_data_type'    => $mappedDataType,
    ];

    $rowIndex = 1;
    foreach ($question->question->input_table->rows as $row) {
        $colIndex = 1;
        foreach ($question->question->input_table->columns as $col) {
            $compositeId = "{$row->row_id}_{$col->column_id}";
            $mapping['subquestion_mapping'][$compositeId] = [
                'question_id' => $compositeId,
                'field'       => "question_{$questionCounter}_{$rowIndex}_{$colIndex}",
            ];
            $colIndex++;
        }
        $rowIndex++;
    }

    return $mapping;
}
```

Generated config (2×2 example):

```php
[
    'question_id' => 666981,
    'type'        => 'input_table',
    'subquestion_mapping' => [
        '1745901_177301' => ['question_id' => '1745901_177301', 'field' => 'question_1_1_1'],
        '1745901_177302' => ['question_id' => '1745901_177302', 'field' => 'question_1_1_2'],
        '1745902_177301' => ['question_id' => '1745902_177301', 'field' => 'question_1_2_1'],
        '1745902_177302' => ['question_id' => '1745902_177302', 'field' => 'question_1_2_2'],
    ],
    'mapped_data_type' => 'int',
]
```

### 2. `InputTableQuestionAndAnswerCreator`

**File:**
`src/Services/Factories/QuestionAndAnswerCreator/InputTableQuestionAndAnswerCreator.php`

Creates one `SurveyQuestion` per cell. The label combines row and column labels
(e.g., `"Product A — Mon"`) so that consumers have human-readable field names.
The `surveyhero_question_id` is the composite key.

```php
const TYPE = 'input_table';

public function updateOrCreateQuestionAndAnswer(SurveyElementDTO $question, SurveyContract $survey, string $lang): SurveyQuestionContract|array
{
    $questions = [];
    foreach ($question->question->input_table->rows as $row) {
        foreach ($question->question->input_table->columns as $col) {
            $compositeId = "{$row->row_id}_{$col->column_id}";
            $label       = "{$row->label} — {$col->label}";

            $questions[] = $this->updateOrCreateQuestion(
                $survey, $lang,
                $question->element_id,  // surveyhero_element_id
                $label,
                $compositeId            // surveyhero_question_id
            );
        }
    }
    return $questions;
}
```

No `SurveyAnswer` rows are pre-seeded — inputs are free-form.

### 3. `InputTableResponseCreator`

**File:** `src/Services/Factories/ResponseCreator/InputTableResponseCreator.php`

Handles `type: input_table` responses. For each answered cell, it builds the
composite key from `row_id` and `column_id`, looks up the sub-question, and
delegates to `fetchOrCreateInputAnswer`.

```php
const TYPE = 'input_table';

public function updateOrCreateQuestionResponse(
    \stdClass $surveyheroQuestionResponse,
    SurveyResponseContract $response,
    array $questionMapping): SurveyQuestionResponseContract|array
{
    $responseList   = [];
    $mappingService = new SurveyMappingService;
    $dataType       = $questionMapping['mapped_data_type'] ?? SurveyAnswerContract::CONVERTED_TYPE_STRING;

    foreach ($surveyheroQuestionResponse->input_table as $row) {
        foreach ($row->columns as $col) {
            $compositeId        = "{$row->row_id}_{$col->column_id}";
            $subquestionMapping = $mappingService->getSubquestionMapping($compositeId, $questionMapping);
            $surveyQuestion     = $this->findSurveyQuestion($compositeId);

            $rawValue = match ($col->answer->type) {
                'number' => $col->answer->number,
                default  => $col->answer->text,
            };

            $surveyAnswer     = $this->fetchOrCreateInputAnswer($surveyQuestion, $dataType, $rawValue);
            $existingResponse = $this->findExistingQuestionResponse($subquestionMapping['question_id'], $response);
            $responseData     = $this->createSurveyQuestionResponseData($surveyQuestion, $response, $surveyAnswer);

            $responseList[] = app(SurveyheroRegistrar::class)->getSurveyQuestionResponseClass()::updateOrCreate(
                ['id' => $existingResponse->id ?? null],
                $responseData
            );
        }
    }

    return $responseList;
}
```

### 4. Register in service dispatch tables

**`SurveyMappingService::getQuestionMapper()`**:

```php
InputTableQuestionMapper::TYPE => new InputTableQuestionMapper,
```

**`SurveyQuestionsAndAnswersImportService::getQuestionAndAnswerCreator()`**:

```php
InputTableQuestionAndAnswerCreator::TYPE => new InputTableQuestionAndAnswerCreator,
```

**`SurveyResponseImportService::getQuestionResponseCreator()`**:

```php
InputTableResponseCreator::TYPE => new InputTableResponseCreator,
```

---

## Key design decision: composite key as `surveyhero_question_id`

`SurveyMappingService::getSubquestionMapping()` looks up `subquestion_mapping`
by key. Using a string composite key (`"row_id_col_id"`) requires that the
lookup key matches exactly what is stored in `subquestion_mapping`. Verify that
`getSubquestionMapping()` supports string keys — it uses `array_key_exists`, so
any scalar type works.

The `SurveyQuestion.surveyhero_question_id` column must accept strings (it does
— it's a `string` field in existing migrations). No schema changes needed.

---

## Tests

- Fixture `tests/Fixtures/Saloon/get-survey-questions-input-table.json` — 2 rows
  × 2 columns.
- Fixture `tests/Fixtures/Saloon/get-survey-response-input-table.json` — partial
  response (one row fully answered, one row with one missing cell) to verify
  sparse handling.
- Unit test for `InputTableQuestionMapper` — assert composite keys and field
  naming for a 2×2 grid.
- Unit test for `InputTableQuestionAndAnswerCreator` — assert `SurveyQuestion`
  count equals rows × columns, labels are combined correctly.
- Unit test for `InputTableResponseCreator` — assert cell values stored to
  correct sub-questions, missing cells produce no response row.

---

## Complexity estimate

High. Novel composite-key sub-question pattern, 2D iteration in three layers,
and partial-response handling. Verify
`SurveyMappingService::getSubquestionMapping()` handles string keys before
assuming it works end-to-end.
