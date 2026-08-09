<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            if (! Schema::hasColumn('patients', 'state_id')) {
                $table->string('state_id')->nullable()->after('phone');
            }

            if (! Schema::hasColumn('patients', 'county_id')) {
                $table->string('county_id')->nullable()->after('state_id');
            }

            if (! Schema::hasColumn('patients', 'facility_id')) {
                $table->string('facility_id')->nullable()->after('county_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            if (Schema::hasColumn('patients', 'facility_id')) {
                $table->dropColumn('facility_id');
            }

            if (Schema::hasColumn('patients', 'county_id')) {
                $table->dropColumn('county_id');
            }

            if (Schema::hasColumn('patients', 'state_id')) {
                $table->dropColumn('state_id');
            }
        });
    }
};
