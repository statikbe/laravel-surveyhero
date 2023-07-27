<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUniqueSurveyheroIdIndices extends Migration
{
    public function up()
    {
        $tableNames = config('surveyhero.table_names');

        if (empty($tableNames)) {
            throw new \Exception('Error: config/surveyhero.php not loaded. Check your config file, run [php artisan config:clear] and try again.');
        }

        Schema::table($tableNames['surveys']['name'], function (Blueprint $table) {
            $table->unique(['surveyhero_id']);
        });

        Schema::table($tableNames['survey_responses']['name'], function (Blueprint $table) {
            $table->unique(['surveyhero_id']);
        });

        Schema::table($tableNames['survey_questions']['name'], function (Blueprint $table) {
            $table->unique(['surveyhero_question_id']);
        });

        Schema::table($tableNames['survey_question_responses']['name'], function (Blueprint $table) {
            $table->unique(['survey_response_id', 'survey_question_id', 'survey_answer_id']);
        });
    }
}
