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
        Schema::create('eac_sessions', function (Blueprint $table) {
            $table->id('eac_id');
            $table->unsignedBigInteger('patient_id');
            $table->integer('session_number');
            $table->date('session_date')->nullable();
            $table->string('counselor')->nullable();
            $table->text('barriers')->nullable();
            $table->text('action_plan')->nullable();
            $table->text('notes')->nullable();
            $table->string('completion_status')->default('Pending');
            $table->date('next_session_date')->nullable();
            // No timestamps
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('eac_sessions');
    }
};
