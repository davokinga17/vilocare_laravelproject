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
        Schema::create('viral_load_results', function (Blueprint $table) {
            $table->id('vl_id'); // Primary key
            $table->unsignedBigInteger('patient_id');
            $table->date('sample_date')->nullable();
            $table->string('result')->nullable();
            $table->string('lab')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->nullable();
            $table->date('result_date')->nullable();
            $table->string('sample_type')->nullable();
            $table->decimal('result_cpml', 10, 2)->nullable();
            $table->decimal('result_log', 5, 2)->nullable();
            $table->text('comments')->nullable();
            $table->string('requesting_clinician')->nullable();
            $table->string('clinician_cellphone')->nullable();
            $table->date('request_date')->nullable();
            $table->string('vl_testing_indication')->nullable();
            // No timestamps
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('viral_load_results');
    }
};
