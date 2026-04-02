<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->id('patient_id'); // Primary key as patient_id
            $table->string('art_number');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('sex');
            $table->text('address')->nullable();
            $table->string('phone')->nullable();
            $table->date('art_start_date')->nullable();
            $table->string('current_regimen')->nullable();
            $table->string('age_category')->nullable();
            $table->boolean('is_pregnant')->default(false);
            $table->boolean('is_breastfeeding')->default(false);
            // No timestamps as per model
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
