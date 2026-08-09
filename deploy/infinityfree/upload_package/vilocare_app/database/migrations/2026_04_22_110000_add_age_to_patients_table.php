<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            if (! Schema::hasColumn('patients', 'age')) {
                $table->unsignedTinyInteger('age')->nullable()->after('current_regimen');
            }
        });

        if (Schema::hasColumn('patients', 'age') && Schema::hasColumn('patients', 'age_category')) {
            DB::statement("
                UPDATE patients
                SET age = CASE
                    WHEN age IS NULL AND age_category REGEXP '^[0-9]+$' THEN CAST(age_category AS UNSIGNED)
                    ELSE age
                END
            ");
        }
    }

    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            if (Schema::hasColumn('patients', 'age')) {
                $table->dropColumn('age');
            }
        });
    }
};
