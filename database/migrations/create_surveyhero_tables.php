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

        Schema::create($tableNames['surveys'], function (Blueprint $table) {
            $table->id();
            $table->bigInteger('surveyhero_id');

            $table->string('name');
            $table->datetime('survey_last_imported')->nullable();

            $table->timestamps();
        });

        Schema::create($tableNames['survey_responses'], function (Blueprint $table) use ($tableNames) {
            $table->id();
            $table->bigInteger('surveyhero_id');

            $table->datetime('survey_start_date')->nullable();
            $table->datetime('survey_last_updated')->nullable();
            $table->string('survey_language');
            $table->boolean('survey_completed')->default(false);
            $table->json('surveyhero_link_parameters')->nullable();

            $table->unsignedBigInteger('survey_id');
            $table->foreign('survey_id')
                ->references('id')
                ->on($tableNames['surveys'])
                ->onDelete('cascade');

            $table->timestamps();
        });

        Schema::create($tableNames['survey_questions'], function (Blueprint $table) use ($tableNames) {
            $table->id();
            $table->foreignId('survey_id')
                ->constrained($tableNames['surveys'])
                ->onDelete('cascade');
            $table->bigInteger('surveyhero_question_id')->nullable();
            $table->string('field');
            $table->json('label');
            $table->timestamps();

            $table->index(['field']);
        });

        Schema::create($tableNames['survey_answers'], function (Blueprint $table) use ($tableNames) {
            $table->id();
            $table->foreignId('survey_question_id')
                ->constrained($tableNames['survey_questions'])
                ->onDelete('cascade');
            $table->bigInteger('surveyhero_answer_id')->nullable();
            $table->string('converted_string_value')->nullable();
            $table->integer('converted_int_value')->nullable();
            $table->json('label')->nullable();
            $table->timestamps();
        });

        Schema::create($tableNames['survey_question_responses'], function (Blueprint $table) use ($tableNames) {
            $table->id();
            $table->foreignId('survey_question_id')->constrained();
            $table->foreignId('survey_answer_id')->nullable()->constrained($tableNames['survey_answers']);

            $table->unsignedBigInteger('survey_response_id');
            $table->foreign('survey_response_id')
                ->references('id')
                ->on($tableNames['survey_responses'])
                ->onDelete('cascade');

            $table->timestamps();
        });
    }
}
