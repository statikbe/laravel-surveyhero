# Upgrading

## From v3 to v4

v4 upgrades `maatwebsite/excel` from 3.1 to 4.0. Laravel-Excel 4 requires PHP 8.3+ and
Laravel 12+, so this package's minimum requirements move with it.

### 1. Raise your PHP and Laravel versions — **High Impact**

| | v3 | v4 |
| --- | --- | --- |
| PHP | 8.1 – 8.4 | 8.3 – 8.5 |
| Laravel | 10 – 13 | 12 – 13 |
| `maatwebsite/excel` | ^3.1 | ^4.0 |
| `phpoffice/phpspreadsheet` | ^1.30 | ^5.8 |

If you are on PHP 8.1/8.2 or Laravel 10/11, upgrade those first. Staying on
`maatwebsite/excel` 3.1 is not an option on PHP 8.5: `phpoffice/phpspreadsheet` 1.x
declares `"php": ">=7.4.0 <8.5.0"`, which is what blocks PHP 8.5 today.

### 2. Add return types to custom export sheets — **High Impact**

Laravel-Excel 4 declares native types on every concern. If you pass your own sheets to
`SurveyExport::setSheets()`, or extend the package's sheets, update your signatures:

```php
// Before (v3)
public function query() { ... }
public function collection() { ... }
public function map($row): array { ... }

// After (v4)
public function query(): Builder|EloquentBuilder|Relation { ... }
public function collection(): Enumerable { ... }
public function map(mixed $row): array { ... }
```

Other common ones: `headings(): array`, `title(): string`, `array(): array`,
`registerEvents(): array`.

### 3. Implement the `Export` marker on classes using `WithMultipleSheets` — **Medium Impact**

`Maatwebsite\Excel\Concerns\Export` is new in v4. A class that only implements
`WithMultipleSheets` (no data-source concern of its own) must now implement it
explicitly, and every sheet it returns must implement `Export` or `Import`:

```php
use Maatwebsite\Excel\Concerns\Export;

class MySurveyExport extends \Statikbe\Surveyhero\Exports\SurveyExport implements Export
{
    // ...
}
```

The package's own `SurveyExport` already does this.

### 4. Review direct PhpSpreadsheet usage — **Medium Impact**

`phpoffice/phpspreadsheet` jumps from 1.30 to 5.x. Code that only touches Laravel-Excel's
own API is unaffected, but review anything that reaches into PhpSpreadsheet directly:
`WithEvents` listeners (`$event->sheet->getDelegate()`), `WithCharts` / `WithDrawings`,
custom value binders, and direct use of `NumberFormat`, `Style` or `Coordinate`. See the
[PhpSpreadsheet changelog](https://github.com/PHPOffice/PhpSpreadsheet/blob/master/CHANGELOG.md)
for the 2.0 – 5.0 breaking changes.

### 5. Register the Excel service provider in package tests — **Low Impact**

Only relevant if you run this package's test suite or a testbench app that does not
auto-discover providers: add `Maatwebsite\Excel\ExcelServiceProvider::class` to your
`getPackageProviders()`.

### 6. Response export rows are now ordered deterministically — **Low Impact**

`AnswersSheet::query()` and `QuestionsSheet::query()` now append an `orderBy('id')`.
Laravel-Excel walks `FromQuery` exports with `LIMIT`/`OFFSET` chunking, which silently
duplicates and drops rows when the query has no unique sort. If you relied on the previous
(undefined) row order, note that answers and questions are now emitted in primary-key order.

---

## From v2 to v3

### 1. Remove `use HasFactory` from custom models (PHP 8.2 fatal) — **High Impact**

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

### 2. Refactor your webhook controller to extend the package controller — **High Impact**

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

### 3. Run the upgrade migration — **High Impact**

Two new columns are added. The package ships an upgrade migration (`upgrade_surveyhero_tables_v3`) that is registered automatically. Run:

```shell
php artisan migrate
```

This adds:
- `surveys.use_resume_link` (boolean, default `false`) — enables fetching a resume link from the API per response
- `survey_responses.resume_link` (nullable string) — stores the resume link so respondents can continue incomplete surveys

The migration is guarded with `Schema::hasColumn()` checks so it is safe to run on fresh installs too.

---

### 4. Add `rate_limit_fallback_seconds` to your published config — **Medium Impact**

A new key controls how long the connector sleeps when a 429 rate-limit response is received without a `Retry-After` header.

```php
// config/surveyhero.php
'rate_limit_fallback_seconds' => env('SURVEYHERO_RATE_LIMIT_FALLBACK', 60),
```

> **Warning:** In webhook / HTTP contexts the connector sleeps inline for this duration, blocking a PHP-FPM worker. If you handle webhooks under load, either keep this value well below your server's request timeout or queue the import job instead of processing it inline.

---

### 5. Update `config/surveyhero.php` models to point to your app models — **Medium Impact**

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

### 6. New events — **Low Impact**

`SurveyResponseImported` and `SurveyResponseIncompletelyImported` are now dispatched after each import (implements `ShouldDispatchAfterCommit`). If you previously added post-import logic inline in a custom webhook controller, consider moving it to an event listener instead — this is especially useful when processing imports via CLI commands.

---

### 7. `question_mapping` config structure — **Low Impact**

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
