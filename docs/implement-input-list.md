# Plan: implement `input_list` question type

## Overview

`input_list` presents multiple labelled text (or numeric) input fields under one
question. Each field has its own `input_id` and `label`. The API response type
is `inputs`, with each entry carrying the `input_id`, `label`, and the
respondent's `answer`. Because answers are free-text, no `SurveyAnswer` rows are
pre-seeded — they are created dynamically at response-import time, exactly like
the existing `input` type. Each input field becomes its own `SurveyQuestion` row
(sub-question pattern from `choice_table`).

---

## API shapes

**Question definition:**

```json
{
  "element_id": 667012,
  "type": "question",
  "question": {
    "question_text": "Please enter your contact details:",
    "type": "input_list",
    "input_list": {
      "accepts": {
        "type": "text",
        "text": { "max_number_of_characters": null }
      },
      "inputs": [
        { "input_id": 1745983, "label": "First and last name" },
        { "input_id": 1745984, "label": "Street address" },
        { "input_id": 1745985, "label": "Postal code and city" }
      ]
    },
    "settings": { "is_required": false }
  }
}
```

**Response:**

```json
{
  "element_id": 667012,
  "type": "inputs",
  "inputs": [
    {
      "input_id": 1745983,
      "label": "First and last name",
      "answer": { "type": "text", "text": "John Smith" }
    },
    {
      "input_id": 1745985,
      "label": "Postal code and city",
      "answer": { "type": "text", "text": "8008 Zürich" }
    }
  ]
}
```

> Note: unanswered inputs are omitted from the response array.

---

## What needs to be built

### 1. `InputListQuestionMapper`

**File:** `src/Services/Factories/QuestionMapper/InputListQuestionMapper.php`

Each `input_id` becomes a sub-question entry. The `mapped_data_type` is derived
from `input_list.accepts.type` (`text` → `string`, `number` → `int`).

```php
const TYPE = 'input_list';

public function mapQuestion(SurveyElementDTO $question, int $questionCounter): array
{
    $acceptsType = $question->question->input_list->accepts->type;
    $mappedDataType = $acceptsType === 'number'
        ? SurveyAnswerContract::CONVERTED_TYPE_INT
        : SurveyAnswerContract::CONVERTED_TYPE_STRING;

    $mapping = [
        'question_id'         => $question->element_id,
        'type'                => $question->question->type,
        'subquestion_mapping' => [],
        'mapped_data_type'    => $mappedDataType,
    ];

    $subquestionIndex = 1;
    foreach ($question->question->input_list->inputs as $input) {
        $mapping['subquestion_mapping'][$input->input_id] = [
            'question_id' => $input->input_id,
            'field'       => "question_{$questionCounter}_{$subquestionIndex}",
        ];
        $subquestionIndex++;
    }

    return $mapping;
}
```

Generated config:

```php
[
    'question_id' => 667012,
    'type'        => 'input_list',
    'subquestion_mapping' => [
        1745983 => ['question_id' => 1745983, 'field' => 'question_1_1'],
        1745984 => ['question_id' => 1745984, 'field' => 'question_1_2'],
        1745985 => ['question_id' => 1745985, 'field' => 'question_1_3'],
    ],
    'mapped_data_type' => 'string',
]
```

### 2. `InputListQuestionAndAnswerCreator`

**File:**
`src/Services/Factories/QuestionAndAnswerCreator/InputListQuestionAndAnswerCreator.php`

Creates one `SurveyQuestion` per input field. No `SurveyAnswer` rows are created
(answers are unique per respondent).

```php
const TYPE = 'input_list';

public function updateOrCreateQuestionAndAnswer(SurveyElementDTO $question, SurveyContract $survey, string $lang): SurveyQuestionContract|array
{
    $questions = [];
    foreach ($question->question->input_list->inputs as $input) {
        $questions[] = $this->updateOrCreateQuestion(
            $survey, $lang,
            $question->element_id,  // surveyhero_element_id
            $input->label,
            $input->input_id        // surveyhero_question_id
        );
    }
    return $questions;
}
```

### 3. `InputsResponseCreator`

**File:** `src/Services/Factories/ResponseCreator/InputsResponseCreator.php`

Handles `type: inputs` responses. For each entry, it looks up the sub-question
by `input_id`, then delegates to `fetchOrCreateInputAnswer` with the answer
value (using `answer->text` for text types, `answer->number` for numeric types).

```php
const TYPE = 'inputs';

public function updateOrCreateQuestionResponse(
    \stdClass $surveyheroQuestionResponse,
    SurveyResponseContract $response,
    array $questionMapping): SurveyQuestionResponseContract|array
{
    $responseList   = [];
    $mappingService = new SurveyMappingService;
    $dataType       = $questionMapping['mapped_data_type'] ?? SurveyAnswerContract::CONVERTED_TYPE_STRING;

    foreach ($surveyheroQuestionResponse->inputs as $inputAnswer) {
        $subquestionMapping = $mappingService->getSubquestionMapping($inputAnswer->input_id, $questionMapping);
        $surveyQuestion     = $this->findSurveyQuestion($inputAnswer->input_id);

        $rawValue = match ($inputAnswer->answer->type) {
            'number' => $inputAnswer->answer->number,
            default  => $inputAnswer->answer->text,
        };

        $surveyAnswer     = $this->fetchOrCreateInputAnswer($surveyQuestion, $dataType, $rawValue);
        $existingResponse = $this->findExistingQuestionResponse($subquestionMapping['question_id'], $response);
        $responseData     = $this->createSurveyQuestionResponseData($surveyQuestion, $response, $surveyAnswer);

        $responseList[] = app(SurveyheroRegistrar::class)->getSurveyQuestionResponseClass()::updateOrCreate(
            ['id' => $existingResponse->id ?? null],
            $responseData
        );
    }

    return $responseList;
}
```

### 4. Register in service dispatch tables

**`SurveyMappingService::getQuestionMapper()`**:

```php
InputListQuestionMapper::TYPE => new InputListQuestionMapper,
```

**`SurveyQuestionsAndAnswersImportService::getQuestionAndAnswerCreator()`**:

```php
InputListQuestionAndAnswerCreator::TYPE => new InputListQuestionAndAnswerCreator,
```

**`SurveyResponseImportService::getQuestionResponseCreator()`**:

```php
InputsResponseCreator::TYPE => new InputsResponseCreator,
```

---

## Tests

- Fixture `tests/Fixtures/Saloon/get-survey-questions-input-list.json` with an
  `input_list` question.
- Fixture `tests/Fixtures/Saloon/get-survey-response-input-list.json` with an
  `inputs` response (include at least one unanswered input to verify omission is
  handled).
- Unit test for `InputListQuestionMapper` — assert `subquestion_mapping` entries
  and `mapped_data_type` derivation for both `text` and `number` accepts types.
- Unit test for `InputListQuestionAndAnswerCreator` — assert one
  `SurveyQuestion` per input, no `SurveyAnswer` rows.
- Unit test for `InputsResponseCreator` — assert `converted_string_value` /
  `converted_int_value` stored per answered input, missing inputs produce no
  response row.

---

## Complexity estimate

Medium-low. Follows the sub-question pattern of `choice_table` for structure,
and the dynamic-answer pattern of `input` / `TextResponseCreator` for response
handling.
