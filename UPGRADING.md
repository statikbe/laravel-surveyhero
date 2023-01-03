# Upgrading

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
