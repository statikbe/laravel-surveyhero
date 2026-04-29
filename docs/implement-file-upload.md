# Plan: implement `file_upload` question type

## Overview

`file_upload` allows respondents to attach a file to their survey response. The
API returns file metadata: a filename, size in bytes, and a relative download
path. Because the uploaded content is per-respondent and cannot be pre-defined,
no `SurveyAnswer` rows are seeded at question-import time. At response-import
time, the download path is stored as a dynamic string answer — following the
same pattern as `TextResponseCreator`. The response `type` field is `file`.

---

## API shapes

**Question definition:**

```json
{
  "element_id": 1387752,
  "type": "question",
  "question": {
    "question_text": "Please upload your image here:",
    "type": "file_upload",
    "file_upload": {
      "max_file_size_in_mb": 25,
      "accepted_file_types": ["gif", "jpg", "jpeg", "png"]
    },
    "settings": { "is_required": false }
  }
}
```

**Response:**

```json
{
  "element_id": 1387752,
  "question_text": "Please upload your image here:",
  "type": "file",
  "file": {
    "name": "Galapagos.jpg",
    "size": 480447,
    "path": "/v1/download/element/1387752/response/3875825"
  }
}
```

---

## What needs to be built

### 1. `FileUploadQuestionMapper`

**File:** `src/Services/Factories/QuestionMapper/FileUploadQuestionMapper.php`

Simple single-field mapping. The stored value will be the file download path, so
`mapped_data_type` is `string`.

```php
const TYPE = 'file_upload';

public function mapQuestion(SurveyElementDTO $question, int $questionCounter): array
{
    return $this->createQuestionMap(
        $question->element_id,
        $question->question->type,
        SurveyAnswerContract::CONVERTED_TYPE_STRING,
        $questionCounter
    );
}
```

Generated config:

```php
[
    'question_id'      => 1387752,
    'type'             => 'file_upload',
    'field'            => 'question_1',
    'mapped_data_type' => 'string',
]
```

### 2. `FileUploadQuestionAndAnswerCreator`

**File:**
`src/Services/Factories/QuestionAndAnswerCreator/FileUploadQuestionAndAnswerCreator.php`

Creates the `SurveyQuestion` row. No `SurveyAnswer` rows are pre-seeded
(identical pattern to `InputQuestionAndAnswerCreator`).

```php
const TYPE = 'file_upload';

public function updateOrCreateQuestionAndAnswer(SurveyElementDTO $question, SurveyContract $survey, string $lang): SurveyQuestionContract|array
{
    return $this->updateOrCreateQuestion($survey, $lang, $question->element_id, $question->question->question_text);
}
```

### 3. `FileResponseCreator`

**File:** `src/Services/Factories/ResponseCreator/FileResponseCreator.php`

Handles `type: file` responses. Stores the API-provided download path
(`file->path`) as the `converted_string_value` of a dynamically created answer.
The filename (`file->name`) and size (`file->size`) are not stored separately —
if needed, consumers can call the download endpoint using the path.

```php
const TYPE = 'file';

public function updateOrCreateQuestionResponse(
    \stdClass $surveyheroQuestionResponse,
    SurveyResponseContract $response,
    array $questionMapping): SurveyQuestionResponseContract|array
{
    $existingResponse = $this->findExistingQuestionResponse($questionMapping['question_id'], $response);
    $surveyQuestion   = $this->findSurveyQuestion($surveyheroQuestionResponse->element_id);

    $surveyAnswer = $this->fetchOrCreateInputAnswer(
        $surveyQuestion,
        SurveyAnswerContract::CONVERTED_TYPE_STRING,
        $surveyheroQuestionResponse->file->path
    );

    $responseData = $this->createSurveyQuestionResponseData($surveyQuestion, $response, $surveyAnswer);

    return app(SurveyheroRegistrar::class)->getSurveyQuestionResponseClass()::updateOrCreate(
        ['id' => $existingResponse->id ?? null],
        $responseData
    );
}
```

### 4. Register in service dispatch tables

**`SurveyMappingService::getQuestionMapper()`**:

```php
FileUploadQuestionMapper::TYPE => new FileUploadQuestionMapper,
```

**`SurveyQuestionsAndAnswersImportService::getQuestionAndAnswerCreator()`**:

```php
FileUploadQuestionAndAnswerCreator::TYPE => new FileUploadQuestionAndAnswerCreator,
```

**`SurveyResponseImportService::getQuestionResponseCreator()`**:

```php
FileResponseCreator::TYPE => new FileResponseCreator,
```

---

## Design decisions

**What to store:** The `file->path` field (e.g.,
`/v1/download/element/1387752/response/3875825`) is a stable relative reference
to the file on the SurveyHero API. Storing this means callers need an
authenticated HTTP client to retrieve the actual file — the package already has
`SurveyheroClient`, so this is straightforward.

**What not to store:** `file->name` and `file->size` are omitted. If this
becomes a requirement, a JSON-encoded string of all three fields could be stored
as `converted_string_value` and a custom accessor on the model could decode it.
This is a future concern and should not be added preemptively.

**Unanswered file questions:** If a respondent skipped a `file_upload` question,
the element will be absent from the `answers` array entirely — the response
import loop already handles this (it only processes elements that appear in the
response).

---

## Tests

- Fixture `tests/Fixtures/Saloon/get-survey-questions-file-upload.json` with a
  `file_upload` question.
- Fixture `tests/Fixtures/Saloon/get-survey-response-file-upload.json` with a
  `file` response entry.
- Unit test for `FileUploadQuestionMapper` — assert single field mapping with
  `string` data type.
- Unit test for `FileUploadQuestionAndAnswerCreator` — assert one
  `SurveyQuestion` created, no `SurveyAnswer` rows.
- Unit test for `FileResponseCreator` — assert `converted_string_value` equals
  the path from the fixture.

---

## Complexity estimate

Low. Structurally the simplest of the five types — a degenerate case of the
`input` pattern where the value is always a string path.
