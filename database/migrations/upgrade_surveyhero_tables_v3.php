<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableNames = config('surveyhero.table_names');

        if (empty($tableNames)) {
            throw new Exception('Error: config/surveyhero.php not loaded. Check your config file, run [php artisan config:clear] and try again.');
        }

        $surveysTable = $tableNames['surveys']['name'];
        $responsesTable = $tableNames['survey_responses']['name'];

        Schema::table($surveysTable, function (Blueprint $table) use ($surveysTable) {
            if (! Schema::hasColumn($surveysTable, 'use_resume_link')) {
                $table->boolean('use_resume_link')->default(false)->after('survey_last_imported');
            }
        });

        Schema::table($responsesTable, function (Blueprint $table) use ($responsesTable) {
            if (! Schema::hasColumn($responsesTable, 'resume_link')) {
                $table->string('resume_link')->nullable()->after('surveyhero_link_parameters');
            }
        });
    }

    public function down(): void
    {
        $tableNames = config('surveyhero.table_names');

        $surveysTable = $tableNames['surveys']['name'];
        $responsesTable = $tableNames['survey_responses']['name'];

        Schema::table($surveysTable, function (Blueprint $table) {
            $table->dropColumn('use_resume_link');
        });

        Schema::table($responsesTable, function (Blueprint $table) {
            $table->dropColumn('resume_link');
        });
    }
};
