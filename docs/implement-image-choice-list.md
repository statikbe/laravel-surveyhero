# Plan: implement `image_choice_list` question type

## Overview

`image_choice_list` is a single/multi-select question where each choice is
represented by an image plus a text label. The API question payload uses
`image_choice_list.choices[]`, each with `choice_id`, `label`, and `image_url`.
The **response** payload type is `choices` — identical structure to
`choice_list` responses — so `ChoicesResponseCreator` already handles it.

Only two of the three factory layers are missing.

---

## API shapes

**Question definition** (`GET /surveys/{id}/elements`):

```json
{
  "element_id": 666941,
  "type": "question",
  "question": {
    "question_text": "Pick your two favourite images:",
    "type": "image_choice_list",
    "image_choice_list": {
      "choices": [
        { "choice_id": 6244, "label": "Beach", "image_url": "https://..." },
        { "choice_id": 6245, "label": "Woods", "image_url": "https://..." }
      ],
      "settings": {
        "allows_multiple_choices": true,
        "min_number_of_choices": 2,
        "max_number_of_choices": 2
      }
    },
    "settings": { "is_required": false }
  }
}
```

**Response** (`GET /surveys/{id}/responses/{id}`):

```json
{
  "element_id": 666941,
  "question_text": "Pick your two favourite images:",
  "type": "choices",
  "choices": [
    { "choice_id": 6244, "label": "Beach" },
    { "choice_id": 6245, "label": "Woods" }
  ]
}
```

---

## What needs to be built

### 1. `ImageChoiceListQuestionMapper`

**File:**
`src/Services/Factories/QuestionMapper/ImageChoiceListQuestionMapper.php`

Mirrors `ChoiceListQuestionMapper` exactly, but reads from
`image_choice_list.choices` instead of `choice_list.choices`.

```php
const TYPE = 'image_choice_list';

public function mapQuestion(SurveyElementDTO $question, int $questionCounter): array
{
    $questionData = $this->createQuestionMap(
        $question->element_id,
        $question->question->type,
        SurveyAnswerContract::CONVERTED_TYPE_INT,
        $questionCounter
    );

    foreach ($question->question->image_choice_list->choices as $choiceKey => $choice) {
        $questionData['answer_mapping'][$choice->choice_id] = $choiceKey + 1;
    }

    return $questionData;
}
```

The generated config entry looks identical to `choice_list`:

```php
[
    'question_id' => 666941,
    'type'        => 'image_choice_list',
    'field'       => 'question_1',
    'mapped_data_type' => 'int',
    'answer_mapping' => [6244 => 1, 6245 => 2],
]
```

### 2. `ImageChoiceListQuestionAndAnswerCreator`

**File:**
`src/Services/Factories/QuestionAndAnswerCreator/ImageChoiceListQuestionAndAnswerCreator.php`

Mirrors `ChoiceListQuestionAndAnswerCreator`, iterating
`image_choice_list.choices`. The `image_url` field is not persisted — only
`choice_id` and `label` are relevant for answer import.

```php
const TYPE = 'image_choice_list';

public function updateOrCreateQuestionAndAnswer(SurveyElementDTO $question, SurveyContract $survey, string $lang): SurveyQuestionContract|array
{
    $surveyQuestion = $this->updateOrCreateQuestion($survey, $lang, $question->element_id, $question->question->question_text);

    foreach ($question->question->image_choice_list->choices as $choice) {
        $responseData = [
            'survey_question_id'   => $surveyQuestion->id,
            'surveyhero_answer_id' => $choice->choice_id,
            'label'                => [$lang => $choice->label],
        ];

        $questionMapping = (new SurveyMappingService)->getQuestionMappingForSurvey($survey, $question->element_id);
        $mappedChoice    = $this->getChoiceMapping($choice->choice_id, $question->element_id, $questionMapping);

        $this->setChoiceAndConvertToDataType($mappedChoice, $questionMapping['mapped_data_type'], $responseData, $choice);

        app(SurveyheroRegistrar::class)->getSurveyAnswerClass()::updateOrCreate(
            ['survey_question_id' => $surveyQuestion->id, 'surveyhero_answer_id' => $choice->choice_id],
            $responseData
        );
    }

    return $surveyQuestion;
}
```

### 3. ResponseCreator — **no new class needed**

The response `type` is `choices`, which is already handled by
`ChoicesResponseCreator`. No changes required.

### 4. Register in service dispatch tables

**`SurveyMappingService::getQuestionMapper()`** — add:

```php
ImageChoiceListQuestionMapper::TYPE => new ImageChoiceListQuestionMapper,
```

**`SurveyQuestionsAndAnswersImportService::getQuestionAndAnswerCreator()`** —
add:

```php
ImageChoiceListQuestionAndAnswerCreator::TYPE => new ImageChoiceListQuestionAndAnswerCreator,
```

No change needed in `SurveyResponseImportService`.

---

## Tests

- Add `tests/Fixtures/Saloon/get-survey-questions-image-choice-list.json` with
  an `image_choice_list` question.
- Unit test for `ImageChoiceListQuestionMapper` — assert mapping structure
  matches a `choice_list` equivalent.
- Unit test for `ImageChoiceListQuestionAndAnswerCreator` — assert a
  `SurveyQuestion` and its `SurveyAnswer` rows are created.
- Response import is already covered by the existing `ChoicesResponseCreator`
  tests.

---

## Complexity estimate

Low. Almost identical to `choice_list`; the only difference is the source
property name on the DTO.
