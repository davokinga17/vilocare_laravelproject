<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id('payment_id');
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('eac_id')->nullable();
            $table->unsignedBigInteger('vl_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->string('payment_type', 50);
            $table->string('service_label', 120);
            $table->decimal('amount', 10, 2);
            $table->string('currency', 10)->default('SSP');
            $table->string('payment_method', 50)->default('manual');
            $table->string('status', 30)->default('paid');
            $table->string('receipt_number', 50)->unique();
            $table->string('external_reference', 120)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['patient_id', 'payment_type']);
            $table->index(['eac_id', 'payment_type']);
            $table->index('vl_id');
            $table->index('created_by');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
