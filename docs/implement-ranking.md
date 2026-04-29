# Plan: implement `ranking` question type

## Overview

`ranking` lets respondents order a list of choices by importance. The response
separates choices into `ranked_choices` (ordered array, position = index + 1)
and `not_applicable_choices` (choices the respondent skipped). Because rank
positions are per-response values, they cannot be pre-seeded as fixed
`SurveyAnswer` rows. Instead, each choice becomes a **sub-question** (one
`SurveyQuestion` row per choice), and the rank position is written as a dynamic
answer at response-import time — the same pattern used by `choice_table` rows.

---

## API shapes

**Question definition:**

```json
{
  "element_id": 1238491,
  "type": "question",
  "question": {
    "question_text": "Rank in order of importance:",
    "type": "ranking",
    "ranking": {
      "choices": [
        { "choice_id": 3132165, "label": "Health" },
        { "choice_id": 3132166, "label": "Family & Friends" },
        { "choice_id": 3132167, "label": "Purpose" }
      ],
      "settings": {
        "allows_not_applicable": true,
        "not_applicable_label": "n/a"
      }
    },
    "settings": { "is_required": true }
  }
}
```

**Response:**

```json
{
  "element_id": 1238491,
  "type": "ranking",
  "ranking": {
    "ranked_choices": [
      { "choice_id": 3132165, "label": "Health" },
      { "choice_id": 3132166, "label": "Family & Friends" }
    ],
    "not_applicable_choices": [{ "choice_id": 3132167, "label": "Purpose" }]
  }
}
```

---

## What needs to be built

### 1. `RankingQuestionMapper`

**File:** `src/Services/Factories/QuestionMapper/RankingQuestionMapper.php`

Produces a `choice_table`-style mapping where each choice becomes a sub-question
entry. No `answer_mapping` is needed because rank positions are dynamic.

```php
const TYPE = 'ranking';

public function mapQuestion(SurveyElementDTO $question, int $questionCounter): array
{
    $mapping = [
        'question_id'         => $question->element_id,
        'type'                => $question->question->type,
        'subquestion_mapping' => [],
        'mapped_data_type'    => SurveyAnswerContract::CONVERTED_TYPE_INT,
    ];

    $subquestionIndex = 1;
    foreach ($question->question->ranking->choices as $choice) {
        $mapping['subquestion_mapping'][$choice->choice_id] = [
            'question_id' => $choice->choice_id,
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
    'question_id' => 1238491,
    'type'        => 'ranking',
    'subquestion_mapping' => [
        3132165 => ['question_id' => 3132165, 'field' => 'question_1_1'],
        3132166 => ['question_id' => 3132166, 'field' => 'question_1_2'],
        3132167 => ['question_id' => 3132167, 'field' => 'question_1_3'],
    ],
    'mapped_data_type' => 'int',
]
```

### 2. `RankingQuestionAndAnswerCreator`

**File:**
`src/Services/Factories/QuestionAndAnswerCreator/RankingQuestionAndAnswerCreator.php`

Creates one `SurveyQuestion` per choice (using `choice_id` as the
`surveyhero_question_id`, and `element_id` as `surveyhero_element_id`). No
`SurveyAnswer` rows are pre-seeded because the position value differs per
response.

```php
const TYPE = 'ranking';

public function updateOrCreateQuestionAndAnswer(SurveyElementDTO $question, SurveyContract $survey, string $lang): SurveyQuestionContract|array
{
    $questions = [];
    foreach ($question->question->ranking->choices as $choice) {
        $questions[] = $this->updateOrCreateQuestion(
            $survey, $lang,
            $question->element_id,  // surveyhero_element_id
            $choice->label,
            $choice->choice_id      // surveyhero_question_id (sub-question key)
        );
    }
    return $questions;
}
```

### 3. `RankingResponseCreator`

**File:** `src/Services/Factories/ResponseCreator/RankingResponseCreator.php`

Handles `type: ranking` API responses. Iterates `ranked_choices` in order
(position = index + 1) and `not_applicable_choices` (stored as position `0`).
For each, it finds the sub-question by `choice_id`, then
`fetchOrCreateInputAnswer` with the int position.

```php
const TYPE = 'ranking';

public function updateOrCreateQuestionResponse(
    \stdClass $surveyheroQuestionResponse,
    SurveyResponseContract $response,
    array $questionMapping): SurveyQuestionResponseContract|array
{
    $responseList = [];
    $mappingService = new SurveyMappingService;

    // ranked choices — position is 1-based
    foreach ($surveyheroQuestionResponse->ranking->ranked_choices as $position => $choice) {
        $subquestionMapping = $mappingService->getSubquestionMapping($choice->choice_id, $questionMapping);
        $surveyQuestion     = $this->findSurveyQuestion($choice->choice_id);
        $surveyAnswer       = $this->fetchOrCreateInputAnswer($surveyQuestion, SurveyAnswerContract::CONVERTED_TYPE_INT, $position + 1);

        $existingResponse = $this->findExistingQuestionResponse($subquestionMapping['question_id'], $response);
        $responseData     = $this->createSurveyQuestionResponseData($surveyQuestion, $response, $surveyAnswer);

        $responseList[] = app(SurveyheroRegistrar::class)->getSurveyQuestionResponseClass()::updateOrCreate(
            ['id' => $existingResponse->id ?? null],
            $responseData
        );
    }

    // not-applicable choices — stored as position 0
    foreach ($surveyheroQuestionResponse->ranking->not_applicable_choices as $choice) {
        $subquestionMapping = $mappingService->getSubquestionMapping($choice->choice_id, $questionMapping);
        $surveyQuestion     = $this->findSurveyQuestion($choice->choice_id);
        $surveyAnswer       = $this->fetchOrCreateInputAnswer($surveyQuestion, SurveyAnswerContract::CONVERTED_TYPE_INT, 0);

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

> Position `0` is a sentinel for "not applicable". Consumers of the data can
> filter on `converted_int_value = 0` to detect skipped choices.

### 4. Register in service dispatch tables

**`SurveyMappingService::getQuestionMapper()`**:

```php
RankingQuestionMapper::TYPE => new RankingQuestionMapper,
```

**`SurveyQuestionsAndAnswersImportService::getQuestionAndAnswerCreator()`**:

```php
RankingQuestionAndAnswerCreator::TYPE => new RankingQuestionAndAnswerCreator,
```

**`SurveyResponseImportService::getQuestionResponseCreator()`**:

```php
RankingResponseCreator::TYPE => new RankingResponseCreator,
```

---

## Tests

- Fixture `tests/Fixtures/Saloon/get-survey-questions-ranking.json` with a
  `ranking` question.
- Fixture `tests/Fixtures/Saloon/get-survey-response-ranking.json` with ranked
  and not-applicable choices.
- Unit test for `RankingQuestionMapper` — assert `subquestion_mapping` keys and
  field names.
- Unit test for `RankingQuestionAndAnswerCreator` — assert one `SurveyQuestion`
  created per choice.
- Unit test for `RankingResponseCreator` — assert positions stored correctly, 0
  for n/a choices.

---

## Complexity estimate

Medium. The sub-question pattern is established by `choice_table`, but the
response structure (`ranked_choices` / `not_applicable_choices`) requires a
dedicated creator with position-index logic.
