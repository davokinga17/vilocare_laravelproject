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
            if (DB::getDriverName() === 'sqlite') {
                DB::table('patients')
                    ->select(['patient_id', 'age_category', 'age'])
                    ->whereNull('age')
                    ->get()
                    ->each(function ($patient): void {
                        if (is_string($patient->age_category) && preg_match('/^\d+$/', $patient->age_category) === 1) {
                            DB::table('patients')
                                ->where('patient_id', $patient->patient_id)
                                ->update(['age' => (int) $patient->age_category]);
                        }
                    });
            } else {
                DB::statement("
                    UPDATE patients
                    SET age = CASE
                        WHEN age IS NULL AND age_category REGEXP '^[0-9]+$' THEN CAST(age_category AS UNSIGNED)
                        ELSE age
                    END
                ");
            }
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
