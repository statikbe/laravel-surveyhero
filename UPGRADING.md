# Upgrading

## From v2 to v3

### 1. Remove `use HasFactory` from custom models (PHP 8.2 fatal)

All package models now declare `newFactory()` with a typed return. If your extending model also applies `use HasFactory`, PHP 8.2 throws:

```
Declaration of HasFactory::newFactory() must be compatible with SurveyResponse::newFactory(): SurveyResponseFactory
```

**Fix:** Remove `use HasFactory` (and its import) from any model that extends a package model. The trait is already applied by the parent.

```php
// Before
class SurveyResponse extends \Statikbe\Surveyhero\Models\SurveyResponse
{
    use HasFactory; // <-- remove this
    use SoftDeletes;
}

// After
class SurveyResponse extends \Statikbe\Surveyhero\Models\SurveyResponse
{
    use SoftDeletes;
}
```

---

### 2. Refactor your webhook controller to extend the package controller

The package now ships a full `SurveyheroWebhookController` that handles validation, survey lookup, collector filtering, import, and error wrapping. Custom controllers that reimplemented this logic should now extend the base controller instead.

```php
use Statikbe\Surveyhero\Http\Controllers\Api\SurveyheroWebhookController as BaseWebhookController;
use Statikbe\Surveyhero\Models\Survey;
use Statikbe\Surveyhero\Services\Info\ResponseImportInfo;

class MyWebhookController extends BaseWebhookController
{
    protected function handlePreImport(Survey $survey, array $collectors, array $responseData): void
    {
        // Runs before the import. Throw UnwantedResponseNotImportedException to skip the import
        // and return 200 OK (so Surveyhero does not retry).
    }

    protected function handlePostImport(Survey $survey, array $collectors, array $responseData, ?ResponseImportInfo $responseInfo): void
    {
        // Runs after a successful import.
        // $responseInfo is null if the response was already imported previously.
    }
}
```

You can keep your own route URL — the package-provided `Surveyhero::webhookRoutes()` is optional.

---

### 3. Run the upgrade migration

Two new columns are added. The package ships an upgrade migration (`upgrade_surveyhero_tables_v3`) that is registered automatically. Run:

```shell
php artisan migrate
```

This adds:
- `surveys.use_resume_link` (boolean, default `false`) — enables fetching a resume link from the API per response
- `survey_responses.resume_link` (nullable string) — stores the resume link so respondents can continue incomplete surveys

The migration is guarded with `Schema::hasColumn()` checks so it is safe to run on fresh installs too.

---

### 4. Add `rate_limit_fallback_seconds` to your published config

A new key controls how long the connector sleeps when a 429 rate-limit response is received without a `Retry-After` header.

```php
// config/surveyhero.php
'rate_limit_fallback_seconds' => env('SURVEYHERO_RATE_LIMIT_FALLBACK', 60),
```

> **Warning:** In webhook / HTTP contexts the connector sleeps inline for this duration, blocking a PHP-FPM worker. If you handle webhooks under load, either keep this value well below your server's request timeout or queue the import job instead of processing it inline.

---

### 5. Update `config/surveyhero.php` models to point to your app models

If you have custom model classes, make sure the `models` array in your published config references them. Without this the `SurveyheroRegistrar` resolves the vendor base models, bypassing your customisations (soft deletes, extra relationships, etc.).

```php
'models' => [
    'survey'                   => \App\Models\Survey::class,
    'survey_question'          => \App\Models\SurveyQuestion::class,
    'survey_answer'            => \App\Models\SurveyAnswer::class,
    'survey_response'          => \App\Models\SurveyResponse::class,
    'survey_question_response' => \App\Models\SurveyQuestionResponse::class,
],
```

---

### 6. New events (informational, not breaking)

`SurveyResponseImported` and `SurveyResponseIncompletelyImported` are now dispatched after each import (implements `ShouldDispatchAfterCommit`). If you previously added post-import logic inline in a custom webhook controller, consider moving it to an event listener instead — this is especially useful when processing imports via CLI commands.

---

### 7. `question_mapping` config structure (informational)

Each entry in the `question_mapping` array now supports a nested `questions` key and a `use_resume_link` flag. Existing entries with only `survey_id` + `collectors` continue to work — they just opt out of config-level question mapping (only DB mapping is used).

```php
// New structure
[
    'survey_id'       => 1234567,
    'collectors'      => [9876543],
    'use_resume_link' => false,
    'questions'       => [
        // question mapping entries
    ],
],
```

---

## From v1 to v2

A default mapping config is now stored in the database. The config file question mapping should only be used to overwrite the database mapping with custom values.
Check the **Generate mapping** section in the [docs](README.md) for more info.

Add following migration to your project: (remember to change the tables names to your custom configured names, if necessary)

```php
    Schema::table('surveys', function (Blueprint $table) {
        $table->json('collector_ids')->after('name')->nullable();
        $table->json('question_mapping')->after('name')->nullable();
    });

    Schema::table('survey_questions', function (Blueprint $table) {
        $table->bigInteger('surveyhero_element_id')->after('surveyhero_question_id')->nullable();
    });
```

And run following command to generate the mapping in the database:

```shell
php artisan surveyhero:map --updateDatabase
```
