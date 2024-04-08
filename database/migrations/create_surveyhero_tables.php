<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSurveyheroTables extends Migration
{
    public function up()
    {
        $tableNames = config('surveyhero.table_names');

        if (empty($tableNames)) {
            throw new \Exception('Error: config/surveyhero.php not loaded. Check your config file, run [php artisan config:clear] and try again.');
        }

        Schema::create($tableNames['surveys']['name'], function (Blueprint $table) {
            $table->id();
            $table->bigInteger('surveyhero_id');

            $table->string('name');
            $table->json('collector_ids')->nullable();
            $table->json('question_mapping')->nullable();
            $table->datetime('survey_last_imported')->nullable();
            $table->boolean('use_resume_link')->default(false);

            $table->timestamps();
        });

        Schema::create($tableNames['survey_responses']['name'], function (Blueprint $table) use ($tableNames) {
            $table->id();
            $table->bigInteger('surveyhero_id');

            $table->datetime('survey_start_date')->nullable();
            $table->datetime('survey_last_updated')->nullable();
            $table->string('survey_language')->nullable();
            $table->boolean('survey_completed')->default(false);
            $table->json('surveyhero_link_parameters')->nullable();
            $table->string('resume_link')->nullable();

            $table->foreignId($tableNames['surveys']['foreign_key'])
                ->constrained($tableNames['surveys']['name'])
                ->onDelete('cascade');

            $table->timestamps();
        });

        Schema::create($tableNames['survey_questions']['name'], function (Blueprint $table) use ($tableNames) {
            $table->id();
            $table->foreignId($tableNames['surveys']['foreign_key'])
                ->constrained($tableNames['surveys']['name'])
                ->onDelete('cascade');
            $table->bigInteger('surveyhero_element_id')->nullable();
            $table->bigInteger('surveyhero_question_id')->nullable();
            $table->string('field');
            $table->json('label');
            $table->timestamps();

            $table->index(['field']);
        });

        Schema::create($tableNames['survey_answers']['name'], function (Blueprint $table) use ($tableNames) {
            $table->id();
            $table->foreignId($tableNames['survey_questions']['foreign_key'])
                ->constrained($tableNames['survey_questions']['name'])
                ->onDelete('cascade');
            $table->bigInteger('surveyhero_answer_id')->nullable();
            $table->text('converted_string_value')->nullable();
            $table->integer('converted_int_value')->nullable();
            $table->json('label')->nullable();
            $table->timestamps();
        });

        Schema::create($tableNames['survey_question_responses']['name'], function (Blueprint $table) use ($tableNames) {
            $table->id();
            $table->foreignId($tableNames['survey_questions']['foreign_key'])
                ->constrained($tableNames['survey_questions']['name']);
            $table->foreignId($tableNames['survey_answers']['foreign_key'])
                ->nullable()
                ->constrained($tableNames['survey_answers']['name']);

            $table->foreignId($tableNames['survey_responses']['foreign_key'])
                ->constrained($tableNames['survey_responses']['name'])
                ->onDelete('cascade');

            $table->timestamps();
        });
    }
}
